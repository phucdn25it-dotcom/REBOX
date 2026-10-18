<?php
use App\Core\{Auth, Flash};
$user = Auth::user();
$flash = Flash::pull();
$file = dirname(__DIR__) . '/' . $name . '.php';
if ($name === 'home') {
    ?><!doctype html>
    <html lang="vi"><head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#17221c">
        <title><?=e($title ?? 'ReBox · Dùng lại thật nhẹ nhàng')?></title>
        <style>html,body{margin:0;width:100%;height:100%;overflow:hidden;background:#17221c}.kage-frame{position:fixed;inset:0;width:100%;height:100%;border:0}</style>
    </head><body>
    <?php require $file; ?>
    <?php if ($flash): ?><div style="position:fixed;z-index:30;top:20px;left:50%;transform:translateX(-50%);padding:12px 18px;border:1px solid #ffffff33;border-radius:14px;background:#111c;color:#fff;backdrop-filter:blur(18px)"><?=e($flash['message'])?></div><?php endif; ?>
    </body></html><?php
    return;
}
?><!doctype html>
<html lang="vi"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#090b0d">
    <title><?=e(($title ?? 'ReBox') . ' · ReBox')?></title>
    <link rel="stylesheet" href="<?=e(url('/landing-pages/secret-pathways-assets/fonts.css'))?>">
    <link rel="stylesheet" href="<?=e(url('/assets/app.css'))?>">
    <link rel="stylesheet" href="<?=e(url('/assets/rebox-theme.css'))?>">
</head><body>
<header class="site-header">
    <a class="brand" href="<?=e(url('/'))?>"><span class="brand-mark">&#8635;</span><span>rebox<span class="brand-dot">.</span><small>REUSE, REIMAGINED</small></span></a>
    <nav class="main-nav"><a href="<?=e(url('/'))?>">Trang chủ</a><a href="<?=e(url('/catalog'))?>">Thuê hộp</a><a href="<?=e(url('/#how'))?>">Cách hoạt động</a><a href="<?=e(url('/#locations'))?>">Điểm nhận / trả</a></nav>
    <div class="header-actions">
        <?php if(!$user || $user['role_code']==='CUSTOMER'): ?><a class="cart-link" href="<?=e(url('/cart'))?>">Giỏ thuê <span><?=array_sum($_SESSION['cart']??[])?></span></a><?php endif; ?>
        <?php if($user): ?>
            <?php $dashboard = match($user['role_code']) {'CUSTOMER'=>'/account','STAFF'=>'/staff','PARTNER'=>'/partner','ADMIN'=>'/admin',default=>'/'}; ?>
            <div class="user-menu"><a href="<?=e(url($dashboard))?>" class="user-pill"><span class="avatar"><?=e(mb_substr($user['name'],0,1))?></span><?=e(explode(' ',$user['name'])[0])?></a>
                <?php if($user['role_code']==='CUSTOMER'): ?><a class="text-link" href="<?=e(url('/orders'))?>">Đơn thuê</a><?php endif; ?>
                <form method="post" action="<?=e(url('/logout'))?>"><?=csrf_field()?><button class="link-btn">Đăng xuất</button></form>
            </div>
        <?php else: ?><a class="btn btn-dark btn-small" href="<?=e(url('/login'))?>">Đăng nhập ↗</a><a class="btn btn-small btn-register" href="<?=e(url('/register'))?>">Đăng ký</a><?php endif; ?>
    </div>
    <button class="mobile-toggle" aria-label="Mở menu" onclick="document.body.classList.toggle('menu-open')">☰</button>
</header>
<?php if($flash): ?><div class="flash <?=e($flash['kind'])?>" role="status"><?=e($flash['message'])?></div><?php endif; ?>
<main><?php require $file; ?></main>
<footer class="footer"><a class="brand" href="<?=e(url('/'))?>"><span class="brand-mark">&#8635;</span><span>rebox<span class="brand-dot">.</span><small>REUSE, REIMAGINED</small></span></a><p>Thói quen nhỏ. Tương lai nhẹ hơn.</p><span>© <?=date('Y')?> ReBox · Hộp tái sử dụng cho nhịp sống mới</span></footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script><script src="<?=e(url('/assets/app.js'))?>" defer></script>
</body></html>
