<?php
require '../backend/config/config.php';
require '../backend/app/core/Database.php';
$db = (new Database())->connect();

// Kiểm tra logic isRoomAvailable với Ca 1 ngày 23/4
$resourceId = 1;
$date = '2026-04-23';
$slotId = 1;

$sql = "SELECT COUNT(*) as total, GROUP_CONCAT(status) as statuses FROM bookings 
        WHERE resource_id = ? AND booking_date = ? AND slot_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$resourceId, $date, $slotId]);
$all = $stmt->fetch(PDO::FETCH_ASSOC);
echo "TẤT CẢ đơn (kể cả rejected): " . json_encode($all) . "\n\n";

$sql2 = "SELECT COUNT(*) as total FROM bookings 
         WHERE resource_id = ? AND booking_date = ? AND slot_id = ? 
         AND status IN ('pending', 'approved')";
$stmt2 = $db->prepare($sql2);
$stmt2->execute([$resourceId, $date, $slotId]);
$active = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "Đơn active (pending/approved): " . json_encode($active) . "\n";
echo ($active['total'] == 0) ? "=> Ca này CÒN TRỐNG, có thể đặt!\n" : "=> Ca này ĐÃ CÓ NGƯỜI ĐẶT!\n";
