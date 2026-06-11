<?php

class TimeSlotController extends Controller {
    public function index() {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // 1. Tự động kiểm tra và chèn các ca thể thao mẫu nếu trong DB chưa có ca thể thao nào
            try {
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM time_slots WHERE slot_type = 'sport'");
                $checkStmt->execute();
                $sportCount = $checkStmt->fetchColumn();
                
                if ($sportCount == 0) {
                    $inserts = [
                        ['05:30:00', '07:30:00', 'Ca Thể thao Sáng', 'sport', 0],
                        ['16:30:00', '18:30:00', 'Ca Thể thao Chiều', 'sport', 1],
                        ['18:30:00', '20:30:00', 'Ca Thể thao Tối', 'sport', 1]
                    ];
                    foreach ($inserts as $ins) {
                        $insertStmt = $conn->prepare("INSERT INTO time_slots (start_time, end_time, label, slot_type, is_peak) VALUES (?, ?, ?, ?, ?)");
                        $insertStmt->execute($ins);
                    }
                }
            } catch (Exception $e) {
                // Bỏ qua nếu cấu trúc DB cũ chưa đồng bộ cột slot_type
            }

            // 2. Lấy danh sách các ca đang hoạt động (Đa lớp phòng thủ đề phòng cột slot_type thiếu)
            try {
                $stmt = $conn->prepare("SELECT id, label, start_time, end_time, slot_type, is_peak FROM time_slots WHERE is_active = 1 ORDER BY start_time ASC");
                $stmt->execute();
                $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stmt = $conn->prepare("SELECT id, label, start_time, end_time, is_peak FROM time_slots WHERE is_active = 1 ORDER BY start_time ASC");
                $stmt->execute();
                $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($slots as &$slot) {
                    $slot['slot_type'] = 'academic';
                }
            }
            
            return $this->success($slots, "Lấy danh sách ca thành công");
        } catch (Exception $e) {
            return $this->error("Lỗi khi lấy danh sách ca: " . $e->getMessage());
        }
    }
}
