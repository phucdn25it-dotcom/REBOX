CREATE TABLE IF NOT EXISTS rental_order_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  actor_user_id BIGINT UNSIGNED NULL,
  event_type ENUM('ORDER_CREATED','PAYMENT_CONFIRMED','HANDOVER','RETURN_RECEIVED','CLEANED','DEPOSIT_REFUNDED','ORDER_CANCELLED') NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  container_id BIGINT UNSIGNED NULL,
  condition_status VARCHAR(24) NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES rental_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
  FOREIGN KEY (container_id) REFERENCES containers(id) ON DELETE SET NULL,
  INDEX (order_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rental_order_events(order_id,event_type,note,created_at)
SELECT o.id,'ORDER_CREATED',CONCAT('Đơn thuê ',o.order_code,' được tạo'),o.created_at FROM rental_orders o
WHERE NOT EXISTS (SELECT 1 FROM rental_order_events e WHERE e.order_id=o.id AND e.event_type='ORDER_CREATED');

INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,container_id,condition_status,note,created_at)
SELECT ri.order_id,NULL,'HANDOVER',c.location_id,c.id,ric.checkout_condition,'Bàn giao hộp (dữ liệu lịch sử)',ric.checkout_at
FROM rental_item_containers ric JOIN rental_items ri ON ri.id=ric.rental_item_id JOIN containers c ON c.id=ric.container_id
WHERE ric.checkout_at IS NOT NULL AND NOT EXISTS (SELECT 1 FROM rental_order_events e WHERE e.order_id=ri.order_id AND e.container_id=c.id AND e.event_type='HANDOVER' AND e.created_at=ric.checkout_at);

INSERT INTO rental_order_events(order_id,actor_user_id,event_type,location_id,container_id,condition_status,note,created_at)
SELECT r.order_id,r.staff_id,'RETURN_RECEIVED',r.location_id,ri.container_id,ri.condition_status,COALESCE(ri.note,r.notes),r.returned_at
FROM returns r JOIN return_items ri ON ri.return_id=r.id
WHERE NOT EXISTS (SELECT 1 FROM rental_order_events e WHERE e.order_id=r.order_id AND e.container_id=ri.container_id AND e.event_type='RETURN_RECEIVED' AND e.created_at=r.returned_at);
