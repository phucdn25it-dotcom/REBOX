<?php
namespace App\Controllers;

use App\Core\{Controller, Database};

final class PartnerController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth(['PARTNER']);
        $db = Database::connection();
        $locationQuery = $db->prepare("SELECT l.id,l.name,l.address,l.phone,l.opening_hours,
            COUNT(DISTINCT c.id) container_total,
            SUM(c.lifecycle_status='AVAILABLE') available,
            SUM(c.lifecycle_status='RENTED') rented,
            SUM(c.lifecycle_status IN ('RETURNED_DIRTY','CLEANING')) cleaning
            FROM staff_locations sl JOIN locations l ON l.id=sl.location_id
            LEFT JOIN containers c ON c.location_id=l.id
            WHERE sl.user_id=? GROUP BY l.id ORDER BY l.name");
        $locationQuery->execute([$user['id']]);
        $locations = $locationQuery->fetchAll();
        $orderQuery = $db->prepare("SELECT o.order_code,o.order_status,o.due_at,o.created_at,
            u.name customer,l.name pickup
            FROM rental_orders o JOIN users u ON u.id=o.user_id
            JOIN locations l ON l.id=o.pickup_location_id
            JOIN staff_locations sl ON sl.location_id=l.id
            WHERE sl.user_id=? ORDER BY o.created_at DESC LIMIT 50");
        $orderQuery->execute([$user['id']]);
        $returnQuery = $db->prepare("SELECT r.return_code,r.returned_at,r.notes,l.name location,
            u.name staff_name,o.order_code
            FROM returns r JOIN locations l ON l.id=r.location_id
            JOIN users u ON u.id=r.staff_id JOIN rental_orders o ON o.id=r.order_id
            JOIN staff_locations sl ON sl.location_id=r.location_id
            WHERE sl.user_id=? ORDER BY r.returned_at DESC LIMIT 20");
        $returnQuery->execute([$user['id']]);
        $this->view('partner/dashboard', [
            'title' => 'Khu vực đối tác',
            'locations' => $locations,
            'orders' => $orderQuery->fetchAll(),
            'returns' => $returnQuery->fetchAll(),
        ]);
    }
}
