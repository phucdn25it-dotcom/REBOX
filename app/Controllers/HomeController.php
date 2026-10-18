<?php
namespace App\Controllers;
use App\Core\{Controller,Database,Auth};
use App\Models\Product;
class HomeController extends Controller {
 public function index():void{$db=Database::connection();$products=Product::allActive();$locations=$db->query("SELECT * FROM locations WHERE status='ACTIVE' LIMIT 3")->fetchAll();$stats=$db->query("SELECT COUNT(*) boxes,SUM(lifecycle_status='RENTED') rented FROM containers")->fetch();$this->view('home',['title'=>'Tái sử dụng dễ dàng mỗi ngày','products'=>$products,'locations'=>$locations,'stats'=>$stats,'user'=>Auth::user()]);}
}
