<?php
require '../backend/config/config.php';
require '../backend/app/core/Database.php';
$db = (new Database())->connect();

// Xem toàn bộ cấu trúc bảng bookings bao gồm các UNIQUE KEY
$stmt = $db->query('SHOW CREATE TABLE bookings');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['Create Table'] . "\n\n";

// Xem tất cả bookings ngày 23/4
$stmt2 = $db->query("SELECT id, user_id, resource_id, slot_id, booking_date, status FROM bookings WHERE booking_date='2026-04-23'");
echo "=== BOOKINGS 23/4 ===\n";
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
