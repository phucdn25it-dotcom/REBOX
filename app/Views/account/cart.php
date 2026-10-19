<div class="page-wrap narrow">
    <div class="eyebrow dark-eyebrow">YOUR NEXT REUSE</div>
    <h1 class="page-title">Giỏ thuê <em>của bạn.</em></h1>
    <?php if (!$products): ?>
        <div class="empty-state"><span>↗</span><h2>Chiếc hộp tiếp theo đang chờ bạn.</h2><p>Chọn hộp, chọn thời gian và chúng mình sẽ lo phần còn lại.</p><a class="btn btn-dark" href="<?=e(url('/catalog'))?>">Khám phá các loại hộp</a></div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-lines">
                <?php foreach ($products as $p): $q = (int)($cart[$p['id']] ?? 0); ?>
                    <div class="cart-line"><img src="<?=e(image_url($p['image_url']))?>" alt="<?=e($p['name'])?>"><div><h3><?=e($p['name'])?></h3><p><?=$q?> hộp · <?=money($p['rental_price'])?> / ngày · Cọc <?=money($p['deposit_amount'])?></p><form method="post" action="<?=e(url('/cart/remove'))?>"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><button class="link-btn">Xóa</button></form></div></div>
                <?php endforeach; ?>
            </div>
            <form class="checkout-card" method="post" action="<?=e(url('/checkout'))?>">
                <?=csrf_field()?>
                <h2>Xác nhận đơn thuê</h2>
                <label>Số ngày thuê<select name="days"><?php for ($i=1; $i<=7; $i++): ?><option value="<?=$i?>" <?=$i===3?'selected':''?>><?=$i?> ngày</option><?php endfor; ?></select></label>
                <label>Điểm nhận<select name="pickup_location_id" required><?php foreach ($locations as $l): ?><option value="<?=$l['id']?>"><?=e($l['name'])?></option><?php endforeach; ?></select></label>
                <label>Điểm trả<select name="return_location_id" required><?php foreach ($locations as $l): ?><option value="<?=$l['id']?>"><?=e($l['name'])?></option><?php endforeach; ?></select></label>
                <fieldset class="payment-options"><legend>Phương thức thanh toán</legend>
                    <label class="payment-choice"><input type="radio" name="payment_method" value="BANK_TRANSFER" checked><span><b>Chuyển khoản ngân hàng</b><small>Quét QR MB; đơn được xác nhận sau khi nhân viên đối soát.</small></span></label>
                    <label class="payment-choice"><input type="radio" name="payment_method" value="CASH"><span><b>Tiền mặt tại điểm nhận</b><small>Thanh toán tiền thuê và tiền cọc khi nhận hộp.</small></span></label>
                </fieldset>
                <div class="summary-row"><span>Phí thuê</span><span>Tính theo số ngày đã chọn</span></div>
                <div class="summary-row"><span>Tiền cọc</span><span>Được đối soát khi trả hộp</span></div>
                <p>Tiền cọc được giữ riêng và hoàn sau khi nhân viên nhận lại, kiểm tra các hộp thuê.</p>
                <button class="btn btn-dark full">Tạo đơn thuê ↗</button>
            </form>
        </div>
    <?php endif; ?>
</div>
