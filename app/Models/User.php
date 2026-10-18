<?php
namespace App\Models;
use App\Core\Database;
final class User {
 public static function findActiveByEmail(string $email):?array{$s=Database::connection()->prepare("SELECT u.*,r.code role_code FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND u.status='ACTIVE'");$s->execute([$email]);return $s->fetch()?:null;}
}