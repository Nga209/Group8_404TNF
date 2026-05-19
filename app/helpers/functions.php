<?php
// Hàm trả về JSON nhanh
function jsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Hàm format tiền tệ hoặc ngày tháng (nếu cần)
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}