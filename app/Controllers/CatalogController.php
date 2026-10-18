<?php
namespace App\Controllers;
use App\Core\{Controller,Database};
use App\Models\Product;
class CatalogController extends Controller {
 public function index():void{$db=Database::connection();$q=trim($_GET['q']??'');$cat=(int)($_GET['category']??0);$sql="SELECT p.*,c.name category,COUNT(ct.id) available FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN containers ct ON ct.product_id=p.id AND ct.lifecycle_status='AVAILABLE' WHERE p.status='ACTIVE' AND c.status=1";$args=[];if($q!==''){$sql.=" AND (p.name LIKE ? OR p.sku LIKE ? OR p.material LIKE ?)";array_push($args,"%$q%","%$q%","%$q%");}if($cat){$sql.=" AND p.category_id=?";$args[]=$cat;}$sql.=" GROUP BY p.id ORDER BY ".(($_GET['sort']??'')==='price'?'p.rental_price ASC':'available DESC,p.id DESC');$s=$db->prepare($sql);$s->execute($args);$this->view('catalog/index',['title'=>'Chọn hộp tái sử dụng','products'=>$s->fetchAll(),'categories'=>$db->query('SELECT * FROM categories WHERE status=1')->fetchAll(),'q'=>$q,'categoryId'=>$cat]);}
 public function show():void{$p=Product::findAvailable((int)($_GET['id']??0));if(!$p){http_response_code(404);$this->view('errors/404',['title'=>'Không tìm thấy hộp']);return;}$this->view('catalog/show',['title'=>$p['name'],'product'=>$p]);}
}
