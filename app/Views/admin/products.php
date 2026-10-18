<div class="page-wrap">
    <div class="admin-nav"><a href="<?=e(url('/admin'))?>">Tổng quan</a><a class="active" href="<?=e(url('/admin/products'))?>">Sản phẩm</a><a href="<?=e(url('/admin/containers'))?>">Vòng đời hộp</a><a href="<?=e(url('/admin/locations'))?>">Điểm nhận/trả</a><a href="<?=e(url('/admin/users'))?>">Tài khoản</a><a href="<?=e(url('/admin/reports'))?>">Báo cáo</a></div>
    <h1 class="page-title">Danh mục <em>hộp thuê.</em></h1>
    <div class="admin-card"><h2>Thêm sản phẩm</h2>
        <form class="admin-form" method="post" action="<?=e(url('/admin/product/save'))?>"><?=csrf_field()?>
            <label>Tên hộp<input name="name" required></label><label>Mã SKU<input name="sku" required placeholder="RB-1200"></label>
            <label>Danh mục<select name="category_id" required><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label>
            <label>Dung tích (ml)<input type="number" name="capacity_ml" min="1" required></label><label>Chất liệu<input name="material"></label><label>URL ảnh<input name="image_url" type="url"></label>
            <label>Kích thước<input name="dimensions"></label><label>Khối lượng (g)<input name="weight_g" type="number" min="0"></label><label>Loại thực phẩm<input name="food_types"></label>
            <label>Giá thuê/ngày<input name="rental_price" type="number" min="0" required></label><label>Tiền cọc<input name="deposit_amount" type="number" min="0" required></label><label>Phí trễ/ngày<input name="late_fee_per_day" type="number" min="0" value="0"></label><label>Số ngày thuê tối đa<input name="max_rental_days" type="number" min="1" value="7"></label>
            <label class="span-all">Mô tả<textarea name="description"></textarea></label><button class="btn btn-dark">Thêm sản phẩm</button>
        </form>
    </div>
    <div class="admin-card"><h2>Danh mục sản phẩm</h2><form class="admin-form" method="post" action="<?=e(url('/admin/category/save'))?>"><?=csrf_field()?><label>Tên danh mục<input name="name" required></label><label>Mô tả<input name="description"></label><button class="btn btn-dark">Thêm danh mục</button></form>
        <div class="category-admin-list"><?php foreach($categories as $category):?><div><form class="admin-form" method="post" action="<?=e(url('/admin/category/update'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$category['id']?>"><label>Tên<input name="name" value="<?=e($category['name'])?>" required></label><label>Mô tả<input name="description" value="<?=e($category['description'])?>"></label><label>Trạng thái<select name="status"><option value="1" <?=$category['status']?'selected':''?>>Đang dùng</option><option value="0" <?=!$category['status']?'selected':''?>>Tạm ẩn</option></select></label><button class="link-btn">Lưu danh mục</button></form><form method="post" action="<?=e(url('/admin/category/delete'))?>" data-confirm="Xóa danh mục này? Chỉ xóa được khi danh mục chưa có sản phẩm."><?=csrf_field()?><input type="hidden" name="id" value="<?=$category['id']?>"><button class="link-btn">Xóa</button></form></div><?php endforeach;?></div>
    </div>
    <div class="admin-card"><h2>Sản phẩm hiện có</h2><div class="table-wrap"><table><thead><tr><th>Sản phẩm</th><th>Danh mục</th><th>Thuê / cọc</th><th>Hộp sẵn sàng</th><th>SKU</th><th>Trạng thái / thao tác</th></tr></thead><tbody>
        <?php foreach($products as $p):?><tr>
            <td><b><?=e($p['name'])?></b><small><?=number_format($p['capacity_ml'])?> ml · <?=e($p['material'])?></small></td><td><?=e($p['category'])?></td><td><?=money($p['rental_price'])?> / <?=money($p['deposit_amount'])?></td><td><?=$p['available']?> / <?=$p['total']?></td><td><?=e($p['sku'])?></td>
            <td><span class="status-pill"><?=e($p['status'])?></span><details class="admin-edit"><summary>Sửa thông tin</summary>
                <form class="admin-form" method="post" action="<?=e(url('/admin/product/update'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$p['id']?>">
                    <label>Tên hộp<input name="name" value="<?=e($p['name'])?>" required></label><label>Mã SKU<input name="sku" value="<?=e($p['sku'])?>" required></label>
                    <label>Danh mục<select name="category_id"><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=$p['category_id']==$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></label>
                    <label>Dung tích (ml)<input name="capacity_ml" type="number" min="1" value="<?=$p['capacity_ml']?>" required></label><label>Chất liệu<input name="material" value="<?=e($p['material'])?>"></label><label>URL ảnh<input name="image_url" type="url" value="<?=e($p['image_url'])?>"></label>
                    <label>Kích thước<input name="dimensions" value="<?=e($p['dimensions'])?>"></label><label>Khối lượng (g)<input name="weight_g" type="number" min="0" value="<?=e($p['weight_g'])?>"></label><label>Loại thực phẩm<input name="food_types" value="<?=e($p['food_types'])?>"></label>
                    <label>Giá thuê/ngày<input name="rental_price" type="number" min="0" step="100" value="<?=e($p['rental_price'])?>" required></label><label>Tiền cọc<input name="deposit_amount" type="number" min="0" step="100" value="<?=e($p['deposit_amount'])?>" required></label><label>Phí trễ/ngày<input name="late_fee_per_day" type="number" min="0" value="<?=e($p['late_fee_per_day'])?>"></label><label>Ngày thuê tối đa<input name="max_rental_days" type="number" min="1" value="<?=$p['max_rental_days']?>"></label>
                    <label>Trạng thái<select name="status"><option value="ACTIVE" <?=$p['status']==='ACTIVE'?'selected':''?>>Đang kinh doanh</option><option value="INACTIVE" <?=$p['status']==='INACTIVE'?'selected':''?>>Ngừng kinh doanh</option></select></label><label class="span-all">Mô tả<textarea name="description"><?=e($p['description'])?></textarea></label><button class="btn btn-dark">Lưu thay đổi</button>
                </form>
            </details>
            <?php if($p['status']==='ACTIVE'):?><form method="post" action="<?=e(url('/admin/product/delete'))?>" data-confirm="Ngừng kinh doanh sản phẩm này? Lịch sử thuê sẽ được giữ lại."><?=csrf_field()?><input type="hidden" name="id" value="<?=$p['id']?>"><button class="link-btn">Ngừng kinh doanh</button></form><?php endif;?></td>
        </tr><?php endforeach;?>
        <?php if(!$products):?><tr><td colspan="6">Chưa có sản phẩm.</td></tr><?php endif;?>
    </tbody></table></div></div>
</div>
