<?php

class BookingController extends Controller {
    private $service;

    public function __construct() {
        $this->service = new BookingService();
    }

    public function store() {
        // Lấy dữ liệu từ Request
        $request = new Request();
        $input = $request->getBody();

        // Kiểm tra đầu vào cơ bản
        if (!isset($input['resource_id'], $input['booking_date'], $input['slot_id'], $input['user_id'])) {
            return $this->error("Dữ liệu không đầy đủ (cần resource_id, booking_date, slot_id, user_id)");
        }

        // Đảm bảo user_id là số hợp lệ
        $input['user_id'] = intval($input['user_id']);
        if ($input['user_id'] <= 0) {
            return $this->error("user_id không hợp lệ. Vui lòng đăng nhập lại.");
        }

        // Gọi Service xử lý nghiệp vụ
        $result = $this->service->makeBooking($input);

        if ($result['status'] === 'success') {
            return $this->success([], $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }

    public function myBookings() {
        $user = $this->checkRole(); 
        $userId = $user['id'];

        $bookingRepo = new BookingRepository();
        $bookings = $bookingRepo->findByUserId($userId);
        
        return $this->success($bookings, "Lấy danh sách đơn đặt lịch của bạn thành công");
    }

    public function cancel() {
        $user = $this->checkRole();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingId = $input['booking_id'] ?? null;
        $reason = $input['reason'] ?? 'Hủy bởi người đặt';

        if (!$bookingId) {
            return $this->error("Thiếu thông tin booking_id");
        }

        $bookingRepo = new BookingRepository();
        $booking = $bookingRepo->findById($bookingId);

        if (!$booking) {
            return $this->error("Không tìm thấy đơn đặt lịch", 404);
        }

        // Quyền hạn: Chỉ cho phép Admin/Teacher hoặc chính chủ đơn đặt lịch hủy đơn
        if ($user['role'] !== 'admin' && $user['role'] !== 'teacher' && $booking['user_id'] != $user['id']) {
            return $this->error("Bạn không có quyền hủy đơn đặt lịch này", 403);
        }

        if ($bookingRepo->updateStatus($bookingId, 'cancelled')) {
            // Lưu thông tin vào bảng cancellations
            $db = (new Database())->connect();
            $stmt = $db->prepare("INSERT INTO cancellations (booking_id, user_id, reason, cancelled_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$bookingId, $user['id'], $reason]);

            // Gửi thông báo hoạt động
            try {
                $stmtRes = $db->prepare("SELECT name FROM resources WHERE id = ?");
                $stmtRes->execute([$booking['resource_id']]);
                $resName = $stmtRes->fetchColumn() ?: "tài nguyên";

                $notifMsg = "Bạn đã hủy ca đặt " . $resName . " ngày " . date('d/m/Y', strtotime($booking['booking_date'])) . ".";
                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                $stmtNotif->execute([$booking['user_id'], $notifMsg]);
            } catch (Exception $e) {
                // Bỏ qua
            }
            
            return $this->success([], "Hủy đơn đặt lịch thành công");
        } else {
            return $this->error("Không thể cập nhật trạng thái đơn hủy");
        }
    }
}