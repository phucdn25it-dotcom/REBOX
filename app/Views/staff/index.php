<div class="page-wrap staff-page">
    <div class="admin-heading">
        <div><div class="eyebrow dark-eyebrow">NHÂN VIÊN · ĐIỂM ĐƯỢC PHÂN CÔNG</div><h1 class="page-title">Quầy vận hành <em>hôm nay.</em></h1></div>
        <a class="btn btn-dark" href="<?=e(url('/staff'))?>">Bảng điều khiển</a>
    </div>
    <div class="admin-nav"><a class="active" href="<?=e(url('/staff'))?>">Đơn cần xử lý</a><a href="<?=e(url('/orders'))?>">Đơn thuê</a></div>

    <section class="admin-card">
        <div class="staff-section-heading"><span class="eyebrow dark-eyebrow">NHẬN VÀ BÀN GIAO ĐƠN</span><h2>Quét hoặc nhập mã QR</h2><p>Nhập token QR trong chi tiết đơn để bàn giao hộp, nhận hộp trả hoặc xác nhận vệ sinh.</p></div>
        <form class="admin-form" method="post" action="<?=e(url('/staff/action'))?>">
            <?=csrf_field()?>
            <label class="span-all">QR token<input name="qr_token" required placeholder="Dán token QR tại đây"></label>
            <label>Thao tác<select name="action"><option value="checkout">Bàn giao hộp</option><option value="return">Nhận hộp trả</option><option value="clean">Hoàn tất vệ sinh</option></select></label>
            <label>Tình trạng hộp<select name="condition"><option value="CLEAN">Sạch, dùng lại được</option><option value="DIRTY">Cần vệ sinh</option><option value="DAMAGED">Hư hỏng / cách ly</option><option value="MISSING">Thất lạc</option></select></label>
            <label>Điểm trả<select name="location_id"><?php foreach($locations as $location):?><option value="<?=$location['id']?>"><?=e($location['name'])?></option><?php endforeach;?></select></label>
            <label>Ghi chú<input name="note"></label>
            <button class="btn btn-dark">Ghi nhận thao tác ↗</button>
        </form>
    </section>

    <div class="staff-inventory-grid">
        <section class="admin-card">
            <div class="staff-section-heading"><span class="eyebrow dark-eyebrow">NHẬP KHO</span><h2>Nhập hộp mới</h2><p>Chọn loại hộp, điểm nhận hàng và số lượng. Hệ thống tạo serial, QR và ghi sổ kho cho từng hộp.</p></div>
            <form class="admin-form" method="post" action="<?=e(url('/staff/inventory'))?>">
                <?=csrf_field()?><input type="hidden" name="inventory_action" value="intake">
                <label>Loại hộp<select name="product_id" required><?php foreach($products as $product):?><option value="<?=$product['id']?>"><?=e($product['name'])?> · <?=number_format($product['capacity_ml'])?> ml</option><?php endforeach;?></select></label>
                <label>Điểm nhận hàng<select name="location_id" required><?php foreach($locations as $location):?><option value="<?=$location['id']?>"><?=e($location['name'])?></option><?php endforeach;?></select></label>
                <label>Số lượng hộp<input type="number" name="quantity" min="1" max="100" value="1" required></label>
                <label>Tiền tố serial <small>(không bắt buộc)</small><input name="serial_prefix" maxlength="48" placeholder="Ví dụ: RB-LOT-01"></label>
                <button class="btn btn-dark" <?=$products&&$locations?'':'disabled'?>>Xác nhận nhập kho ↗</button>
            </form>
        </section>
        <section class="admin-card">
            <div class="staff-section-heading"><span class="eyebrow dark-eyebrow">XUẤT / ĐIỀU CHUYỂN</span><h2>Xuất một hộp</h2><p>Nhập serial hộp sẵn sàng. Có thể chuyển sang điểm được phân công hoặc ngừng sử dụng và đưa khỏi tồn kho.</p></div>
            <form class="admin-form" method="post" action="<?=e(url('/staff/inventory'))?>">
                <?=csrf_field()?><input type="hidden" name="inventory_action" value="dispatch">
                <label class="span-all">Mã serial hộp<input name="serial_code" required placeholder="Quét hoặc nhập mã serial trên hộp"></label>
                <label>Hình thức<select name="dispatch_type" id="dispatch-type"><option value="transfer">Chuyển đến điểm khác</option><option value="retire">Xuất khỏi vòng sử dụng</option></select></label>
                <label id="destination-field">Điểm đến<select name="destination_location_id"><?php foreach($locations as $location):?><option value="<?=$location['id']?>"><?=e($location['name'])?></option><?php endforeach;?></select></label>
                <button class="btn btn-dark" <?=$locations?'':'disabled'?>>Xác nhận xuất hộp ↗</button>
            </form>
        </section>
    </div>

    <section class="admin-card staff-stock-card">
        <div class="staff-section-heading"><span class="eyebrow dark-eyebrow">TỒN KHO ĐƯỢC PHÂN CÔNG</span><h2>Hộp tại các điểm của bạn</h2></div>
        <div class="table-wrap"><table><thead><tr><th>Serial</th><th>Loại hộp</th><th>Điểm lưu</th><th>Tình trạng</th><th>Lượt dùng</th></tr></thead><tbody>
            <?php foreach($inventory as $box):?><tr><td><?=e($box['serial_code'])?></td><td><?=e($box['product'])?></td><td><?=e($box['location']??'—')?></td><td><span class="status-pill"><?=e($box['lifecycle_status'])?></span></td><td><?=(int)$box['usage_count']?></td></tr><?php endforeach;?>
            <?php if(!$inventory):?><tr><td colspan="5">Chưa có hộp tại điểm được phân công.</td></tr><?php endif;?>
        </tbody></table></div>
    </section>

    <h2 class="subhead">Đơn cần xử lý</h2>
    <div class="table-wrap"><table><thead><tr><th>Đơn</th><th>Khách</th><th>Điểm nhận</th><th>Hạn trả</th><th>Trạng thái</th><th>Thanh toán</th></tr></thead><tbody>
        <?php foreach($orders as $order):?><tr><td><a class="link-btn" href="<?=e(url('/order?id='.(int)$order['id']))?>"><?=e($order['order_code'])?> · Lịch sử</a></td><td><?=e($order['customer'])?></td><td><?=e($order['pickup'])?></td><td><?=date('d/m H:i',strtotime($order['due_at']))?></td><td><?=e($order['order_status'])?></td><td><?=e(($order['payment_provider']??'').' · '.($order['payment_status']??''))?><?php if(($order['payment_provider']??'')==='BANK_TRANSFER'&&($order['payment_status']??'')==='PENDING'):?><form method="post" action="<?=e(url('/staff/payment/verify'))?>"><?=csrf_field()?><input type="hidden" name="order_id" value="<?=$order['id']?>"><button class="link-btn">Xác nhận đã nhận tiền</button></form><?php endif;?><?php if($order['order_status']==='RETURNED'&&(float)$order['deposit_total']>0&&($order['deposit_refund_status']??'')!=='COMPLETED'&&($order['deposit_hold_status']??'')==='COMPLETED'&&(int)$order['damaged_count']===0):?><form method="post" action="<?=e(url('/staff/deposit/refund'))?>"><?=csrf_field()?><input type="hidden" name="order_id" value="<?=$order['id']?>"><button class="link-btn" onclick="return confirm('Xác nhận đã hoàn tiền cọc cho khách?')">Ghi nhận đã hoàn cọc</button></form><?php elseif((int)($order['damaged_count']??0)>0):?><small>Cần đối soát hộp hỏng / thất lạc</small><?php elseif(($order['deposit_refund_status']??'')==='COMPLETED'):?><small>Đã hoàn cọc</small><?php endif;?></td></tr><?php endforeach;?>
        <?php if(!$orders):?><tr><td colspan="6">Hiện chưa có đơn cần xử lý.</td></tr><?php endif;?>
    </tbody></table></div>
</div>
<script>
const dispatchType = document.getElementById('dispatch-type');
const destinationField = document.getElementById('destination-field');
const destinationSelect = destinationField?.querySelector('select');
dispatchType?.addEventListener('change', () => {
    const transfer = dispatchType.value === 'transfer';
    destinationField.hidden = !transfer;
    if (destinationSelect) destinationSelect.required = transfer;
});
</script>

