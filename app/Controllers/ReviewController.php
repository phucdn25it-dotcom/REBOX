<?php
namespace App\Controllers;

use App\Core\{Controller, Database, Flash};

final class ReviewController extends Controller
{
    public function adminIndex(): void
    {
        $this->requireAuth(['ADMIN']);
        $reviews = Database::connection()->query("SELECT r.*,u.name customer,p.name product,o.order_code
            FROM reviews r JOIN users u ON u.id=r.user_id JOIN products p ON p.id=r.product_id
            JOIN rental_orders o ON o.id=r.order_id ORDER BY r.created_at DESC LIMIT 200")->fetchAll();
        $this->view('admin/reviews', ['title' => 'Duyệt đánh giá', 'reviews' => $reviews]);
    }

    public function setStatus(): void
    {
        $this->requireAuth(['ADMIN']);
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['PUBLISHED','HIDDEN'], true)) {
            Flash::set('error', 'Trạng thái đánh giá không hợp lệ.');
            $this->redirect('/admin/reviews');
        }
        Database::connection()->prepare('UPDATE reviews SET status=? WHERE id=?')->execute([$status, $id]);
        Flash::set('success', 'Đã cập nhật trạng thái đánh giá.');
        $this->redirect('/admin/reviews');
    }
}
