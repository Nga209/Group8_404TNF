<?php

class BookingService {
    private $repo;
    private $policy;

    public function __construct() {
        $this->repo = new BookingRepository();
        $this->policy = new PolicyService();
    }

    public function makeBooking($data) {
        // 1. Kiểm tra các quy tắc chính sách (Ngày, Giờ cao điểm, Giới hạn tuần)
        $valid = $this->policy->validateBooking($data);
        if ($valid !== true) {
            return ['status' => 'error', 'message' => $valid];
        }

        $db = (new Database())->connect();
        
        // Kiểm tra tính hợp lệ giữa loại tài nguyên và loại ca đặt (học tập / thể thao)
        $stmtResType = $db->prepare("SELECT slot_type FROM resources WHERE id = ?");
        $stmtResType->execute([$data['resource_id']]);
        $resSlotType = $stmtResType->fetchColumn();

        $stmtSlotType = $db->prepare("SELECT slot_type FROM time_slots WHERE id = ?");
        $stmtSlotType->execute([$data['slot_id']]);
        $slotType = $stmtSlotType->fetchColumn();

        if ($resSlotType && $slotType && $resSlotType !== $slotType) {
            return ['status' => 'error', 'message' => 'Khung giờ chọn không phù hợp với loại tài nguyên này.'];
        }

        // Lấy vai trò của người thực hiện đặt chỗ
        $stmtUser = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmtUser->execute([$data['user_id']]);
        $userRole = $stmtUser->fetchColumn();

        // 2. Check trùng ca
        $isAvailable = $this->repo->isRoomAvailable(
            $data['resource_id'], 
            $data['booking_date'], 
            $data['slot_id']
        );

        if (!$isAvailable) {
            // Nếu phòng bị bận, kiểm tra xem có phải do các đơn 'pending' của sinh viên (user) không
            if ($userRole === 'teacher' || $userRole === 'admin') {
                $pendingConflicting = $this->repo->findPendingByUserAndSlot(
                    $data['resource_id'], 
                    $data['booking_date'], 
                    $data['slot_id']
                );
                
                if (!empty($pendingConflicting)) {
                    // Giảng viên được quyền chèn/ghi đè lên đơn pending của sinh viên
                    foreach ($pendingConflicting as $confBooking) {
                        $this->repo->updateStatus($confBooking['id'], 'rejected');
                        
                        // Cập nhật lý do và gửi thông báo
                        $stmtReason = $db->prepare("UPDATE bookings SET reason = ? WHERE id = ?");
                        $stmtReason->execute(["Bị hủy do Giảng viên/Admin đăng ký dạy/sử dụng ưu tiên", $confBooking['id']]);

                        // Tìm tên phòng để hiển thị trong thông báo
                        $stmtRes = $db->prepare("SELECT name FROM resources WHERE id = ?");
                        $stmtRes->execute([$confBooking['resource_id']]);
                        $resName = $stmtRes->fetchColumn() ?: "tài nguyên";

                        $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                        $stmtNotif->execute([
                            $confBooking['user_id'],
                            "Yêu cầu đặt " . $resName . " ngày " . date('d/m/Y', strtotime($confBooking['booking_date'])) . " đã bị hủy do Giảng viên đăng ký sử dụng ưu tiên."
                        ]);
                    }
                    $isAvailable = true;
                }
            }
        }

        if (!$isAvailable) {
            return ['status' => 'error', 'message' => 'Ca này đã có người đặt'];
        }

        // 3. Xác định trạng thái ban đầu (Cần duyệt hay Tự động duyệt)
        $requiresApproval = $this->policy->requiresApproval($data['resource_id'], $data['user_id']);
        $data['status'] = $requiresApproval ? 'pending' : 'approved';

        // 4. Lưu Database
        if ($this->repo->create($data)) {
            $msg = $requiresApproval ? 'Đã gửi yêu cầu đặt chỗ (Chờ phê duyệt)' : 'Đặt chỗ thành công';
            
            try {
                $db = (new Database())->connect();
                $stmtRes = $db->prepare("SELECT name FROM resources WHERE id = ?");
                $stmtRes->execute([$data['resource_id']]);
                $resName = $stmtRes->fetchColumn() ?: "tài nguyên";

                $notifMsg = "Bạn đã tạo yêu cầu đặt " . $resName . " ngày " . date('d/m/Y', strtotime($data['booking_date'])) . ($requiresApproval ? " (Đang chờ duyệt)." : " thành công.");

                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                $stmtNotif->execute([$data['user_id'], $notifMsg]);
            } catch (Exception $e) {
                // Bỏ qua lỗi thông báo để tránh ảnh hưởng đến luồng đặt chỗ chính
            }

            return ['status' => 'success', 'message' => $msg];
        }

        return ['status' => 'error', 'message' => 'Lỗi hệ thống khi lưu đơn'];
    }
}