<?php
namespace App\Models;
use App\Core\Database;
use PDO;
final class Product {
 public static function featured(int $limit=4):array{$limit=max(1,min(100,$limit));return Database::connection()->query("SELECT p.*,c.name category,COUNT(ct.id) available FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN containers ct ON ct.product_id=p.id AND ct.lifecycle_status='AVAILABLE' WHERE p.status='ACTIVE' AND c.status=1 GROUP BY p.id ORDER BY available DESC LIMIT ".$limit)->fetchAll(PDO::FETCH_ASSOC);}
 public static function allActive():array{return Database::connection()->query("SELECT p.*,c.name category,COUNT(ct.id) available FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN containers ct ON ct.product_id=p.id AND ct.lifecycle_status='AVAILABLE' WHERE p.status='ACTIVE' AND c.status=1 GROUP BY p.id ORDER BY available DESC")->fetchAll(PDO::FETCH_ASSOC);}
 public static function findAvailable(int $id):?array{$s=Database::connection()->prepare("SELECT p.*,c.name category,COUNT(ct.id) available FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN containers ct ON ct.product_id=p.id AND ct.lifecycle_status='AVAILABLE' WHERE p.id=? AND p.status='ACTIVE' AND c.status=1 GROUP BY p.id");$s->execute([$id]);return $s->fetch()?:null;}
}
