<?php
class ResourceController extends Controller {
    private $repo;

    public function __construct() {
        // Khởi tạo Repository để tương tác với DB
        $this->repo = new ResourceRepository();
    }

    /**
     * Lấy danh sách tài nguyên (Dùng cho trang đặt chỗ và Admin quản lý)
     */
    public function getAll() {
        header('Content-Type: application/json');
        try {
            $resources = $this->repo->getAllResources();
            
            if (!$resources) {
                $resources = [];
            }

            return $this->success($resources, "Lấy danh sách tài nguyên thành công");
        } catch (Exception $e) {
            return $this->error("Lỗi hệ thống: " . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy thông tin chi tiết của một tài nguyên (Phòng/Thiết bị)
     */
    public function getDetail($id) {
        try {
            $resource = $this->repo->find($id);
            if (!$resource) {
                return $this->error("Không tìm thấy tài nguyên", 404);
            }
            return $this->success($resource, "Lấy thông tin thành công");
        } catch (Exception $e) {
            return $this->error("Lỗi: " . $e->getMessage());
        }
    }

    /**
     * Hàm bổ trợ cho Dashboard: Đếm tổng số tài nguyên hiện có
     */
    public function getStats() {
        try {
            // Giả định bạn có hàm count trong ResourceRepository
            $count = $this->repo->countAll(); 
            return $this->success(['total_resources' => $count]);
        } catch (Exception $e) {
            return $this->error("Không thể lấy thống kê");
        }
    }
}