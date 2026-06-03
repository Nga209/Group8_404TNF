<?php
class ReportController extends Controller {
    public function getSummary() {
        // Chỉ Admin và Teacher mới được xem báo cáo tổng quát
        $this->checkRole(['admin', 'teacher']);

        $db = (new Database())->connect();
        
        // Thống kê theo tháng hiện tại
        $overviewSql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected
            FROM bookings
            WHERE YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())";
        
        $res = $db->query($overviewSql)->fetch(PDO::FETCH_ASSOC);

        $stats = [
            'overview' => [
                'total_bookings' => (int)$res['total'],
                'today_count' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()")->fetchColumn(),
                'yesterday_count' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn(),
                'pending_requests' => (int)$res['pending'],
                'approved_requests' => (int)$res['approved'],
                'rejected_requests' => (int)$res['rejected']
            ],
            'daily_stats' => $db->query("
                SELECT DAYNAME(booking_date) as day, COUNT(id) as count 
                FROM bookings 
                WHERE YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())
                GROUP BY DAYOFWEEK(booking_date)
                ORDER BY DAYOFWEEK(booking_date)
            ")->fetchAll(PDO::FETCH_ASSOC),
            'user_type_distribution' => $db->query("
                SELECT u.role, COUNT(b.id) as count 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE YEAR(b.booking_date) = YEAR(CURDATE()) AND MONTH(b.booking_date) = MONTH(CURDATE())
                GROUP BY u.role
            ")->fetchAll(PDO::FETCH_ASSOC),
            'top_users' => $db->query("
                SELECT booked_for_name, COUNT(id) as total_bookings 
                FROM bookings 
                WHERE booked_for_name IS NOT NULL AND booked_for_name != ''
                AND YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())
                GROUP BY booked_for_name 
                ORDER BY total_bookings DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC),
            'top_resources' => $db->query("
                SELECT r.name, COUNT(b.id) as total_usage 
                FROM resources r 
                LEFT JOIN bookings b ON r.id = b.resource_id 
                AND YEAR(b.booking_date) = YEAR(CURDATE()) AND MONTH(b.booking_date) = MONTH(CURDATE())
                GROUP BY r.id 
                ORDER BY total_usage DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC),
            'slot_usage_stats' => $db->query("
                SELECT 
                    ts.label,
                    ts.start_time,
                    ts.is_peak,
                    ts.slot_type,
                    COUNT(b.id) as total_bookings
                FROM time_slots ts
                LEFT JOIN bookings b ON b.slot_id = ts.id
                    AND YEAR(b.booking_date) = YEAR(CURDATE())
                    AND MONTH(b.booking_date) = MONTH(CURDATE())
                WHERE ts.is_active = 1
                GROUP BY ts.id
                ORDER BY ts.start_time
            ")->fetchAll(PDO::FETCH_ASSOC),
            'recent_activity' => $db->query("
                SELECT b.id, b.booked_for_name, u.fullname, r.name as resource_name, b.status, b.created_at 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN resources r ON b.resource_id = r.id 
                ORDER BY b.created_at DESC 
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC)
        ];
        
        return $this->success($stats, "Lấy dữ liệu báo cáo thành công");
    }
}