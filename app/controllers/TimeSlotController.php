<?php

class TimeSlotController extends Controller {
    public function index() {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Lấy danh sách các ca đang hoạt động
            $stmt = $conn->prepare("SELECT id, label, start_time, end_time, is_peak FROM time_slots WHERE is_active = 1 ORDER BY start_time ASC");
            $stmt->execute();
            $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($slots, "Lấy danh sách ca thành công");
        } catch (Exception $e) {
            return $this->error("Lỗi khi lấy danh sách ca: " . $e->getMessage());
        }
    }
}
