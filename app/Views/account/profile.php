<div class="page-wrap">
    <div class="eyebrow dark-eyebrow">TÀI KHOẢN REBOX</div><h1 class="page-title">Hồ sơ <em>của bạn.</em></h1>
    <div class="cart-layout">
        <section class="admin-card"><h2>Thông tin cá nhân</h2><form class="admin-form" method="post" action="<?=e(url('/account/profile'))?>">
            <?=csrf_field()?>
            <label>Họ và tên<input name="name" maxlength="120" required value="<?=e($profile['name']??'')?>"></label>
            <label>Email<input type="email" value="<?=e($profile['email']??'')?>" disabled></label>
            <label>Số điện thoại<input name="phone" maxlength="24" value="<?=e($profile['phone']??'')?>"></label>
            <p class="muted">Điểm ReBox: <strong><?=number_format((int)($profile['points']??0))?></strong></p>
            <button class="btn btn-dark">Lưu hồ sơ</button>
        </form></section>
        <section class="admin-card"><div class="admin-heading"><h2>Thông báo</h2><form method="post" action="<?=e(url('/account/notifications/read'))?>"><?=csrf_field()?><button class="link-btn">Đánh dấu đã đọc</button></form></div>
            <?php foreach($notifications as $notification): ?><article class="notification-item <?=empty($notification['read_at'])?'unread':''?>"><b><?=e($notification['title'])?></b><p><?=e($notification['message'])?></p><small><?=date('d/m/Y H:i',strtotime($notification['created_at']))?></small></article><?php endforeach; ?>
            <?php if(!$notifications): ?><p class="muted">Bạn chưa có thông báo.</p><?php endif; ?>
        </section>
    </div>
    <section class="admin-card"><div class="admin-heading"><h2>Đơn thuê gần đây</h2><a class="text-link" href="<?=e(url('/orders'))?>">Tất cả đơn ↗</a></div><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Hạn trả</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody>
        <?php foreach($orders as $order): ?><tr><td><a href="<?=e(url('/order?id='.$order['id']))?>"><?=e($order['order_code'])?></a></td><td><?=date('d/m/Y H:i',strtotime($order['due_at']))?></td><td><?=money($order['grand_total'])?></td><td><?=e($order['order_status'])?></td></tr><?php endforeach; ?>
        <?php if(!$orders): ?><tr><td colspan="4">Bạn chưa có đơn thuê.</td></tr><?php endif; ?>
    </tbody></table></div></section>
</div>
