<div class="page-wrap">
    <div class="admin-nav"><a href="<?=e(url('/admin'))?>">Tổng quan</a><a href="<?=e(url('/admin/products'))?>">Sản phẩm</a><a href="<?=e(url('/admin/containers'))?>">Vòng đời hộp</a><a href="<?=e(url('/admin/locations'))?>">Điểm nhận/trả</a><a class="active" href="<?=e(url('/admin/users'))?>">Tài khoản</a><a href="<?=e(url('/admin/assignments'))?>">Phân công điểm</a><a href="<?=e(url('/admin/reviews'))?>">Đánh giá</a><a href="<?=e(url('/admin/reports'))?>">Báo cáo</a></div>
    <h1 class="page-title">Tài khoản <em>và quyền truy cập.</em></h1>
    <p class="page-lead">Quản trị viên quản lý vai trò, trạng thái tài khoản và phân công phạm vi địa điểm.</p>
    <div class="admin-card"><div class="table-wrap"><table><thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Đổi vai trò</th><th>Khóa tài khoản</th></tr></thead><tbody>
        <?php foreach($users as $u): ?><tr>
            <td><b><?=e($u['name'])?></b><small><?=e($u['email'])?> · <?=e($u['phone']??'')?></small></td>
            <td><?=e($u['role_name'])?></td><td><span class="status-pill"><?=e($u['status'])?></span></td>
            <td><?php if((int)$u['id']!==(int)$_SESSION['user_id']): ?><form method="post" action="<?=e(url('/admin/user/role'))?>"><?=csrf_field()?>
                <input type="hidden" name="id" value="<?=$u['id']?>"><select name="role_code">
                    <?php foreach(['CUSTOMER'=>'Khách hàng','STAFF'=>'Nhân viên','PARTNER'=>'Đối tác','ADMIN'=>'Quản trị viên'] as $code=>$label): ?><option value="<?=$code?>" <?=$u['role_code']===$code?'selected':''?>><?=$label?></option><?php endforeach; ?>
                </select><button class="link-btn">Lưu vai trò</button></form><?php endif; ?></td>
            <td><?php if($u['role_code']!=='ADMIN'): ?><form method="post" action="<?=e(url('/admin/user/status'))?>"><?=csrf_field()?>
                <input type="hidden" name="id" value="<?=$u['id']?>"><input type="hidden" name="status" value="<?=$u['status']==='ACTIVE'?'LOCKED':'ACTIVE'?>"><button class="link-btn"><?=$u['status']==='ACTIVE'?'Khóa':'Mở khóa'?></button></form><?php endif; ?></td>
        </tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>
