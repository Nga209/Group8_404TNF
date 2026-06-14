<?php
class AuthMiddleware {
    public static function handle() {
        // Trong đồ án này, ta giả sử dùng Session để kiểm tra
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này']);
            exit();
        }
    }
}