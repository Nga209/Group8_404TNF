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
        $this->checkRole(['admin', 'teacher']);
        
        // Gọi hàm đã viết ở Repository để lấy đơn kèm tên người đặt (fullname)
        $bookings = $this->bookingRepo->findAllPending();
        
        if ($bookings) {
            return $this->success($bookings, "Lấy danh sách đơn chờ duyệt thành công");
        } else {
            // Nếu không có đơn nào, trả về mảng rỗng thay vì báo lỗi để giao diện vẫn hiện bảng trắng
            return $this->success([], "Hiện tại không có đơn nào chờ duyệt");
        }
    }

    /**
     * Xử lý Duyệt hoặc Từ chối đơn
     */
    public function approve() {
        $this->checkRole(['admin', 'teacher']);
        
        // Lấy dữ liệu từ Request (JSON)
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Kiểm tra đầu vào xem có đủ dữ liệu không
        if (!isset($input['booking_id']) || !isset($input['status'])) {
            return $this->error("Thiếu thông tin booking_id hoặc status");
        }

        $bookingId = $input['booking_id'];
        $status = $input['status']; // 'approved' hoặc 'cancelled'

        // Gọi Repository để update trạng thái vào DB
        if ($this->bookingRepo->updateStatus($bookingId, $status)) {
            return $this->success([], "Cập nhật trạng thái thành công");
        } else {
            return $this->error("Không thể cập nhật trạng thái vào cơ sở dữ liệu");
        }
    }
}