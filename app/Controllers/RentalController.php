<?php
namespace App\Controllers;
use App\Core\{Controller,Database,Flash};
class RentalController extends Controller {
 public function cart():void{$cart=$_SESSION['cart']??[];$ids=array_map('intval',array_keys($cart));$products=[];if($ids){$in=implode(',',array_fill(0,count($ids),'?'));$s=Database::connection()->prepare("SELECT p.*,COUNT(c.id) available FROM products p LEFT JOIN containers c ON c.product_id=p.id AND c.lifecycle_status='AVAILABLE' WHERE p.id IN ($in) GROUP BY p.id");$s->execute($ids);$products=$s->fetchAll();}$locations=Database::connection()->query("SELECT * FROM locations WHERE status='ACTIVE'")->fetchAll();$this->view('account/cart',['title'=>'Giỏ thuê','products'=>$products,'cart'=>$cart,'locations'=>$locations]);}
 public function add():void{$returnTo=$_POST['return_to']??'';verify_csrf();$id=(int)($_POST['product_id']??0);$qty=max(1,min(20,(int)($_POST['quantity']??1)));$s=Database::connection()->prepare("SELECT p.id,COUNT(c.id) available FROM products p LEFT JOIN containers c ON c.product_id=p.id AND c.lifecycle_status='AVAILABLE' WHERE p.id=? AND p.status='ACTIVE' GROUP BY p.id");$s->execute([$id]);$p=$s->fetch();if(!$p||!(int)$p['available']){Flash::set('error','Hộp hiện chưa có sẵn.');$this->redirect($returnTo==='home'?'/':($returnTo==='catalog'?'/catalog':'/catalog'));}$_SESSION['cart'][$id]=min((int)$p['available'],($_SESSION['cart'][$id]??0)+$qty);Flash::set('success','Đã thêm hộp vào giỏ thuê.');$this->redirect($returnTo==='home'?'/':($returnTo==='catalog'?'/catalog':'/cart'));}
 public function remove():void{verify_csrf();unset($_SESSION['cart'][(int)($_POST['product_id']??0)]);$this->redirect('/cart');}
 public function checkout():void
 {
  $u=$this->requireAuth(['CUSTOMER','ADMIN']);verify_csrf();$cart=$_SESSION['cart']??[];
  $days=max(1,min(14,(int)($_POST['days']??3)));$pickup=(int)($_POST['pickup_location_id']??0);$ret=(int)($_POST['return_location_id']??0);
  $paymentMethod=$_POST['payment_method']??'BANK_TRANSFER';
  if(!in_array($paymentMethod,['BANK_TRANSFER','CASH'],true)){Flash::set('error','Vui lòng chọn phương thức thanh toán.');$this->redirect('/cart');}
  if(!$cart){Flash::set('error','Giỏ thuê đang trống.');$this->redirect('/catalog');}
  $db=Database::connection();
  try{
   $db->beginTransaction();
   $loc=$db->prepare("SELECT id FROM locations WHERE id IN (?,?) AND status='ACTIVE'");$loc->execute([$pickup,$ret]);$valid=$loc->fetchAll();
   if(count($valid)<1||($pickup!==$ret&&count($valid)<2))throw new \RuntimeException('Vui lòng chọn điểm nhận và điểm trả hợp lệ.');
   $items=[];$subtotal=0;$deposit=0;
   foreach($cart as $pid=>$qty){
    $s=$db->prepare("SELECT p.*, (SELECT COUNT(*) FROM containers c WHERE c.product_id=p.id AND c.location_id=? AND c.lifecycle_status='AVAILABLE') available FROM products p WHERE p.id=? AND p.status='ACTIVE' FOR UPDATE");$s->execute([$pickup,(int)$pid]);$p=$s->fetch();
    if(!$p||(int)$qty>(int)$p['available'])throw new \RuntimeException('Tồn kho vừa thay đổi. Vui lòng cập nhật giỏ thuê.');
    $qty=(int)$qty;$items[]=['p'=>$p,'qty'=>$qty];$subtotal+=(float)$p['rental_price']*$qty*$days;$deposit+=(float)$p['deposit_amount']*$qty;
   }
   $code='RB'.date('ymd').strtoupper(bin2hex(random_bytes(3)));$startAt=new \DateTimeImmutable();$due=$startAt->modify("+$days days");
   $s=$db->prepare("INSERT INTO rental_orders(order_code,user_id,pickup_location_id,planned_return_location_id,order_status,rental_start_at,due_at,rental_subtotal,deposit_total,grand_total) VALUES(?,?,?,?,?,?,?,?,?,?)");
   $status=$paymentMethod==='CASH'?'CONFIRMED':'PENDING_PAYMENT';
   $s->execute([$code,$u['id'],$pickup,$ret,$status,$startAt->format('Y-m-d H:i:s'),$due->format('Y-m-d H:i:s'),$subtotal,$deposit,$subtotal+$deposit]);$oid=(int)$db->lastInsertId();
   foreach($items as $it){
    $p=$it['p'];$qty=$it['qty'];$line=(float)$p['rental_price']*$qty*$days;
    $ins=$db->prepare('INSERT INTO rental_items(order_id,product_id,quantity,unit_rental_price,unit_deposit,rental_days,line_total) VALUES(?,?,?,?,?,?,?)');$ins->execute([$oid,$p['id'],$qty,$p['rental_price'],$p['deposit_amount'],$days,$line]);$iid=(int)$db->lastInsertId();
    $c=$db->prepare("SELECT id FROM containers WHERE product_id=? AND location_id=? AND lifecycle_status='AVAILABLE' ORDER BY id LIMIT $qty FOR UPDATE");$c->execute([$p['id'],$pickup]);$boxes=$c->fetchAll();
    if(count($boxes)!==$qty)throw new \RuntimeException('Không đủ hộp tại điểm nhận.');
    foreach($boxes as $box){$db->prepare("UPDATE containers SET lifecycle_status='RESERVED' WHERE id=?")->execute([$box['id']]);$db->prepare('INSERT INTO rental_item_containers(rental_item_id,container_id) VALUES(?,?)')->execute([$iid,$box['id']]);$db->prepare('INSERT INTO inventory_movements(container_id,from_location_id,to_location_id,actor_user_id,reason) VALUES(?,?,?,?,?)')->execute([$box['id'],$pickup,$pickup,$u['id'],'Giữ hộp cho đơn '.$code]);}
   }
   $provider=$paymentMethod==='CASH'?'CASH':'BANK_TRANSFER';
   $payment=$db->prepare("INSERT INTO payments(order_id,payment_type,provider,amount,status) VALUES(?,'RENTAL',?,?,'PENDING')");$payment->execute([$oid,$provider,$subtotal+$deposit]);$paymentId=(int)$db->lastInsertId();
   $db->prepare("INSERT INTO deposit_transactions(order_id,transaction_type,amount,status,reference_payment_id,reason) VALUES(?,'HOLD',?,'PENDING',?,'Chờ hoàn tất thanh toán đơn thuê')")->execute([$oid,$deposit,$paymentId]);
   $message=$provider==='BANK_TRANSFER'?'Đơn '.$code.' đang chờ xác nhận chuyển khoản.':'Đơn '.$code.' đã giữ hộp; thanh toán tại điểm nhận.';
   $db->prepare('INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)')->execute([$u['id'],'Đơn thuê đã được tạo',$message]);
   $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,note) VALUES(?,?,'ORDER_CREATED',?,?)")
       ->execute([$oid,$u['id'],$pickup,'Phương thức thanh toán: '.($provider==='CASH'?'Tiền mặt':'Chuyển khoản MB')]);
   $db->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,'CREATE_RENTAL_ORDER','rental_orders',?,?)")->execute([$u['id'],$oid,$_SERVER['REMOTE_ADDR']??null]);
   $db->commit();$_SESSION['cart']=[];Flash::set('success',$provider==='BANK_TRANSFER'?'Đơn đã tạo. Hãy chuyển khoản theo mã QR để nhân viên xác nhận.':'Đơn đã tạo. Thanh toán tại điểm nhận hộp.');$this->redirect('/order?id='.$oid);
  }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();Flash::set('error',$e instanceof \RuntimeException?$e->getMessage():'Chưa thể tạo đơn lúc này.');$this->redirect('/cart');}
 }
 public function changePaymentMethod():void{$u=$this->requireAuth(['CUSTOMER','ADMIN']);verify_csrf();$id=(int)($_POST['order_id']??0);$method=$_POST['payment_method']??'';if(!in_array($method,['BANK_TRANSFER','CASH'],true)){Flash::set('error','Phương thức thanh toán không hợp lệ.');$this->redirect('/orders');}$db=Database::connection();try{$db->beginTransaction();$q=$db->prepare("SELECT o.id,o.order_status,p.id payment_id,p.status payment_status FROM rental_orders o JOIN payments p ON p.order_id=o.id AND p.payment_type='RENTAL' WHERE o.id=? AND o.user_id=? ORDER BY p.id DESC LIMIT 1 FOR UPDATE");$q->execute([$id,$u['id']]);$order=$q->fetch();if(!$order||!in_array($order['order_status'],['PENDING_PAYMENT','CONFIRMED'],true)||$order['payment_status']!=='PENDING')throw new \RuntimeException('Chỉ có thể đổi phương thức trước khi thanh toán và nhận hộp.');$out=$db->prepare("SELECT COUNT(*) FROM rental_item_containers ric JOIN rental_items ri ON ri.id=ric.rental_item_id WHERE ri.order_id=? AND ric.item_status='OUT'");$out->execute([$id]);if((int)$out->fetchColumn()>0)throw new \RuntimeException('Hộp đã bàn giao nên không thể đổi thanh toán.');$provider=$method==='CASH'?'CASH':'BANK_TRANSFER';$status=$provider==='CASH'?'CONFIRMED':'PENDING_PAYMENT';$db->prepare("UPDATE payments SET provider=?,status='PENDING',paid_at=NULL WHERE id=?")->execute([$provider,$order['payment_id']]);$db->prepare('UPDATE rental_orders SET order_status=? WHERE id=?')->execute([$status,$id]);$db->commit();Flash::set('success',$provider==='CASH'?'Đã chuyển sang thanh toán tiền mặt tại điểm nhận.':'Đã chuyển sang thanh toán chuyển khoản MB.');}catch(\Throwable $e){if($db->inTransaction())$db->rollBack();Flash::set('error',$e instanceof \RuntimeException?$e->getMessage():'Không thể đổi phương thức thanh toán.');}$this->redirect('/order?id='.$id);} public function orders():void{$u=$this->requireAuth();$s=Database::connection()->prepare("SELECT o.*,l.name pickup_name FROM rental_orders o JOIN locations l ON l.id=o.pickup_location_id WHERE o.user_id=? ORDER BY o.created_at DESC");$s->execute([$u['id']]);$this->view('account/orders',['title'=>'Đơn thuê của Tài','orders'=>$s->fetchAll()]);}
 public function show():void
 {
  $u=$this->requireAuth();$id=(int)($_GET['id']??0);$db=Database::connection();
  if(in_array($u['role_code'],['ADMIN','STAFF'],true)){
   $s=$db->prepare("SELECT o.*,p.id product_id,p.name product,p.image_url,ri.quantity,ri.rental_days,ri.line_total,pl.name pickup_name,rl.name return_name FROM rental_orders o JOIN locations pl ON pl.id=o.pickup_location_id JOIN locations rl ON rl.id=o.planned_return_location_id JOIN rental_items ri ON ri.order_id=o.id JOIN products p ON p.id=ri.product_id WHERE o.id=? AND (?='ADMIN' OR EXISTS(SELECT 1 FROM staff_locations sl WHERE sl.user_id=? AND sl.location_id IN (o.pickup_location_id,o.planned_return_location_id)))");$s->execute([$id,$u['role_code'],$u['id']]);$canManage=true;
  }else{
   $s=$db->prepare("SELECT o.*,p.id product_id,p.name product,p.image_url,ri.quantity,ri.rental_days,ri.line_total,pl.name pickup_name,rl.name return_name FROM rental_orders o JOIN locations pl ON pl.id=o.pickup_location_id JOIN locations rl ON rl.id=o.planned_return_location_id JOIN rental_items ri ON ri.order_id=o.id JOIN products p ON p.id=ri.product_id WHERE o.id=? AND o.user_id=?");$s->execute([$id,$u['id']]);$canManage=false;
  }
  $items=$s->fetchAll();if(!$items){http_response_code(404);$this->view('errors/404',['title'=>'Không tìm thấy đơn']);return;}
  $q=$db->prepare('SELECT ri.product_id,c.serial_code,c.qr_token,ric.item_status FROM rental_items ri JOIN rental_item_containers ric ON ric.rental_item_id=ri.id JOIN containers c ON c.id=ric.container_id WHERE ri.order_id=?');$q->execute([$id]);
  $pay=$db->prepare("SELECT * FROM payments WHERE order_id=? AND payment_type='RENTAL' ORDER BY id DESC LIMIT 1");$pay->execute([$id]);$payment=$pay->fetch();
  $refundQuery=$db->prepare("SELECT * FROM deposit_transactions WHERE order_id=? AND transaction_type='REFUND' ORDER BY id DESC LIMIT 1");$refundQuery->execute([$id]);$depositRefund=$refundQuery->fetch();
  $eventsQuery=$db->prepare("SELECT e.*,u.name actor_name,l.name location_name,c.serial_code FROM rental_order_events e LEFT JOIN users u ON u.id=e.actor_user_id LEFT JOIN locations l ON l.id=e.location_id LEFT JOIN containers c ON c.id=e.container_id WHERE e.order_id=? ORDER BY e.created_at DESC,e.id DESC");$eventsQuery->execute([$id]);
  $this->view('account/order',['title'=>'Chi tiết đơn','items'=>$items,'boxes'=>$q->fetchAll(),'payment'=>$payment,'depositRefund'=>$depositRefund,'events'=>$eventsQuery->fetchAll(),'canManage'=>$canManage]);
 } public function extend():void{$u=$this->requireAuth();verify_csrf();$id=(int)($_POST['order_id']??0);$days=max(1,min(7,(int)($_POST['days']??1)));$db=Database::connection();$s=$db->prepare("SELECT o.*,SUM(ri.quantity*p.late_fee_per_day) daily FROM rental_orders o JOIN rental_items ri ON ri.order_id=o.id JOIN products p ON p.id=ri.product_id WHERE o.id=? AND o.user_id=? GROUP BY o.id");$s->execute([$id,$u['id']]);$o=$s->fetch();if(!$o||!in_array($o['order_status'],['RENTED','PARTIALLY_RETURNED'],true)||strtotime($o['due_at'])<time()){Flash::set('error','Đơn chưa đủ điều kiện gia hạn.');$this->redirect('/orders');}$new=(new \DateTimeImmutable($o['due_at']))->modify("+$days days");$fee=(float)$o['daily']*$days;$db->prepare("INSERT INTO rental_extensions(order_id,requested_due_at,extra_fee,status,processed_by) VALUES(?,?,?,'APPROVED',?)")->execute([$id,$new->format('Y-m-d H:i:s'),$fee,$u['id']]);$db->prepare('UPDATE rental_orders SET due_at=?,fee_total=fee_total+? WHERE id=?')->execute([$new->format('Y-m-d H:i:s'),$fee,$id]);$db->prepare("INSERT INTO payments(order_id,payment_type,provider,amount,status,paid_at) VALUES(?,'EXTENSION','DEMO',?,'PAID',NOW())")->execute([$id,$fee]);Flash::set('success','Đã gia hạn. Phí demo: '.money($fee));$this->redirect('/orders');}
}

