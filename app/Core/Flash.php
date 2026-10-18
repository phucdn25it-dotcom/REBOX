<?php
namespace App\Core;
final class Flash {public static function set(string $k,string $m):void{$_SESSION['_flash']=['kind'=>$k,'message'=>$m];}public static function pull():?array{$f=$_SESSION['_flash']??null;unset($_SESSION['_flash']);return $f;}}
