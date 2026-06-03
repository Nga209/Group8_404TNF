<?php
class ResourceRepository {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    // Lấy toàn bộ danh sách phòng/thiết bị - Đã xóa bỏ WHERE status để hết lỗi đỏ
    public function getAllResources() {
        $sql = "SELECT * FROM resources"; 
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Hàm đếm để Dashboard nhảy số
    public function countAll() {
        $sql = "SELECT COUNT(*) FROM resources";
        return $this->db->query($sql)->fetchColumn();
    }

    // Tìm chi tiết 1 phòng theo ID
    public function find($id) {
        $sql = "SELECT * FROM resources WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Thêm tài nguyên mới
    public function create($data) {
        $sql = "INSERT INTO resources (category_id, name, location, capacity, description, slot_type, is_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['location'],
            $data['capacity'],
            $data['description'],
            $data['slot_type'],
            $data['is_available'] ?? 1
        ]);
    }

    // Cập nhật tài nguyên
    public function update($data) {
        $sql = "UPDATE resources SET category_id = ?, name = ?, location = ?, capacity = ?, description = ?, slot_type = ?, is_available = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['location'],
            $data['capacity'],
            $data['description'],
            $data['slot_type'],
            $data['is_available'],
            $data['id']
        ]);
    }

    // Xóa tài nguyên
    public function delete($id) {
        $sql = "DELETE FROM resources WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}