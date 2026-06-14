<?php
require '../backend/config/config.php';
require '../backend/app/core/Database.php';
$db = (new Database())->connect();

// Phải xóa FK liên quan đến cột trong UNIQUE KEY trước
$db->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_2");
$db->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_3");

// Xóa UNIQUE KEY gây lỗi
$db->exec("ALTER TABLE bookings DROP INDEX unique_booking");

// Tạo lại FK bình thường (không cần UNIQUE KEY)
$db->exec("ALTER TABLE bookings ADD CONSTRAINT bookings_ibfk_2 FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE");
$db->exec("ALTER TABLE bookings ADD CONSTRAINT bookings_ibfk_3 FOREIGN KEY (slot_id) REFERENCES time_slots(id) ON DELETE CASCADE");

echo "Hoàn tất! Đã xóa UNIQUE KEY và tạo lại FK thành công!\n";

// Kiểm tra lại
$stmt = $db->query('SHOW INDEX FROM bookings');
$indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n=== CÁC INDEX HIỆN TẠI ===\n";
foreach ($indexes as $idx) {
    echo "- " . $idx['Key_name'] . " (" . $idx['Column_name'] . ")\n";
}
