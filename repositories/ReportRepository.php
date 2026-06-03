<?php
class ReportRepository {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getUsageStats() {
        $query = "SELECT r.name, COUNT(b.id) as total_bookings, 
                  SUM(TIMESTAMPDIFF(HOUR, b.start_time, b.end_time)) as total_hours
                  FROM resources r
                  LEFT JOIN bookings b ON r.id = b.resource_id
                  WHERE b.status = 'approved' OR b.status IS NULL
                  GROUP BY r.id";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSystemSummary() {
        $stats = [];
        $stats['total_users'] = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_bookings'] = $this->db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        $stats['pending_bookings'] = $this->db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
        return $stats;
    }
}