<?php
namespace App\Controllers;

use App\Core\{Auth, Controller, Database, Flash};
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Đăng nhập ReBox']);
    }

    public function showRegister(): void
    {
        $this->view('auth/register', ['title' => 'Tạo tài khoản']);
    }

    public function login(): void
    {
        verify_csrf();
        $user = User::findActiveByEmail(mb_strtolower(trim($_POST['email'] ?? '')));
        if (!$user || !password_verify($_POST['password'] ?? '', $user['password_hash'])) {
            Flash::set('error', 'Email hoặc mật khẩu chưa chính xác.');
            $this->redirect('/login');
        }
        Auth::login($user);
        $this->redirect(match ($user['role_code']) {
            'ADMIN' => '/admin',
            'STAFF' => '/staff',
            'PARTNER' => '/partner',
            default => '/account',
        });
    }

    public function register(): void
    {
        verify_csrf();
        $name = trim($_POST['name'] ?? '');
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 10 || ($phone !== '' && !preg_match('/^[0-9+() .-]{8,24}$/', $phone))) {
            Flash::set('error', 'Vui lòng kiểm tra thông tin. Mật khẩu cần ít nhất 10 ký tự.');
            $this->redirect('/register');
        }
        try {
            $db = Database::connection();
            $query = $db->prepare("INSERT INTO users(role_id,name,email,phone,password_hash)
                SELECT id,?,?,?,? FROM roles WHERE code='CUSTOMER'");
            $query->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
            Auth::login(['id' => $db->lastInsertId()]);
            Flash::set('success', 'Chào mừng bạn đến với ReBox.');
            $this->redirect('/account');
        } catch (\PDOException $error) {
            Flash::set('error', 'Email hoặc số điện thoại đã được sử dụng.');
            $this->redirect('/register');
        }
    }

    public function logout(): void
    {
        verify_csrf();
        Auth::logout();
        $this->redirect('/');
    }
}
