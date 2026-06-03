<?php
class ApprovalService {
    private $repo;

    public function __construct() {
        $this->repo = new BookingRepository();
    }

    public function process($bookingId, $status, $adminId) {
        // Cập nhật trạng thái đơn
        $result = $this->repo->updateStatus($bookingId, $status);
        
        if ($result) {
            // Sau này có thể thêm code lưu vào bảng 'approvals' để biết admin nào duyệt
            return true;
        }
        return false;
    }
}