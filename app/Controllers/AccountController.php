<?php
namespace App\Controllers;

use App\Core\{Controller, Database, Flash};

final class AccountController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth(['CUSTOMER']);
        $db = Database::connection();
        $profileQuery = $db->prepare('SELECT name,email,phone,points,created_at FROM users WHERE id=?');
        $profileQuery->execute([$user['id']]);
        $ordersQuery = $db->prepare('SELECT id,order_code,order_status,due_at,grand_total,created_at FROM rental_orders WHERE user_id=? ORDER BY created_at DESC LIMIT 8');
        $ordersQuery->execute([$user['id']]);
        $notificationsQuery = $db->prepare('SELECT id,title,message,created_at,read_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 12');
        $notificationsQuery->execute([$user['id']]);
        $this->view('account/profile', [
            'title' => 'Tài khoản của tôi',
            'profile' => $profileQuery->fetch(),
            'orders' => $ordersQuery->fetchAll(),
            'notifications' => $notificationsQuery->fetchAll(),
        ]);
    }

    public function updateProfile(): void
    {
        $user = $this->requireAuth(['CUSTOMER']);
        verify_csrf();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || ($phone !== '' && !preg_match('/^[0-9+() .-]{8,24}$/', $phone))) {
            Flash::set('error', 'Vui lòng nhập họ tên và số điện thoại hợp lệ.');
            $this->redirect('/account');
        }
        $db = Database::connection();
        try {
            $query = $db->prepare('UPDATE users SET name=?,phone=? WHERE id=?');
            $query->execute([$name, $phone !== '' ? $phone : null, $user['id']]);
            Flash::set('success', 'Thông tin tài khoản đã được cập nhật.');
        } catch (\PDOException $error) {
            Flash::set('error', 'Số điện thoại này đã được tài khoản khác sử dụng.');
        }
        $this->redirect('/account');
    }

    public function readNotifications(): void
    {
        $user = $this->requireAuth(['CUSTOMER']);
        verify_csrf();
        Database::connection()->prepare('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL')
            ->execute([$user['id']]);
        Flash::set('success', 'Đã đánh dấu thông báo là đã đọc.');
        $this->redirect('/account');
    }

    public function cancelOrder(): void
    {
        $user = $this->requireAuth(['CUSTOMER']);
        verify_csrf();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $orderQuery = $db->prepare("SELECT * FROM rental_orders WHERE id=? AND user_id=? AND order_status IN ('PENDING_PAYMENT','CONFIRMED','READY_FOR_PICKUP') FOR UPDATE");
            $orderQuery->execute([$orderId, $user['id']]);
            $order = $orderQuery->fetch();
            if (!$order) throw new \RuntimeException('Đơn không còn đủ điều kiện hủy.');
            $containers = $db->prepare("SELECT c.id,c.location_id FROM rental_items ri
                JOIN rental_item_containers ric ON ric.rental_item_id=ri.id
                JOIN containers c ON c.id=ric.container_id
                WHERE ri.order_id=? AND c.lifecycle_status='RESERVED' FOR UPDATE");
            $containers->execute([$orderId]);
            foreach ($containers->fetchAll() as $box) {
                $db->prepare("UPDATE containers SET lifecycle_status='AVAILABLE' WHERE id=?")->execute([$box['id']]);
                $db->prepare('INSERT INTO inventory_movements(container_id,from_location_id,to_location_id,actor_user_id,reason) VALUES(?,?,?,?,?)')
                    ->execute([$box['id'], $box['location_id'], $box['location_id'], $user['id'], 'Hủy đơn ' . $order['order_code']]);
            }
            $db->prepare('DELETE ric FROM rental_item_containers ric JOIN rental_items ri ON ri.id=ric.rental_item_id WHERE ri.order_id=? AND ric.item_status=\'RESERVED\'')
                ->execute([$orderId]);
            $db->prepare("UPDATE payments SET status='REFUNDED' WHERE order_id=? AND payment_type='RENTAL' AND status='PAID'")
                ->execute([$orderId]);
            $db->prepare("UPDATE payments SET status='FAILED' WHERE order_id=? AND payment_type='RENTAL' AND status='PENDING'")
                ->execute([$orderId]);
            $hold = $db->prepare("SELECT status FROM deposit_transactions WHERE order_id=? AND transaction_type='HOLD' ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $hold->execute([$orderId]);
            if ((float)$order['deposit_total'] > 0 && $hold->fetchColumn() === 'COMPLETED') {
                $db->prepare("INSERT INTO deposit_transactions(order_id,transaction_type,amount,status,reason,processed_by) VALUES(?,'REFUND',?,'COMPLETED','Hoàn cọc do khách hủy đơn trước khi nhận hộp',?)")
                    ->execute([$orderId, $order['deposit_total'], $user['id']]);
            } else {
                $db->prepare("UPDATE deposit_transactions SET status='FAILED',reason='Đơn đã hủy trước khi nhận cọc' WHERE order_id=? AND transaction_type='HOLD' AND status='PENDING'")
                    ->execute([$orderId]);
            }
            $db->prepare("UPDATE rental_orders SET order_status='CANCELLED' WHERE id=?")->execute([$orderId]);
            $db->prepare("INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,note) VALUES(?,?,'ORDER_CANCELLED',?,'Khách hàng đã hủy đơn')")
                ->execute([$orderId, $user['id'], $order['pickup_location_id']]);
            $db->prepare('INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)')
                ->execute([$user['id'], 'Đơn thuê đã hủy', 'Đơn ' . $order['order_code'] . ' đã được hủy; hộp đã được trả về tồn kho.']);
            $db->commit();
            Flash::set('success', 'Đã hủy đơn và giải phóng hộp đã giữ.');
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Flash::set('error', $error instanceof \RuntimeException ? $error->getMessage() : 'Không thể hủy đơn lúc này.');
        }
        $this->redirect('/orders');
    }

    public function submitReview(): void
    {
        $user = $this->requireAuth(['CUSTOMER']);
        verify_csrf();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $db = Database::connection();
        $eligible = $db->prepare("SELECT 1 FROM rental_orders o JOIN rental_items ri ON ri.order_id=o.id
            WHERE o.id=? AND o.user_id=? AND ri.product_id=? AND o.order_status IN ('RETURNED','COMPLETED')");
        $eligible->execute([$orderId, $user['id'], $productId]);
        if (!$eligible->fetchColumn() || $rating < 1 || $rating > 5) {
            Flash::set('error', 'Chỉ có thể đánh giá sản phẩm trong đơn đã trả đủ hộp.');
            $this->redirect('/orders');
        }
        try {
            $db->prepare("INSERT INTO reviews(user_id,product_id,order_id,rating,comment,status) VALUES(?,?,?,?,?,'PENDING')")
                ->execute([$user['id'], $productId, $orderId, $rating, $comment]);
            Flash::set('success', 'Đánh giá đã gửi và chờ quản trị viên duyệt.');
        } catch (\PDOException $error) {
            Flash::set('error', 'Bạn đã đánh giá sản phẩm này trong đơn hàng.');
        }
        $this->redirect('/order?id=' . $orderId);
    }
}
