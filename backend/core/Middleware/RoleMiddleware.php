<?php
class RoleMiddleware {
    public static function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Chỉ Admin mới có quyền này']);
            exit();
        }
    }
}