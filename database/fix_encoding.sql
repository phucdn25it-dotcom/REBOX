SET NAMES utf8mb4;
USE rebox;

UPDATE roles
SET name = CASE code
    WHEN 'ADMIN' THEN 'Quản trị viên'
    WHEN 'STAFF' THEN 'Nhân viên'
    WHEN 'CUSTOMER' THEN 'Khách hàng'
    WHEN 'PARTNER' THEN 'Đối tác'
    ELSE name
END;

UPDATE categories
SET name = CASE slug
    WHEN 'hop-com' THEN 'Hộp cơm'
    WHEN 'hop-nhieu-ngan' THEN 'Hộp nhiều ngăn'
    WHEN 'hop-canh' THEN 'Hộp canh'
    WHEN 'hop-salad' THEN 'Hộp salad'
    ELSE name
END,
description = CASE slug
    WHEN 'hop-com' THEN 'Hộp dùng cho bữa chính'
    WHEN 'hop-nhieu-ngan' THEN 'Tách riêng các món ăn'
    WHEN 'hop-canh' THEN 'Hộp chống tràn cho món nước'
    WHEN 'hop-salad' THEN 'Dành cho salad và món nguội'
    ELSE description
END;

UPDATE locations
SET name = CASE id
    WHEN 1 THEN 'ReBox Nguyễn Huệ'
    WHEN 2 THEN 'ReBox Đại học'
    WHEN 3 THEN 'Điểm đối tác Bếp Xanh'
    ELSE name
END,
address = CASE id
    WHEN 1 THEN '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh'
    WHEN 2 THEN '88 Võ Văn Ngân, TP. Thủ Đức, TP. Hồ Chí Minh'
    WHEN 3 THEN '25 Nguyễn Thị Minh Khai, Quận 1, TP. Hồ Chí Minh'
    ELSE address
END;

UPDATE products
SET name = CASE sku
    WHEN 'RB-1000' THEN 'Hộp cơm xanh 1000 ml'
    WHEN 'RB-900D' THEN 'Hộp hai ngăn 900 ml'
    WHEN 'RB-600S' THEN 'Hộp canh kín 600 ml'
    WHEN 'RB-750F' THEN 'Bát salad 750 ml'
    ELSE name
END,
description = CASE sku
    WHEN 'RB-1000' THEN 'Dung tích rộng cho bữa trưa đầy đủ. Nắp kín, dễ xếp chồng và dùng lại nhiều lần.'
    WHEN 'RB-900D' THEN 'Hai ngăn riêng biệt giúp giữ món chính và món phụ gọn gàng.'
    WHEN 'RB-600S' THEN 'Thiết kế nắp khóa bốn cạnh, phù hợp món nước và súp.'
    WHEN 'RB-750F' THEN 'Bát rộng nhẹ cho salad, trái cây và món ăn nguội.'
    ELSE description
END,
material = 'PP an toàn thực phẩm',
food_types = CASE sku
    WHEN 'RB-1000' THEN 'Cơm, món khô'
    WHEN 'RB-900D' THEN 'Cơm, món mặn, rau'
    WHEN 'RB-600S' THEN 'Canh, súp, nước sốt'
    WHEN 'RB-750F' THEN 'Salad, trái cây'
    ELSE food_types
END
WHERE sku IN ('RB-1000','RB-900D','RB-600S','RB-750F');
