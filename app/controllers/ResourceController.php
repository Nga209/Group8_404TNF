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

    public function store() {
        $this->checkRole(['admin']);

        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $category_id = $input['category_id'] ?? null;
        $location = $input['location'] ?? '';
        $capacity = $input['capacity'] ?? 0;
        $description = $input['description'] ?? '';
        $slot_type = $input['slot_type'] ?? 'all';
        $is_available = $input['is_available'] ?? 1;

        if (empty($name) || !$category_id || empty($location)) {
            return $this->error("Vui lòng nhập tên, danh mục và vị trí tài nguyên!");
        }

        $success = $this->repo->create([
            'category_id' => intval($category_id),
            'name' => $name,
            'location' => $location,
            'capacity' => intval($capacity),
            'description' => $description,
            'slot_type' => $slot_type,
            'is_available' => intval($is_available)
        ]);

        if ($success) {
            return $this->success([], "Thêm tài nguyên thành công!");
        }
        return $this->error("Không thể thêm tài nguyên.");
    }

    public function update() {
        $this->checkRole(['admin']);

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        $category_id = $input['category_id'] ?? null;
        $location = $input['location'] ?? '';
        $capacity = $input['capacity'] ?? 0;
        $description = $input['description'] ?? '';
        $slot_type = $input['slot_type'] ?? 'all';
        $is_available = $input['is_available'] ?? 1;

        if (!$id || empty($name) || !$category_id || empty($location)) {
            return $this->error("Thiếu thông tin cập nhật tài nguyên!");
        }

        $success = $this->repo->update([
            'id' => intval($id),
            'category_id' => intval($category_id),
            'name' => $name,
            'location' => $location,
            'capacity' => intval($capacity),
            'description' => $description,
            'slot_type' => $slot_type,
            'is_available' => intval($is_available)
        ]);

        if ($success) {
            return $this->success([], "Cập nhật tài nguyên thành công!");
        }
        return $this->error("Cập nhật tài nguyên thất bại.");
    }

    public function delete() {
        $this->checkRole(['admin']);

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            return $this->error("Thiếu ID tài nguyên cần xóa!");
        }

        $success = $this->repo->delete(intval($id));

        if ($success) {
            return $this->success([], "Xóa tài nguyên thành công!");
        }
        return $this->error("Không thể xóa tài nguyên.");
    }
}