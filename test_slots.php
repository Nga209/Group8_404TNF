<?php
require '../backend/config/config.php';
require '../backend/app/core/Database.php';
$db = (new Database())->connect();
$stmt = $db->query('SELECT * FROM time_slots');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
