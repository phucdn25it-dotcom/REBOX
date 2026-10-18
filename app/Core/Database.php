<?php
namespace App\Core;
use PDO;
final class Database {private static ?PDO $pdo=null;public static function connection():PDO{if(!self::$pdo){$host=getenv('DB_HOST')?:'127.0.0.1';$db=getenv('DB_NAME')?:'rebox';$user=getenv('DB_USER')?:'root';$pass=getenv('DB_PASS')?:'';self::$pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}return self::$pdo;}}
