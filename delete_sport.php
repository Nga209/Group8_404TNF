<?php
require_once __DIR__ . '/../backend/app/core/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare("DELETE FROM time_slots WHERE slot_type = 'sport'");
    $stmt->execute();
    echo "Deleted sport slots successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
