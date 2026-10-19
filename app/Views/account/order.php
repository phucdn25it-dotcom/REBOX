<?php $order = $items[0]; $bankQr = 'https://img.vietqr.io/image/MB-0795742134-compact2.png?amount=' . (int)round((float)$order['grand_total']) . '&addInfo=' . rawurlencode('REBOX ' . $order['order_code']); ?>
<div class="page-wrap narrow">
    <a class="text-link" href="<?=e(url('/orders'))?>">← Đơn thuê của tôi</a>
    <div class="order-head"><div><div class="eyebrow dark-eyebrow">REBOX ORDER · <?=e($order['order_code'])?></div><h1>Chi tiết <em>đơn thuê.</em></h1></div><span class="status-pill"><?=e($order['order_status'])?></span></div>
    <div class="order-panel">
        <div class="summary-row"><span>Ngày nhận dự kiến</span><b><?=date('d/m/Y H:i',strtotime($order['rental_start_at']))?></b></div>
        <div class="summary-row"><span>Hạn trả</span><b><?=date('d/m/Y H:i',strtotime($order['due_at']))?></b></div>
        <div class="summary-row"><span>Điểm nhận</span><b><?=e($order['pickup_name'])?></b></div>
        <div class="summary-row"><span>Điểm trả</span><b><?=e($order['return_name'])?></b></div>
        <div class="summary-row"><span>Tiền thuê</span><b><?=money($order['rental_subtotal'])?></b></div>
        <div class="summary-row"><span>Tiền cọc</span><b><?=money($order['deposit_total'])?></b></div>
        <div class="summary-row"><span>Tổng thanh toán</span><b><?=money($order['grand_total'])?></b></div>
    </div>

    <?php if ($payment && $payment['provider'] === 'BANK_TRANSFER' && $payment['status'] === 'PENDING'): ?>
        <section class="payment-panel">
            <img src="<?=e($bankQr)?>" alt="QR chuyển khoản MB cho đơn <?=e($order['order_code'])?>">
            <div><h2>Quét QR để chuyển khoản</h2><p>Ngân hàng: MB Bank<br>Số tài khoản: <b>0795742134</b><br>Số tiền: <b><?=money($payment['amount'])?></b><br>Nội dung: <code>REBOX <?=e($order['order_code'])?></code></p><p>Đơn sẽ chuyển sang đã xác nhận sau khi nhân viên đối soát giao dịch. Vui lòng giữ nguyên nội dung chuyển khoản.</p><button class="payment-copy" type="button" data-copy="REBOX <?=e($order['order_code'])?>">Sao chép nội dung chuyển khoản</button></div>
        </section>
    <?php elseif ($payment && $payment['provider'] === 'CASH' && $payment['status'] === 'PENDING'): ?>
        <section class="admin-card"><h2>Thanh toán tại điểm nhận</h2><p>Vui lòng thanh toán <?=money($payment['amount'])?> (bao gồm tiền thuê và tiền cọc) cho nhân viên khi nhận hộp.</p></section>
    <?php elseif ($payment && $payment['status'] === 'PAID'): ?>
        <section class="admin-card"><h2>Đã ghi nhận thanh toán</h2><p><?=money($payment['amount'])?> · <?=$payment['provider']==='BANK_TRANSFER'?'Chuyển khoản MB':'Tiền mặt'?><?=!empty($payment['paid_at'])?' · '.date('d/m/Y H:i',strtotime($payment['paid_at'])):''?></p></section>
    <?php endif; ?>

    <?php if (empty($canManage) && $payment && $payment['status'] === 'PENDING' && in_array($order['order_status'], ['PENDING_PAYMENT','CONFIRMED'], true)): ?>
        <form class="admin-card payment-switch" method="post" action="<?=e(url('/order/payment/change'))?>"><?=csrf_field()?>
            <input type="hidden" name="order_id" value="<?=$order['id']?>"><h2>Đổi phương thức thanh toán</h2>
            <label><input type="radio" name="payment_method" value="BANK_TRANSFER" <?=$payment['provider']==='BANK_TRANSFER'?'checked':''?>> Chuyển khoản MB</label>
            <label><input type="radio" name="payment_method" value="CASH" <?=$payment['provider']==='CASH'?'checked':''?>> Tiền mặt tại điểm nhận</label>
            <button class="btn btn-dark">Cập nhật phương thức</button>
        </form>
    <?php endif; ?>

    <?php if (in_array($order['order_status'], ['RETURNED','COMPLETED'], true)): ?>
        <section class="admin-card"><h2>Đối soát tiền cọc</h2><p><?php if ($depositRefund && $depositRefund['status'] === 'COMPLETED'): ?>Đã ghi nhận hoàn <?=money($depositRefund['amount'])?> vào <?=date('d/m/Y H:i',strtotime($depositRefund['created_at']))?>.<?php else: ?>Nhân viên đang kiểm tra tình trạng hộp và sẽ ghi nhận hoàn cọc sau khi đối soát.<?php endif; ?></p></section>
    <?php endif; ?>

    <section class="admin-card"><h2>Nhận và trả hộp</h2><p>Khi đến điểm nhận hoặc trả hộp, mở mã QR của từng hộp bên dưới để nhân viên quét. Nhân viên sẽ ghi nhận bàn giao, tình trạng hộp và lượt trả trong hệ thống. Hộp trả sẽ được kiểm tra trước khi hoàn tiền cọc.</p></section>
    <section class="admin-card order-history">
        <div class="staff-section-heading"><span class="eyebrow dark-eyebrow">LỊCH SỬ ĐƠN THUÊ</span><h2>Theo dõi bàn giao và nhận trả</h2><p>Mỗi lần nhân viên quét QR, ghi nhận tình trạng hoặc hoàn cọc sẽ xuất hiện tại đây.</p></div>
        <?php if (!empty($events)): ?><ol class="order-timeline">
            <?php foreach ($events as $event): $eventLabels = ['ORDER_CREATED'=>'Tạo đơn','PAYMENT_CONFIRMED'=>'Xác nhận thanh toán','HANDOVER'=>'Bàn giao hộp','RETURN_REQUESTED'=>'Khách yêu cầu trả hộp','RETURN_APPROVED'=>'Xác nhận điểm trả','RETURN_DECLINED'=>'Từ chối yêu cầu trả','RETURN_RECEIVED'=>'Đã nhận hộp trả','CLEANED'=>'Đã vệ sinh và kiểm tra','DEPOSIT_REFUNDED'=>'Hoàn tiền cọc','ORDER_CANCELLED'=>'Hủy đơn']; ?>
                <li class="order-event"><span class="order-event-dot"></span><div class="order-event-content"><div class="order-event-title"><b><?=e($eventLabels[$event['event_type']] ?? $event['event_type'])?></b><time><?=date('d/m/Y H:i', strtotime($event['created_at']))?></time></div>
                    <p><?php if (!empty($event['serial_code'])): ?>Hộp <?=e($event['serial_code'])?><?php endif; ?><?php if (!empty($event['condition_status'])): ?> · Tình trạng: <?=e($event['condition_status'])?><?php endif; ?><?php if (!empty($event['location_name'])): ?> · <?=e($event['location_name'])?><?php endif; ?></p>
                    <?php if (!empty($event['note'])): ?><small><?=e($event['note'])?></small><?php endif; ?><?php if (!empty($event['actor_name'])): ?><small>Ghi nhận bởi <?=e($event['actor_name'])?></small><?php endif; ?></div></li>
            <?php endforeach; ?>
        </ol><?php else: ?><p class="empty-state">Chưa có cập nhật bàn giao hoặc nhận trả.</p><?php endif; ?>
    </section>
    <?php if (empty($canManage) && in_array($order['order_status'], ['RENTED','PARTIALLY_RETURNED'], true)): ?>
        <?php $activeReturn = null; foreach (($returnRequests ?? []) as $request) { if (in_array($request['status'], ['PENDING','APPROVED'], true)) { $activeReturn = $request; break; } } ?>
        <section class="admin-card"><h2>Trả hộp tại điểm nhận</h2>
            <?php if ($activeReturn): ?><p>Yêu cầu đang <?= $activeReturn['status']==='PENDING'?'chờ nhân viên xác nhận':'đã được xác nhận' ?> tại <b><?=e($activeReturn['location_name'])?></b>.</p><?php if(!empty($activeReturn['staff_note'])):?><p>Ghi chú: <?=e($activeReturn['staff_note'])?></p><?php endif;?>
            <?php elseif (!empty($returnLocations)): ?><p>Chọn điểm trả. Khi đến nơi, mở mã QR từng hộp để nhân viên quét và ghi nhận.</p><form class="admin-form" method="post" action="<?=e(url('/order/return-request'))?>"><?=csrf_field()?><input type="hidden" name="order_id" value="<?=$order['id']?>"><label>Điểm trả<select name="location_id" required><?php foreach($returnLocations as $location):?><option value="<?=$location['id']?>"><?=e($location['name'])?> · <?=e($location['address'])?></option><?php endforeach;?></select></label><label>Ghi chú<textarea name="customer_note" maxlength="500" placeholder="Thời gian dự kiến hoặc thông tin thêm"></textarea></label><button class="btn btn-dark">Gửi yêu cầu trả hộp</button></form>
            <?php else: ?><p>Hiện chưa có điểm nhận trả đang hoạt động. Vui lòng liên hệ ReBox.</p><?php endif;?>
        </section>
    <?php endif; ?>
    <h2 class="subhead">Hộp trong đơn · mã QR bàn giao / trả</h2>
    <?php foreach ($items as $item): ?>
        <article class="admin-card"><h3><?=e($item['product'])?> × <?= (int)$item['quantity'] ?></h3><p><?= (int)$item['rental_days'] ?> ngày · <?=money($item['line_total'])?></p>
            <?php foreach ($boxes as $box): if ((int)$box['product_id'] !== (int)$item['product_id']) continue; ?>
                <div class="qr-card"><div class="qr-pattern" data-qrcode="<?=e($box['qr_token'])?>" aria-label="Mã QR hộp"></div><div><b><?=e($box['serial_code'])?></b><small>Trạng thái: <?=e($box['item_status'])?></small><button class="link-btn" type="button" data-copy="<?=e($box['qr_token'])?>">Sao chép mã QR</button></div></div>
            <?php endforeach; ?>
            <?php if (empty($canManage) && in_array($order['order_status'], ['RETURNED','COMPLETED'], true)): ?>
                <form class="admin-form" method="post" action="<?=e(url('/review'))?>"><?=csrf_field()?>
                    <input type="hidden" name="order_id" value="<?=$order['id']?>"><input type="hidden" name="product_id" value="<?=$item['product_id']?>">
                    <label>Đánh giá<select name="rating" required><option value="5">5 sao</option><option value="4">4 sao</option><option value="3">3 sao</option><option value="2">2 sao</option><option value="1">1 sao</option></select></label><label>Nhận xét<textarea name="comment" maxlength="2000"></textarea></label><button class="btn btn-dark">Gửi đánh giá</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if (empty($canManage) && in_array($order['order_status'], ['RENTED','PARTIALLY_RETURNED'], true)): ?>
        <form class="extend-form" method="post" action="<?=e(url('/order/extend'))?>"><?=csrf_field()?><input type="hidden" name="order_id" value="<?=$order['id']?>"><label>Gia hạn<select name="days"><?php for($days=1;$days<=3;$days++): ?><option value="<?=$days?>"><?=$days?> ngày</option><?php endfor; ?></select></label><button class="btn btn-dark">Gia hạn</button></form>
    <?php endif; ?>
    <?php if (empty($canManage) && in_array($order['order_status'], ['PENDING_PAYMENT','CONFIRMED','READY_FOR_PICKUP'], true)): ?>
        <form method="post" action="<?=e(url('/order/cancel'))?>" class="admin-card order-actions"><?=csrf_field()?><input type="hidden" name="order_id" value="<?=$order['id']?>"><button class="link-btn" onclick="return confirm('Bạn muốn hủy đơn thuê này?')">Hủy đơn</button></form>
    <?php endif; ?>
</div>
