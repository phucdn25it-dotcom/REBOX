<div class="page-wrap">
    <div class="admin-nav"><a href="<?=e(url('/admin'))?>">Tổng quan</a><a href="<?=e(url('/admin/users'))?>">Tài khoản</a><a href="<?=e(url('/admin/assignments'))?>">Phân công điểm</a><a class="active" href="<?=e(url('/admin/reviews'))?>">Đánh giá</a></div>
    <h1 class="page-title">Đánh giá <em>khách hàng.</em></h1>
    <div class="admin-card"><div class="table-wrap"><table><thead><tr><th>Sản phẩm</th><th>Khách hàng</th><th>Đơn hàng</th><th>Sao</th><th>Nhận xét</th><th>Trạng thái</th><th>Duyệt</th></tr></thead><tbody>
        <?php foreach($reviews as $review): ?><tr><td><?=e($review['product'])?></td><td><?=e($review['customer'])?></td><td><?=e($review['order_code'])?></td><td><?= (int)$review['rating'] ?>/5</td><td><?=e($review['comment'])?></td><td><?=e($review['status'])?></td><td>
            <?php if($review['status']==='PENDING'): ?><form method="post" action="<?=e(url('/admin/review/status'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$review['id']?>"><input type="hidden" name="status" value="PUBLISHED"><button class="link-btn">Duyệt</button></form><?php endif; ?>
            <?php if($review['status']==='PUBLISHED'): ?><form method="post" action="<?=e(url('/admin/review/status'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$review['id']?>"><input type="hidden" name="status" value="HIDDEN"><button class="link-btn">Ẩn</button></form><?php endif; ?>
            <?php if($review['status']==='HIDDEN'): ?><form method="post" action="<?=e(url('/admin/review/status'))?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$review['id']?>"><input type="hidden" name="status" value="PUBLISHED"><button class="link-btn">Hiện lại</button></form><?php endif; ?>
        </td></tr><?php endforeach; ?>
        <?php if(!$reviews): ?><tr><td colspan="7">Chưa có đánh giá nào.</td></tr><?php endif; ?>
    </tbody></table></div></div>
</div>
