<?php

// Kế thừa từ Controller cha để dùng các hàm success/error
class ApprovalController extends Controller {
    private $bookingRepo;

    public function __construct() {
        // Đảm bảo file BookingRepository.php đã tồn tại trong thư mục repositories
        $this->bookingRepo = new BookingRepository();
    }

    /**
     * Lấy danh sách đơn để Admin duyệt
     * Sử dụng hàm findAllPending từ Repository để đảm bảo đúng tên cột (fullname)
     */
    public function index() {
        $user = $this->checkRole(['admin', 'teacher']);
        
        if ($user['role'] === 'teacher') {
            $db = (new Database())->connect();
            $stmt = $db->prepare("SELECT resource_id FROM teacher_resources WHERE teacher_id = ?");
            $stmt->execute([$user['id']]);
            $resourceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $bookings = $this->bookingRepo->findAllByResourceIds($resourceIds);
        } else {
            $bookings = $this->bookingRepo->findAll();
        }
        
        if ($bookings) {
            return $this->success($bookings, "Lấy danh sách đơn thành công");
        } else {
            return $this->success([], "Hiện tại không có đơn nào");
        }
    }

    /**
     * Xử lý Duyệt hoặc Từ chối đơn
     */
    public function approve() {
        $user = $this->checkRole(['admin', 'teacher']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['booking_id']) || !isset($input['status'])) {
            return $this->error("Thiếu thông tin booking_id hoặc status");
        }

        $bookingId = $input['booking_id'];
        $status = $input['status']; // 'approved' hoặc 'rejected'

        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            return $this->error("Không tìm thấy đơn đặt lịch cần duyệt");
        }

        if ($user['role'] === 'teacher') {
            $db = (new Database())->connect();
            $stmt = $db->prepare("SELECT COUNT(*) FROM teacher_resources WHERE teacher_id = ? AND resource_id = ?");
            $stmt->execute([$user['id'], $booking['resource_id']]);
            if ($stmt->fetchColumn() == 0) {
                return $this->error("Bạn không có quyền phê duyệt hoặc từ chối đơn đặt phòng này", 403);
            }
        }

        // Gọi Repository để update trạng thái vào DB
        if ($this->bookingRepo->updateStatus($bookingId, $status)) {
            // Lấy tên phòng
            $db = (new Database())->connect();
            $stmtRes = $db->prepare("SELECT name FROM resources WHERE id = ?");
            $stmtRes->execute([$booking['resource_id']]);
            $resName = $stmtRes->fetchColumn() ?: "tài nguyên";

            // Tạo thông báo cho học sinh đặt đơn
            $statusText = $status === 'approved' ? 'phê duyệt' : 'từ chối';
            $message = "Yêu cầu đặt " . $resName . " ngày " . date('d/m/Y', strtotime($booking['booking_date'])) . " của bạn đã được " . $statusText . " bởi " . $user['fullname'] . ".";

            $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
            $stmtNotif->execute([$booking['user_id'], $message]);

            return $this->success([], "Cập nhật trạng thái thành công");
        } else {
            return $this->error("Không thể cập nhật trạng thái vào cơ sở dữ liệu");
        }
    }
}