<?php
require_once __DIR__ . '/../core/Controller.php';

class NotificationController extends Controller {
    
    public function getMyNotifications() {
        $user = $this->checkRole(['user', 'teacher', 'admin']);
        $userId = $user['id'];

        try {
            $db = (new Database())->connect();
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->success($notifications, "Lấy thông báo thành công");
        } catch (Exception $e) {
            return $this->error("Lỗi lấy thông báo: " . $e->getMessage(), 500);
        }
    }

    public function markAsRead() {
        $user = $this->checkRole(['user', 'teacher', 'admin']);
        $userId = $user['id'];

        try {
            $db = (new Database())->connect();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);

            return $this->success([], "Đã đánh dấu đọc tất cả thông báo");
        } catch (Exception $e) {
            return $this->error("Lỗi cập nhật trạng thái thông báo", 500);
        }
    }
}
