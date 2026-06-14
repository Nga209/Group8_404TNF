<?php
require '../backend/config/config.php';
require '../backend/app/core/Database.php';
$db = (new Database())->connect();

echo "=== USERS ===\n";
$stmt = $db->query('SELECT id, username, email, role FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== RECENT BOOKINGS ===\n";
$stmt2 = $db->query('SELECT * FROM bookings ORDER BY id DESC LIMIT 5');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
