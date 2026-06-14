<?php
session_start();
$_SESSION['user'] = ['id' => 1];
$ch = curl_init('http://localhost/INS3064/campus_services_booking/backend/public/bookings');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['resource_id' => 1, 'booking_date' => '2026-04-23', 'slot_id' => 1]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.session_id());
echo curl_exec($ch);
