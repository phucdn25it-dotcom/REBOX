<div class="page-wrap">
    <div class="admin-nav"><a href="<?=e(url('/admin'))?>">Tổng quan</a><a href="<?=e(url('/admin/users'))?>">Tài khoản</a><a class="active" href="<?=e(url('/admin/assignments'))?>">Phân công điểm</a><a href="<?=e(url('/admin/locations'))?>">Điểm nhận/trả</a></div>
    <h1 class="page-title">Phân công <em>nhân viên và đối tác.</em></h1>
    <p class="page-lead">Nhân viên và đối tác chỉ xem được đơn, tồn kho tại các điểm được gán.</p>
    <?php foreach($users as $assignedUser): $selectedLocations=$assignments[(int)$assignedUser['id']]??[]; ?>
        <section class="admin-card">
            <div class="admin-heading"><div><h2><?=e($assignedUser['name'])?></h2><p class="muted"><?=e($assignedUser['email'])?> · <?=e($assignedUser['role_code'])?></p></div></div>
            <form method="post" action="<?=e(url('/admin/assignments'))?>" class="admin-form">
                <?=csrf_field()?>
                <input type="hidden" name="user_id" value="<?=$assignedUser['id']?>">
                <?php foreach($locations as $location): ?>
                    <label class="check-option"><input type="checkbox" name="location_ids[]" value="<?=$location['id']?>" <?=in_array((int)$location['id'],$selectedLocations,true)?'checked':''?> <?=($location['status']??'ACTIVE')!=='ACTIVE'?'disabled':''?>> <?=e($location['name'])?> <small><?=e($location['address'])?></small></label>
                <?php endforeach; ?>
                <button class="btn btn-dark">Lưu phân công</button>
            </form>
        </section>
    <?php endforeach; ?>
    <?php if(!$users): ?><div class="empty-state"><h2>Chưa có tài khoản nhân viên hoặc đối tác.</h2><p>Đổi vai trò tài khoản trong mục Tài khoản trước, sau đó quay lại để phân công địa điểm.</p></div><?php endif; ?>
</div>
