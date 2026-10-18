<?php
namespace App\Controllers;

use App\Core\{Controller, Database, Flash};

final class AccessController extends Controller
{
    public function assignments(): void
    {
        $this->requireAuth(['ADMIN']);
        $db = Database::connection();
        $users = $db->query("SELECT u.id,u.name,u.email,r.code role_code
            FROM users u JOIN roles r ON r.id=u.role_id
            WHERE r.code IN ('STAFF','PARTNER') ORDER BY r.code,u.name")->fetchAll();
        $locations = $db->query("SELECT id,name,address,status FROM locations ORDER BY name")->fetchAll();
        $assigned = $db->query('SELECT user_id,location_id FROM staff_locations')->fetchAll();
        $map = [];
        foreach ($assigned as $row) {
            $map[(int)$row['user_id']][] = (int)$row['location_id'];
        }
        $this->view('admin/assignments', [
            'title' => 'Phân công nhân viên và đối tác',
            'users' => $users,
            'locations' => $locations,
            'assignments' => $map,
        ]);
    }

    public function saveAssignments(): void
    {
        $this->requireAuth(['ADMIN']);
        verify_csrf();
        $userId = (int)($_POST['user_id'] ?? 0);
        $db = Database::connection();
        $userQuery = $db->prepare("SELECT u.id,r.code FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND r.code IN ('STAFF','PARTNER')");
        $userQuery->execute([$userId]);
        if (!$userQuery->fetch()) {
            Flash::set('error', 'Chỉ có thể phân công tài khoản nhân viên hoặc đối tác.');
            $this->redirect('/admin/assignments');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $_POST['location_ids'] ?? []))));
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM staff_locations WHERE user_id=?')->execute([$userId]);
            $insert = $db->prepare('INSERT INTO staff_locations(user_id,location_id) SELECT ?,id FROM locations WHERE id=? AND status=\'ACTIVE\'');
            foreach ($ids as $locationId) {
                $insert->execute([$userId, $locationId]);
            }
            $db->prepare("INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,?,?,?,?)")
                ->execute([$_SESSION['user_id'], 'ASSIGN_LOCATIONS', 'users', $userId, $_SERVER['REMOTE_ADDR'] ?? null]);
            $db->commit();
            Flash::set('success', 'Đã lưu danh sách điểm được phân công.');
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            Flash::set('error', 'Không thể lưu phân công. Vui lòng thử lại.');
        }
        $this->redirect('/admin/assignments');
    }
}
