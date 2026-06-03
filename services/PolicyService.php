<?php
require_once __DIR__ . '/../core/Database.php';

class PolicyService {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function validateBooking($data) {
        $bookingDate = $data['booking_date'];
        $userId = $data['user_id'];
        $resourceId = $data['resource_id'];
        $slotId = $data['slot_id'];

        $selectedDate = strtotime($bookingDate);
        $today = strtotime(date('Y-m-d'));

        // 1. Không đặt cho ngày đã qua
        if ($selectedDate < $today) {
            return "Ngày đặt không được ở quá khứ";
        }

        // 2. Chỉ cho phép đặt trước trong vòng 7 ngày
        $maxDate = strtotime('+7 days', $today);
        if ($selectedDate > $maxDate) {
            return "Hệ thống chỉ cho phép đặt trước tối đa 7 ngày";
        }

        // 3. Kiểm tra giới hạn 2 slot giờ cao điểm/tuần (dành cho sinh viên)
        $userRole = $this->getUserRole($userId);
        if ($userRole === 'user') {
            $isPeak = $this->isPeakSlot($slotId);
            if ($isPeak) {
                if (!$this->checkPeakSlotLimit($userId, $bookingDate)) {
                    return "Bạn đã đạt giới hạn 2 slot giờ cao điểm trong tuần này";
                }
            }
        }

        return true;
    }

    /**
     * Kiểm tra xem khung giờ có phải là giờ cao điểm không
     */
    private function isPeakSlot($slotId) {
        $stmt = $this->db->prepare("SELECT is_peak FROM time_slots WHERE id = ?");
        $stmt->execute([$slotId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Đếm số lượng slot giờ cao điểm người dùng đã đặt trong tuần của ngày booking
     */
    private function checkPeakSlotLimit($userId, $bookingDate) {
        // Tính ngày đầu tuần (Thứ 2) và cuối tuần (Chủ nhật) của ngày đặt chỗ
        $ts = strtotime($bookingDate);
        $startOfWeek = date('Y-m-d', strtotime('monday this week', $ts));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week', $ts));

        $sql = "SELECT COUNT(*) FROM bookings b
                JOIN time_slots s ON b.slot_id = s.id
                WHERE b.user_id = ? 
                AND b.booking_date BETWEEN ? AND ?
                AND s.is_peak = 1
                AND b.status IN ('pending', 'approved')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startOfWeek, $endOfWeek]);
        $count = $stmt->fetchColumn();

        return $count < 2;
    }

    /**
     * Kiểm tra xem tài nguyên có yêu cầu phê duyệt không dựa trên chính sách
     */
    public function requiresApproval($resourceId, $userId) {
        $userRole = $this->getUserRole($userId);
        
        // Lấy category_id của tài nguyên
        $stmt = $this->db->prepare("SELECT category_id FROM resources WHERE id = ?");
        $stmt->execute([$resourceId]);
        $categoryId = $stmt->fetchColumn();

        if (!$categoryId) return true; // Mặc định duyệt nếu không tìm thấy loại

        // Kiểm tra bảng booking_policies
        $stmt = $this->db->prepare("SELECT requires_approval FROM booking_policies 
                                    WHERE category_id = ? AND user_role = ?");
        $stmt->execute([$categoryId, $userRole]);
        $result = $stmt->fetch();

        if ($result) {
            return (bool)$result['requires_approval'];
        }

        // Mặc định: SV (user) cần duyệt, GV/Admin không cần
        return ($userRole === 'user');
    }

    private function getUserRole($userId) {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}