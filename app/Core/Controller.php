<?php
namespace App\Core;
class Controller {protected function view(string $n,array $d=[]):void{View::render($n,$d);}protected function redirect(string $p):never{header('Location: '.url($p));exit;}protected function requireAuth(?array $roles=null):array{$u=Auth::user();if(!$u){Flash::set('error','Vui lòng đăng nhập.');$this->redirect('/login');}if($roles&&!in_array($u['role_code'],$roles,true)){http_response_code(403);View::render('errors/403',['title'=>'Không đủ quyền']);exit;}return $u;}}
