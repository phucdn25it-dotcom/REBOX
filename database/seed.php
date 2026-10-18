<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$seedPassword = getenv('REBOX_SEED_PASSWORD');
if (!$seedPassword || strlen($seedPassword) < 12) {
    throw new RuntimeException('Set REBOX_SEED_PASSWORD to a unique password of at least 12 characters before running the demo seed.');
}

$db = Database::connection();
$db->beginTransaction();

try {
    $roles = [
        ['ADMIN', 'Quản trị viên'],
        ['STAFF', 'Nhân viên'],
        ['CUSTOMER', 'Khách hàng'],
        ['PARTNER', 'Đối tác'],
    ];
    $roleQuery = $db->prepare('INSERT INTO roles(code,name) VALUES(?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)');
    foreach ($roles as $role) $roleQuery->execute($role);

    $categories = [
        ['Hộp cơm', 'hop-com', 'Hộp dùng cho bữa chính'],
        ['Hộp nhiều ngăn', 'hop-nhieu-ngan', 'Tách riêng các món ăn'],
        ['Hộp canh', 'hop-canh', 'Hộp chống tràn cho món nước'],
        ['Hộp salad', 'hop-salad', 'Dành cho salad và món nguội'],
    ];
    $categoryQuery = $db->prepare('INSERT INTO categories(name,slug,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description)');
    foreach ($categories as $category) $categoryQuery->execute($category);

    $locations = [
        ['ReBox Nguyễn Huệ', '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh', '028 3822 1000', '08:00–21:00 mỗi ngày', 180],
        ['ReBox Đại học', '88 Võ Văn Ngân, TP. Thủ Đức, TP. Hồ Chí Minh', '028 3896 7000', '07:30–20:30 mỗi ngày', 120],
        ['Điểm đối tác Bếp Xanh', '25 Nguyễn Thị Minh Khai, Quận 1, TP. Hồ Chí Minh', '090 123 4567', '09:00–22:00 mỗi ngày', 80],
    ];
    $locationQuery = $db->prepare('INSERT INTO locations(name,address,phone,opening_hours,capacity) VALUES(?,?,?,?,?)');
    foreach ($locations as $location) {
        $exists = $db->prepare('SELECT id FROM locations WHERE name=? LIMIT 1');
        $exists->execute([$location[0]]);
        if (!$exists->fetchColumn()) $locationQuery->execute($location);
    }

    $accounts = [
        ['Admin ReBox', 'admin@rebox.local', 'ADMIN'],
        ['Nhân viên Nguyễn Huệ', 'staff@rebox.local', 'STAFF'],
        ['Khách hàng mẫu', 'khach@rebox.local', 'CUSTOMER'],
        ['Đối tác ReBox', 'partner@rebox.local', 'PARTNER'],
    ];
    $accountQuery = $db->prepare("INSERT INTO users(role_id,name,email,password_hash)
        SELECT id,?,?,? FROM roles WHERE code=?
        ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash),status='ACTIVE'");
    foreach ($accounts as [$name, $email, $role]) {
        $accountQuery->execute([$name, $email, password_hash($seedPassword, PASSWORD_DEFAULT), $role]);
    }

    $products = [
        ['RB-1000', 'Hộp cơm xanh 1000 ml', 'hop-com-xanh-1000', 'Dung tích rộng cho bữa trưa đầy đủ. Nắp kín, dễ xếp chồng và dùng lại nhiều lần.', 'PP an toàn thực phẩm', 1000, '20 × 15 × 6 cm', 180, 'Cơm, món khô'],
        ['RB-900D', 'Hộp hai ngăn 900 ml', 'hop-hai-ngan-900', 'Hai ngăn riêng biệt giúp giữ món chính và món phụ gọn gàng.', 'PP an toàn thực phẩm', 900, '19 × 14 × 6 cm', 195, 'Cơm, món mặn, rau'],
        ['RB-600S', 'Hộp canh kín 600 ml', 'hop-canh-600', 'Thiết kế nắp khóa bốn cạnh, phù hợp món nước và súp.', 'PP an toàn thực phẩm', 600, '12 × 12 × 8 cm', 130, 'Canh, súp, nước sốt'],
        ['RB-750F', 'Bát salad 750 ml', 'bat-salad-750', 'Bát rộng nhẹ cho salad, trái cây và món ăn nguội.', 'PP an toàn thực phẩm', 750, '16 × 16 × 7 cm', 145, 'Salad, trái cây'],
    ];
    $productQuery = $db->prepare('UPDATE products SET name=?,slug=?,description=?,material=?,capacity_ml=?,dimensions=?,weight_g=?,food_types=? WHERE sku=?');
    foreach ($products as [$sku, $name, $slug, $description, $material, $capacity, $dimensions, $weight, $foodTypes]) {
        $productQuery->execute([$name, $slug, $description, $material, $capacity, $dimensions, $weight, $foodTypes, $sku]);
    }

    $locationIds = $db->query('SELECT id FROM locations WHERE status=\'ACTIVE\' ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    $assignmentQuery = $db->prepare('INSERT IGNORE INTO staff_locations(user_id,location_id) VALUES(?,?)');
    foreach (['staff@rebox.local', 'partner@rebox.local'] as $email) {
        $userQuery = $db->prepare('SELECT id FROM users WHERE email=?');
        $userQuery->execute([$email]);
        $userId = (int)$userQuery->fetchColumn();
        foreach ($locationIds as $locationId) $assignmentQuery->execute([$userId, $locationId]);
    }

    $productIds = $db->query('SELECT id FROM products ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    $containerQuery = $db->prepare('INSERT IGNORE INTO containers(product_id,location_id,serial_code,qr_token,lifecycle_status) VALUES(?,?,?,?,\'AVAILABLE\')');
    foreach ($productIds as $productIndex => $productId) {
        for ($number = 1; $number <= 12; $number++) {
            $serial = sprintf('RB-%02d-%03d', $productIndex + 1, $number);
            $locationId = $locationIds[($number - 1) % count($locationIds)];
            $containerQuery->execute([$productId, $locationId, $serial, bin2hex(random_bytes(32))]);
        }
    }

    $db->commit();
    echo "Seed hoàn tất. Tài khoản demo dùng mật khẩu đã cấu hình qua REBOX_SEED_PASSWORD.\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
