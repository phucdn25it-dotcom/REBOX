<?php
namespace App\Controllers;

use App\Core\{Controller, Database, Flash};

class StaffController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth(['STAFF', 'ADMIN']);
        $db = Database::connection();
        $orders = $db->prepare("SELECT DISTINCT o.*, u.name customer, l.name pickup,
                (SELECT p.provider FROM payments p WHERE p.order_id=o.id AND p.payment_type='RENTAL' ORDER BY p.id DESC LIMIT 1) payment_provider,
                (SELECT p.status FROM payments p WHERE p.order_id=o.id AND p.payment_type='RENTAL' ORDER BY p.id DESC LIMIT 1) payment_status,
                (SELECT d.status FROM deposit_transactions d WHERE d.order_id=o.id AND d.transaction_type='REFUND' ORDER BY d.id DESC LIMIT 1) deposit_refund_status,
                (SELECT COUNT(*) FROM return_items x JOIN returns r ON r.id=x.return_id WHERE r.order_id=o.id AND x.condition_status IN ('DAMAGED','MISSING')) damaged_count,
                (SELECT d.status FROM deposit_transactions d WHERE d.order_id=o.id AND d.transaction_type='HOLD' ORDER BY d.id DESC LIMIT 1) deposit_hold_status
            FROM rental_orders o
            JOIN users u ON u.id = o.user_id
            JOIN locations l ON l.id = o.pickup_location_id
            WHERE (? = 'ADMIN' OR EXISTS(SELECT 1 FROM staff_locations sl WHERE sl.user_id=? AND sl.location_id IN (o.pickup_location_id,o.planned_return_location_id)))
              AND o.order_status IN ('PENDING_PAYMENT','CONFIRMED','READY_FOR_PICKUP','RENTED','PARTIALLY_RETURNED','RETURNED','COMPLETED','CANCELLED')
            ORDER BY o.due_at");
        $orders->execute([$user['role_code'], $user['id']]);

        if ($user['role_code'] === 'ADMIN') {
            $locations = $db->query("SELECT id,name FROM locations WHERE status='ACTIVE'")->fetchAll();
        } else {
            $locationsQuery = $db->prepare("SELECT l.id,l.name FROM locations l
                JOIN staff_locations sl ON sl.location_id=l.id
                WHERE sl.user_id=? AND l.status='ACTIVE'");
            $locationsQuery->execute([$user['id']]);
            $locations = $locationsQuery->fetchAll();
        }

        $locationIds = array_map(static fn(array $location): int => (int)$location['id'], $locations);
        $requestSql = "SELECT cr.*,o.order_code,u.name customer,l.name location_name FROM customer_return_requests cr JOIN rental_orders o ON o.id=cr.order_id JOIN users u ON u.id=cr.user_id JOIN locations l ON l.id=cr.location_id WHERE cr.status='PENDING'";
        $requestArgs = [];
        if ($user['role_code'] !== 'ADMIN' && $locationIds) {
            $requestSql .= ' AND cr.location_id IN (' . implode(',', array_fill(0, count($locationIds), '?')) . ')';
            $requestArgs = $locationIds;
        } elseif ($user['role_code'] !== 'ADMIN') { $requestSql .= ' AND 1=0'; }
        $requestSql .= ' ORDER BY cr.requested_at ASC';
        $returnRequestsQuery = $db->prepare($requestSql); $returnRequestsQuery->execute($requestArgs);
        $inventory = [];
        $products = $db->query("SELECT id,name,sku,capacity_ml FROM products WHERE status='ACTIVE' ORDER BY name")->fetchAll();
        if ($locationIds) {
            $marks = implode(',', array_fill(0, count($locationIds), '?'));
            $inventoryQuery = $db->prepare("SELECT c.serial_code,c.lifecycle_status,c.usage_count,p.name product,l.name location
                FROM containers c JOIN products p ON p.id=c.product_id LEFT JOIN locations l ON l.id=c.location_id
                WHERE c.location_id IN ($marks) ORDER BY c.id DESC LIMIT 100");
            $inventoryQuery->execute($locationIds);
            $inventory = $inventoryQuery->fetchAll();
        }

        $this->view('staff/index', [
            'title' => 'Bàn làm việc nhân viên',
            'orders' => $orders->fetchAll(),
            'locations' => $locations,
            'products' => $products,
            'inventory' => $inventory,
            'returnRequests' => $returnRequestsQuery->fetchAll(),
        ]);
    }

    public function inventory(): void
    {
        $user = $this->requireAuth(['STAFF', 'ADMIN']);
        verify_csrf();
        $db = Database::connection();
        $action = $_POST['inventory_action'] ?? '';

        try {
            if ($action === 'intake') {
                $productId = (int)($_POST['product_id'] ?? 0);
                $locationId = (int)($_POST['location_id'] ?? 0);
                $quantity = (int)($_POST['quantity'] ?? 0);
                $prefix = strtoupper(trim($_POST['serial_prefix'] ?? ''));
                if ($quantity < 1 || $quantity > 100) {
                    throw new \RuntimeException('Số lượng nhập phải từ 1 đến 100 hộp.');
                }
                if ($prefix !== '' && !preg_match('/^[A-Z0-9_-]{2,48}$/', $prefix)) {
                    throw new \RuntimeException('Tiền tố serial chỉ gồm chữ, số, dấu gạch ngang hoặc gạch dưới.');
                }
                if (!$this->canUseLocation($db, $user, $locationId)) {
                    throw new \RuntimeException('Bạn chưa được phân công tại điểm nhận hàng này.');
                }
                $productQuery = $db->prepare("SELECT id FROM products WHERE id=? AND status='ACTIVE'");
                $productQuery->execute([$productId]);
                if (!$productQuery->fetchColumn()) {
                    throw new \RuntimeException('Loại hộp không còn hoạt động.');
                }

                $db->beginTransaction();
                $firstId = null;
                for ($i = 1; $i <= $quantity; $i++) {
                    $base = $prefix !== '' ? $prefix : 'RB-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                    $serial = $quantity > 1 ? $base . '-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT) : $base;
                    $insert = $db->prepare("INSERT INTO containers(product_id,location_id,serial_code,qr_token,lifecycle_status)
                        VALUES(?,?,?,?, 'AVAILABLE')");
                    $insert->execute([$productId, $locationId, $serial, bin2hex(random_bytes(32))]);
                    $containerId = (int)$db->lastInsertId();
                    $firstId ??= $containerId;
                    $db->prepare('INSERT INTO inventory_movements(container_id,from_location_id,to_location_id,actor_user_id,reason) VALUES(?,NULL,?,?,?)')
                        ->execute([$containerId, $locationId, $user['id'], 'Nhập lô hộp mới']);
                }
                $after = json_encode(['product_id' => $productId, 'location_id' => $locationId, 'quantity' => $quantity], JSON_UNESCAPED_UNICODE);
                $db->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,after_data,ip_address) VALUES(?,'STAFF_INTAKE_CONTAINERS','containers',?,?,?)")
                    ->execute([$user['id'], $firstId, $after, $_SERVER['REMOTE_ADDR'] ?? null]);
                $db->commit();
                Flash::set('success', "Đã nhập $quantity hộp vào kho và tạo QR riêng cho từng hộp.");
            } elseif ($action === 'dispatch') {
                $serial = strtoupper(trim($_POST['serial_code'] ?? ''));
                $destinationId = (int)($_POST['destination_location_id'] ?? 0);
                $dispatchType = $_POST['dispatch_type'] ?? 'transfer';
                if ($serial === '' || !in_array($dispatchType, ['transfer', 'retire'], true)) {
                    throw new \RuntimeException('Vui lòng nhập serial và chọn cách xuất hộp.');
                }
                $db->beginTransaction();
                $containerQuery = $db->prepare('SELECT * FROM containers WHERE serial_code=? FOR UPDATE');
                $containerQuery->execute([$serial]);
                $container = $containerQuery->fetch();
                if (!$container || $container['lifecycle_status'] !== 'AVAILABLE' || !$container['location_id']) {
                    throw new \RuntimeException('Không tìm thấy hộp sẵn sàng tại kho để xuất.');
                }
                $sourceId = (int)$container['location_id'];
                if (!$this->canUseLocation($db, $user, $sourceId)) {
                    throw new \RuntimeException('Bạn chưa được phân công tại kho đang giữ hộp này.');
                }
                if ($dispatchType === 'transfer') {
                    if ($destinationId === $sourceId || !$this->canUseLocation($db, $user, $destinationId)) {
                        throw new \RuntimeException('Hãy chọn một điểm đến khác mà bạn được phân công.');
                    }
                    $db->prepare('UPDATE containers SET location_id=? WHERE id=?')->execute([$destinationId, $container['id']]);
                    $toLocation = $destinationId;
                    $reason = 'Xuất kho và chuyển hộp đến điểm nhận khác';
                    $message = 'Đã chuyển hộp ' . $serial . ' sang điểm mới.';
                } else {
                    $db->prepare("UPDATE containers SET location_id=NULL,lifecycle_status='RETIRED' WHERE id=?")->execute([$container['id']]);
                    $toLocation = null;
                    $reason = 'Xuất khỏi vòng sử dụng ReBox';
                    $message = 'Đã xuất hộp ' . $serial . ' khỏi vòng sử dụng.';
                }
                $db->prepare('INSERT INTO inventory_movements(container_id,from_location_id,to_location_id,actor_user_id,reason) VALUES(?,?,?,?,?)')
                    ->execute([$container['id'], $sourceId, $toLocation, $user['id'], $reason]);
                $after = json_encode(['from_location_id' => $sourceId, 'to_location_id' => $toLocation, 'dispatch_type' => $dispatchType], JSON_UNESCAPED_UNICODE);
                $db->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,after_data,ip_address) VALUES(?,'STAFF_DISPATCH_CONTAINER','containers',?,?,?)")
                    ->execute([$user['id'], $container['id'], $after, $_SERVER['REMOTE_ADDR'] ?? null]);
                $db->commit();
                Flash::set('success', $message);
            } else {
                throw new \RuntimeException('Thao tác nhập/xuất kho không hợp lệ.');
            }
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Flash::set('error', $error instanceof \RuntimeException ? $error->getMessage() : 'Không thể hoàn tất thao tác kho. Hãy kiểm tra serial và dữ liệu.');
        }
        $this->redirect('/staff');
    }

    private function canUseLocation(\PDO $db, array $user, int $locationId): bool
    {
        if ($locationId < 1) return false;
        if ($user['role_code'] === 'ADMIN') {
            $query = $db->prepare("SELECT 1 FROM locations WHERE id=? AND status='ACTIVE'");
            $query->execute([$locationId]);
        } else {
            $query = $db->prepare("SELECT 1 FROM staff_locations sl JOIN locations l ON l.id=sl.location_id
                WHERE sl.user_id=? AND sl.location_id=? AND l.status='ACTIVE'");
            $query->execute([$user['id'], $locationId]);
        }
        return (bool)$query->fetchColumn();
    }

    public function action(): void
    {
        $user = $this->requireAuth(['STAFF', 'ADMIN']);
        verify_csrf();
        $db = Database::connection();
        $token = trim($_POST['qr_token'] ?? '');
        $action = $_POST['action'] ?? '';
        $db->beginTransaction();

        try {
            $query = $db->prepare("SELECT c.*, ric.item_status, ri.order_id, ric.id link_id,
                o.order_code, o.pickup_location_id
                FROM containers c
                JOIN rental_item_containers ric ON ric.container_id=c.id
                JOIN rental_items ri ON ri.id=ric.rental_item_id
                JOIN rental_orders o ON o.id=ri.order_id
                WHERE c.qr_token=? FOR UPDATE");
            $query->execute([$token]);
            $box = $query->fetch();
            if (!$box) {
                throw new \RuntimeException('QR không hợp lệ hoặc hộp không thuộc đơn thuê.');
            }

            $location = (int)($box['location_id'] ?? $box['pickup_location_id']);
            if ($action === 'return') {
                $location = (int)($_POST['location_id'] ?? 0);
            }
            if ($user['role_code'] !== 'ADMIN') {
                $assigned = $db->prepare('SELECT 1 FROM staff_locations WHERE user_id=? AND location_id=?');
                $assigned->execute([$user['id'], $location]);
                if (!$assigned->fetchColumn()) {
                    throw new \RuntimeException('Bạn chưa được phân công tại điểm thao tác này.');
                }
            }

            if ($action === 'return') {
                $approvedRequest = $db->prepare("SELECT location_id FROM customer_return_requests WHERE order_id=? AND status='APPROVED' ORDER BY id DESC LIMIT 1");
                $approvedRequest->execute([$box['order_id']]);
                $requestedLocation = $approvedRequest->fetchColumn();
                if ($requestedLocation && (int)$requestedLocation !== $location) {
                    throw new \RuntimeException('Yêu cầu trả đã được duyệt tại điểm khác. Hãy chọn đúng điểm trả khách đã đăng ký.');
                }
            }

            if ($action === 'checkout' && $box['lifecycle_status'] === 'RESERVED') {
                $paymentQuery = $db->prepare("SELECT * FROM payments WHERE order_id=? AND payment_type='RENTAL' ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $paymentQuery->execute([$box['order_id']]);
                $payment = $paymentQuery->fetch();
                if (!$payment || ($payment['provider'] !== 'CASH' && $payment['status'] !== 'PAID')) {
                    throw new \RuntimeException('Chưa thể bàn giao: cần xác nhận thanh toán trước.');
                }
                if ($payment['provider'] === 'CASH' && $payment['status'] === 'PENDING') {
                    $db->prepare("UPDATE payments SET status='PAID',paid_at=NOW() WHERE id=?")->execute([$payment['id']]);
                    $db->prepare("UPDATE deposit_transactions SET status='COMPLETED',processed_by=? WHERE order_id=? AND transaction_type='HOLD' AND status='PENDING'")->execute([$user['id'], $box['order_id']]);
                    $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,note) VALUES(?,?,'PAYMENT_CONFIRMED',?,'Đã nhận tiền mặt tại điểm nhận')")
                        ->execute([$box['order_id'], $user['id'], $location]);
                }
                $db->prepare("UPDATE containers SET lifecycle_status='RENTED',usage_count=usage_count+1 WHERE id=?")
                    ->execute([$box['id']]);
                $db->prepare("UPDATE rental_item_containers SET item_status='OUT',checkout_at=NOW(),checkout_condition=? WHERE id=?")
                    ->execute([trim($_POST['condition'] ?? 'OK'), $box['link_id']]);
                $db->prepare("UPDATE rental_orders SET order_status='RENTED' WHERE id=?")
                    ->execute([$box['order_id']]);
                $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,container_id,condition_status,note) VALUES(?,?,'HANDOVER',?,?,?,'Bàn giao hộp cho khách')")
                    ->execute([$box['order_id'], $user['id'], $location, $box['id'], trim($_POST['condition'] ?? 'OK')]);
            } elseif ($action === 'return' && $box['lifecycle_status'] === 'RENTED') {
                $condition = $_POST['condition'] ?? 'DIRTY';
                if (!in_array($condition, ['CLEAN', 'DIRTY', 'DAMAGED', 'MISSING'], true)) {
                    throw new \RuntimeException('Tình trạng hộp không hợp lệ.');
                }
                $lifecycle = match ($condition) {
                    'CLEAN' => 'AVAILABLE',
                    'DAMAGED' => 'MAINTENANCE',
                    'MISSING' => 'LOST',
                    default => 'RETURNED_DIRTY',
                };
                $db->prepare('UPDATE containers SET lifecycle_status=?,location_id=? WHERE id=?')
                    ->execute([$lifecycle, $location, $box['id']]);
                $db->prepare("UPDATE rental_item_containers SET item_status=?,checkin_at=NOW(),return_condition=? WHERE id=?")
                    ->execute([$condition === 'MISSING' ? 'MISSING' : 'RETURNED', $condition, $box['link_id']]);
                $db->prepare('INSERT INTO returns(order_id,location_id,staff_id,return_code,returned_at,notes) VALUES(?,?,?,?,NOW(),?)')
                    ->execute([$box['order_id'], $location, $user['id'], 'RT' . bin2hex(random_bytes(4)), trim($_POST['note'] ?? $condition)]);
                $returnId = (int)$db->lastInsertId();
                $db->prepare('INSERT INTO return_items(return_id,container_id,condition_status,note) VALUES(?,?,?,?)')
                    ->execute([$returnId, $box['id'], $condition, trim($_POST['note'] ?? '')]);
                $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,container_id,condition_status,note) VALUES(?,?,'RETURN_RECEIVED',?,?,?,?)")
                    ->execute([$box['order_id'], $user['id'], $location, $box['id'], $condition, trim($_POST['note'] ?? '')]);
                $remaining = $db->prepare("SELECT COUNT(*) FROM rental_item_containers ric
                    JOIN rental_items ri ON ri.id=ric.rental_item_id
                    WHERE ri.order_id=? AND ric.item_status IN ('RESERVED','OUT')");
                $remaining->execute([$box['order_id']]);
                if (!(int)$remaining->fetchColumn()) {
                    $db->prepare("UPDATE rental_orders SET order_status='RETURNED',returned_at=NOW() WHERE id=?")
                        ->execute([$box['order_id']]);
                    $db->prepare("UPDATE customer_return_requests SET status='COMPLETED',processed_at=NOW(),processed_by=? WHERE order_id=? AND status='APPROVED'")->execute([$user['id'],$box['order_id']]);
                } else {
                    $db->prepare("UPDATE rental_orders SET order_status='PARTIALLY_RETURNED' WHERE id=?")
                        ->execute([$box['order_id']]);
                }
            } elseif ($action === 'clean' && in_array($box['lifecycle_status'], ['RETURNED_DIRTY', 'CLEANING'], true)) {
                $db->prepare("UPDATE containers SET lifecycle_status='AVAILABLE',last_inspected_at=NOW() WHERE id=?")
                    ->execute([$box['id']]);
                $db->prepare("INSERT INTO maintenance_logs(container_id,staff_id,maintenance_type,result_status,description,started_at,completed_at)
                    VALUES(?,?,'CLEANING','PASSED',?,NOW(),NOW())")
                    ->execute([$box['id'], $user['id'], trim($_POST['note'] ?? 'Đã vệ sinh và kiểm tra')]);
                $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,container_id,note) VALUES(?,?,'CLEANED',?,?,?)")
                    ->execute([$box['order_id'], $user['id'], $location, $box['id'], trim($_POST['note'] ?? 'Đã vệ sinh và kiểm tra')]);
            } else {
                throw new \RuntimeException('Trạng thái hộp không cho phép thao tác này.');
            }

            $db->prepare('INSERT INTO inventory_movements(container_id,from_location_id,to_location_id,actor_user_id,reason) VALUES(?,?,?,?,?)')
                ->execute([$box['id'], $box['location_id'], $location, $user['id'], 'Cập nhật vòng đời ' . $box['serial_code']]);
            $db->prepare('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,?,?,?,?)')
                ->execute([$user['id'], strtoupper($action) . '_CONTAINER', 'containers', $box['id'], $_SERVER['REMOTE_ADDR'] ?? null]);
            $db->commit();
            Flash::set('success', 'Đã ghi nhận thao tác cho ' . $box['serial_code'] . '.');
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Flash::set('error', $error->getMessage());
        }

        $this->redirect('/staff');
    }

    public function processReturnRequest(): void
    {
        $user = $this->requireAuth(['STAFF', 'ADMIN']); verify_csrf();
        $id=(int)($_POST['request_id']??0);$status=$_POST['status']??'';$note=trim($_POST['staff_note']??'');$db=Database::connection();
        try {
            if(!in_array($status,['APPROVED','DECLINED'],true))throw new \RuntimeException('Trạng thái yêu cầu không hợp lệ.');
            $db->beginTransaction();
            $sql='SELECT * FROM customer_return_requests WHERE id=? AND status=\'PENDING\' FOR UPDATE';$q=$db->prepare($sql);$q->execute([$id]);$request=$q->fetch();
            if(!$request)throw new \RuntimeException('Yêu cầu đã được xử lý hoặc không tồn tại.');
            if($user['role_code']!=='ADMIN'&&!$this->canUseLocation($db,$user,(int)$request['location_id']))throw new \RuntimeException('Bạn chưa được phân công tại điểm trả này.');
            $db->prepare('UPDATE customer_return_requests SET status=?,staff_note=?,processed_at=NOW(),processed_by=? WHERE id=?')->execute([$status,mb_substr($note,0,500),$user['id'],$id]);
            $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,note) VALUES(?,?,?, ?,?)")->execute([$request['order_id'],$user['id'],$status==='APPROVED'?'RETURN_APPROVED':'RETURN_DECLINED',$request['location_id'],$note?:($status==='APPROVED'?'Đã xác nhận điểm nhận trả':'Yêu cầu trả hộp chưa được chấp nhận')]);
            $db->commit();Flash::set('success',$status==='APPROVED'?'Đã xác nhận yêu cầu. Khách mang hộp và mã QR đến điểm đã chọn.':'Đã từ chối yêu cầu trả hộp.');
        } catch (\Throwable $error) { if($db->inTransaction())$db->rollBack();Flash::set('error',$error instanceof \RuntimeException?$error->getMessage():'Không thể cập nhật yêu cầu trả hộp.'); }
        $this->redirect('/staff');
    }

    public function verifyPayment(): void
    {
        $user = $this->requireAuth(['STAFF', 'ADMIN']);
        verify_csrf();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $query = $db->prepare("SELECT o.id,o.order_code,o.user_id,o.order_status,o.pickup_location_id,p.id payment_id,p.status payment_status,p.provider
                FROM rental_orders o JOIN payments p ON p.order_id=o.id AND p.payment_type='RENTAL'
                WHERE o.id=? ORDER BY p.id DESC LIMIT 1 FOR UPDATE");
            $query->execute([$orderId]);
            $order = $query->fetch();
            if (!$order || $order['provider'] !== 'BANK_TRANSFER' || $order['payment_status'] !== 'PENDING' || $order['order_status'] !== 'PENDING_PAYMENT') {
                throw new \RuntimeException('Đơn không có khoản chuyển khoản đang chờ đối soát.');
            }
            if ($user['role_code'] !== 'ADMIN') {
                $assigned = $db->prepare('SELECT 1 FROM staff_locations WHERE user_id=? AND location_id=?');
                $assigned->execute([$user['id'], $order['pickup_location_id']]);
                if (!$assigned->fetchColumn()) throw new \RuntimeException('Bạn chưa được phân công tại điểm nhận của đơn này.');
            }
            $db->prepare("UPDATE payments SET status='PAID',paid_at=NOW() WHERE id=?")->execute([$order['payment_id']]);
            $db->prepare("UPDATE deposit_transactions SET status='COMPLETED',processed_by=? WHERE order_id=? AND transaction_type='HOLD' AND status='PENDING'")->execute([$user['id'], $orderId]);
            $db->prepare("UPDATE rental_orders SET order_status='CONFIRMED' WHERE id=?")->execute([$orderId]);
            $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,note) VALUES(?,?,'PAYMENT_CONFIRMED',?,'Đã đối soát chuyển khoản ngân hàng')")
                ->execute([$orderId, $user['id'], $order['pickup_location_id']]);
            $db->prepare('INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)')->execute([$order['user_id'],'Đã xác nhận thanh toán','Khoản chuyển khoản của đơn '.$order['order_code'].' đã được đối soát. Đơn thuê đã xác nhận.']);
            $db->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,'VERIFY_BANK_PAYMENT','payments',?,?)")->execute([$user['id'],$order['payment_id'],$_SERVER['REMOTE_ADDR']??null]);
            $db->commit();
            Flash::set('success', 'Đã xác nhận chuyển khoản; đơn thuê đã được xác nhận.');
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Flash::set('error', $error instanceof \RuntimeException ? $error->getMessage() : 'Không thể xác nhận thanh toán lúc này.');
        }
        $this->redirect('/staff');
    }

    public function refundDeposit(): void
    {
        $user = $this->requireAuth(['STAFF', 'ADMIN']);
        verify_csrf();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $query = $db->prepare("SELECT o.id,o.order_code,o.user_id,o.order_status,o.pickup_location_id,o.deposit_total,
                    (SELECT status FROM deposit_transactions WHERE order_id=o.id AND transaction_type='HOLD' ORDER BY id DESC LIMIT 1) hold_status,
                    (SELECT id FROM payments WHERE order_id=o.id AND payment_type='RENTAL' ORDER BY id DESC LIMIT 1) payment_id,
                    (SELECT COUNT(*) FROM return_items x JOIN returns r ON r.id=x.return_id WHERE r.order_id=o.id AND x.condition_status IN ('DAMAGED','MISSING')) damaged_count,
                    (SELECT COUNT(*) FROM deposit_transactions WHERE order_id=o.id AND transaction_type='REFUND' AND status='COMPLETED') refund_count
                FROM rental_orders o WHERE o.id=? FOR UPDATE");
            $query->execute([$orderId]);
            $order = $query->fetch();
            if (!$order || $order['order_status'] !== 'RETURNED' || $order['hold_status'] !== 'COMPLETED' || (float)$order['deposit_total'] <= 0 || (int)$order['refund_count'] > 0 || (int)$order['damaged_count'] > 0) {
                throw new \RuntimeException('Đơn chưa đủ điều kiện hoàn cọc hoặc cần đối soát hộp hỏng/thất lạc.');
            }
            if ($user['role_code'] !== 'ADMIN') {
                $assigned = $db->prepare('SELECT 1 FROM staff_locations WHERE user_id=? AND location_id=?');
                $assigned->execute([$user['id'], $order['pickup_location_id']]);
                if (!$assigned->fetchColumn()) throw new \RuntimeException('Bạn chưa được phân công tại điểm nhận của đơn này.');
            }
            $db->prepare("INSERT INTO deposit_transactions(order_id,transaction_type,amount,status,reference_payment_id,reason,processed_by) VALUES(?,'REFUND',?,'COMPLETED',?,'Đã hoàn cọc thủ công sau khi nhận đủ hộp',?)")
                ->execute([$orderId, $order['deposit_total'], $order['payment_id'], $user['id']]);
            $db->prepare('INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)')->execute([$order['user_id'],'Đã hoàn tiền cọc','Khoản cọc của đơn '.$order['order_code'].' đã được hoàn sau khi nhận đủ hộp.']);
            $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,note) VALUES(?,?,'DEPOSIT_REFUNDED',?,'Đã hoàn tiền cọc cho khách')")
                ->execute([$orderId, $user['id'], $order['pickup_location_id']]);
            $db->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,'REFUND_DEPOSIT','rental_orders',?,?)")->execute([$user['id'],$orderId,$_SERVER['REMOTE_ADDR']??null]);
            $db->commit();
            Flash::set('success', 'Đã ghi nhận hoàn tiền cọc cho đơn '.$order['order_code'].'.');
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Flash::set('error', $error instanceof \RuntimeException ? $error->getMessage() : 'Không thể ghi nhận hoàn cọc lúc này.');
        }
        $this->redirect('/staff');
    }
}
