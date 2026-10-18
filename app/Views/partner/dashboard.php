<div class="page-wrap">
    <div class="admin-heading"><div><div class="eyebrow dark-eyebrow">PARTNER PORTAL</div><h1 class="page-title">Hoạt động tại <em>điểm của bạn.</em></h1></div><a class="btn btn-dark" href="<?=e(url('/catalog'))?>">Xem cửa hàng</a></div>
    <div class="admin-nav"><a class="active" href="<?=e(url('/partner'))?>">Tổng quan</a></div>
    <h2 class="subhead">Địa điểm được phân công</h2>
    <?php if(!$locations): ?><div class="empty-state"><h2>Tài khoản chưa được gán địa điểm.</h2><p>Quản trị viên cần phân công địa điểm trước khi bạn xem hoạt động.</p></div><?php else: ?>
        <div class="location-grid admin-locations">
            <?php foreach($locations as $location): ?>
                <article class="location-card"><span class="location-number"><?=e(str_pad((string)$location['id'],2,'0',STR_PAD_LEFT))?></span><div><h3><?=e($location['name'])?></h3><p><?=e($location['address'])?></p><small><?=e($location['opening_hours']??'')?> · <?= (int)$location['container_total'] ?> hộp</small><p class="muted">Sẵn sàng: <?= (int)$location['available'] ?> · Đang thuê: <?= (int)$location['rented'] ?> · Chờ vệ sinh: <?= (int)$location['cleaning'] ?></p></div></article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <section class="admin-card"><h2>Đơn thuê tại điểm được giao</h2><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Điểm nhận</th><th>Hạn trả</th><th>Trạng thái</th></tr></thead><tbody>
        <?php foreach($orders as $order): ?><tr><td><?=e($order['order_code'])?></td><td><?=e($order['customer'])?></td><td><?=e($order['pickup'])?></td><td><?=date('d/m/Y H:i',strtotime($order['due_at']))?></td><td><span class="status-pill"><?=e($order['order_status'])?></span></td></tr><?php endforeach; ?>
        <?php if(!$orders): ?><tr><td colspan="5">Chưa có đơn tại các địa điểm được giao.</td></tr><?php endif; ?>
    </tbody></table></div></section>
    <section class="admin-card"><h2>Lịch sử nhận trả hộp</h2><div class="table-wrap"><table><thead><tr><th>Mã trả</th><th>Mã đơn</th><th>Địa điểm</th><th>Nhân viên</th><th>Thời gian</th></tr></thead><tbody>
        <?php foreach($returns as $return): ?><tr><td><?=e($return['return_code'])?></td><td><?=e($return['order_code'])?></td><td><?=e($return['location'])?></td><td><?=e($return['staff_name'])?></td><td><?=date('d/m/Y H:i',strtotime($return['returned_at']))?></td></tr><?php endforeach; ?>
        <?php if(!$returns): ?><tr><td colspan="5">Chưa có lượt nhận trả.</td></tr><?php endif; ?>
    </tbody></table></div></section>
</div>
