<?php

class BookingRepository {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    // 1. LẤY DANH SÁCH ĐƠN CHỜ DUYỆT (Để Admin thấy đơn)
    public function findAllPending() {
        // Sử dụng u.fullname vì bảng users của bạn dùng tên này
        $sql = "SELECT b.*, u.fullname, r.name as resource_name, s.label as slot_name 
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN resources r ON b.resource_id = r.id
                JOIN time_slots s ON b.slot_id = s.id
                WHERE b.status = 'pending'
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 1b. LẤY TẤT CẢ DANH SÁCH ĐƠN (Để hiển thị đầy đủ các Tab lọc: Chờ duyệt, Đã duyệt, Từ chối)
    public function findAll() {
        $sql = "SELECT b.*, u.fullname, r.name as resource_name, s.label as slot_name 
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN resources r ON b.resource_id = r.id
                JOIN time_slots s ON b.slot_id = s.id
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. THỐNG KÊ CHO DASHBOARD (Để Dashboard nhảy số)
    public function getStats() {
        $stats = [];
        // Đếm đơn chờ duyệt
        $sql = "SELECT COUNT(*) FROM bookings WHERE status = 'pending'";
        $stats['pending'] = $this->db->query($sql)->fetchColumn();
        
        // Đếm tổng số đơn
        $sql = "SELECT COUNT(*) FROM bookings";
        $stats['total'] = $this->db->query($sql)->fetchColumn();
        
        return $stats;
    }

    // --- CÁC HÀM CŨ GIỮ NGUYÊN ---

    public function isRoomAvailable($resourceId, $date, $slotId) {
        $sql = "SELECT COUNT(*) FROM bookings 
                WHERE resource_id = ? AND booking_date = ? AND slot_id = ? 
                AND status IN ('pending', 'approved')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$resourceId, $date, $slotId]);
        return $stmt->fetchColumn() == 0;
    }

    public function create($data) {
        $status = $data['status'] ?? 'pending';
        $sql = "INSERT INTO bookings (user_id, resource_id, slot_id, booking_date, booked_for_name, status, reason, created_at) 
                VALUES (:user_id, :resource_id, :slot_id, :booking_date, :booked_for_name, :status, :reason, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':user_id'         => $data['user_id'],
            ':resource_id'     => $data['resource_id'],
            ':slot_id'         => $data['slot_id'],
            ':booking_date'    => $data['booking_date'],
            ':booked_for_name' => $data['booked_for_name'] ?? '',
            ':status'          => $status,
            ':reason'          => $data['reason'] ?? 'Đặt lịch học tập/sinh hoạt'
        ]);
    }

    public function updateStatus($bookingId, $status) {
        $sql = "UPDATE bookings SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id' => $bookingId
        ]);
    }

    public function findByUserId($userId) {
        $sql = "SELECT b.*, r.name as resource_name, r.location as resource_location, s.label as slot_name, s.start_time, s.end_time 
                FROM bookings b
                JOIN resources r ON b.resource_id = r.id
                JOIN time_slots s ON b.slot_id = s.id
                WHERE b.user_id = ?
                ORDER BY b.booking_date DESC, s.start_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $sql = "SELECT * FROM bookings WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findPendingByUserAndSlot($resourceId, $date, $slotId) {
        $sql = "SELECT b.*, u.role FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.resource_id = ? AND b.booking_date = ? AND b.slot_id = ? 
                AND b.status = 'pending' AND u.role = 'user'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$resourceId, $date, $slotId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllByResourceIds($resourceIds) {
        if (empty($resourceIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
        $sql = "SELECT b.*, u.fullname, r.name as resource_name, s.label as slot_name 
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN resources r ON b.resource_id = r.id
                JOIN time_slots s ON b.slot_id = s.id
                WHERE b.resource_id IN ($placeholders)
                ORDER BY b.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($resourceIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}