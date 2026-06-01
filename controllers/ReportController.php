<?php
class ReportController extends Controller {
    public function getSummary() {
        $user = $this->checkRole(['admin', 'teacher']);
        $db = (new Database())->connect();
        
        $resourceIds = [];
        $isTeacher = ($user['role'] === 'teacher');
        
        if ($isTeacher) {
            $stmt = $db->prepare("SELECT resource_id FROM teacher_resources WHERE teacher_id = ?");
            $stmt->execute([$user['id']]);
            $resourceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($resourceIds)) {
                $emptyStats = [
                    'overview' => [
                        'total_bookings' => 0,
                        'today_count' => 0,
                        'yesterday_count' => 0,
                        'pending_requests' => 0,
                        'approved_requests' => 0,
                        'rejected_requests' => 0,
                        'total_users' => 0
                    ],
                    'daily_stats' => [],
                    'user_type_distribution' => [],
                    'top_users' => [],
                    'top_resources' => [],
                    'slot_usage_stats' => [],
                    'recent_activity' => []
                ];
                return $this->success($emptyStats, "Giảng viên chưa được phân công phòng nào");
            }
        }

        // 1. Overview stats
        if ($isTeacher) {
            $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
            $overviewSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected
                FROM bookings
                WHERE YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())
                AND resource_id IN ($placeholders)";
            $stmt = $db->prepare($overviewSql);
            $stmt->execute($resourceIds);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmtToday = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE() AND resource_id IN ($placeholders)");
            $stmtToday->execute($resourceIds);
            $todayCount = (int)$stmtToday->fetchColumn();

            $stmtYest = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND resource_id IN ($placeholders)");
            $stmtYest->execute($resourceIds);
            $yesterdayCount = (int)$stmtYest->fetchColumn();

            $stmtUsers = $db->prepare("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE resource_id IN ($placeholders)");
            $stmtUsers->execute($resourceIds);
            $totalUsers = (int)$stmtUsers->fetchColumn();
        } else {
            $overviewSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected
                FROM bookings
                WHERE YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())";
            $res = $db->query($overviewSql)->fetch(PDO::FETCH_ASSOC);

            $todayCount = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()")->fetchColumn();
            $yesterdayCount = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
            $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
        }

        // 2. Daily Stats
        if ($isTeacher) {
            $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
            $stmtDaily = $db->prepare("
                SELECT DAYNAME(booking_date) as day, COUNT(id) as count 
                FROM bookings 
                WHERE YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())
                AND resource_id IN ($placeholders)
                GROUP BY DAYOFWEEK(booking_date)
                ORDER BY DAYOFWEEK(booking_date)
            ");
            $stmtDaily->execute($resourceIds);
            $dailyStats = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $dailyStats = $db->query("
                SELECT DAYNAME(booking_date) as day, COUNT(id) as count 
                FROM bookings 
                WHERE YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())
                GROUP BY DAYOFWEEK(booking_date)
                ORDER BY DAYOFWEEK(booking_date)
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 3. User Type Distribution
        if ($isTeacher) {
            $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
            $stmtDist = $db->prepare("
                SELECT u.role, COUNT(b.id) as count 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE YEAR(b.booking_date) = YEAR(CURDATE()) AND MONTH(b.booking_date) = MONTH(CURDATE())
                AND b.resource_id IN ($placeholders)
                GROUP BY u.role
            ");
            $stmtDist->execute($resourceIds);
            $userTypeDist = $stmtDist->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $userTypeDist = $db->query("
                SELECT u.role, COUNT(b.id) as count 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE YEAR(b.booking_date) = YEAR(CURDATE()) AND MONTH(b.booking_date) = MONTH(CURDATE())
                GROUP BY u.role
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 4. Top Users
        if ($isTeacher) {
            $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
            $stmtTopUsers = $db->prepare("
                SELECT booked_for_name, COUNT(id) as total_bookings 
                FROM bookings 
                WHERE booked_for_name IS NOT NULL AND booked_for_name != ''
                AND YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())
                AND resource_id IN ($placeholders)
                GROUP BY booked_for_name 
                ORDER BY total_bookings DESC 
                LIMIT 5
            ");
            $stmtTopUsers->execute($resourceIds);
            $topUsers = $stmtTopUsers->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $topUsers = $db->query("
                SELECT booked_for_name, COUNT(id) as total_bookings 
                FROM bookings 
                WHERE booked_for_name IS NOT NULL AND booked_for_name != ''
                AND YEAR(booking_date) = YEAR(CURDATE()) AND MONTH(booking_date) = MONTH(CURDATE())
                GROUP BY booked_for_name 
                ORDER BY total_bookings DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 5. Top Resources
        if ($isTeacher) {
            $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
            $stmtTopResources = $db->prepare("
                SELECT r.name, COUNT(b.id) as total_usage 
                FROM resources r 
                LEFT JOIN bookings b ON r.id = b.resource_id 
                AND YEAR(b.booking_date) = YEAR(CURDATE()) AND MONTH(b.booking_date) = MONTH(CURDATE())
                WHERE r.id IN ($placeholders)
                GROUP BY r.id 
                ORDER BY total_usage DESC 
                LIMIT 5
            ");
            $stmtTopResources->execute($resourceIds);
            $topResources = $stmtTopResources->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $topResources = $db->query("
                SELECT r.name, COUNT(b.id) as total_usage 
                FROM resources r 
                LEFT JOIN bookings b ON r.id = b.resource_id 
                AND YEAR(b.booking_date) = YEAR(CURDATE()) AND MONTH(b.booking_date) = MONTH(CURDATE())
                GROUP BY r.id 
                ORDER BY total_usage DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 6. Slot Usage Stats
        if ($isTeacher) {
            $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
            $stmtSlot = $db->prepare("
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
                    AND b.resource_id IN ($placeholders)
            WHERE ts.is_active = 1
            GROUP BY ts.id
            ORDER BY ts.start_time
            ");
            $stmtSlot->execute($resourceIds);
            $slotUsageStats = $stmtSlot->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $slotUsageStats = $db->query("
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
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 7. Recent Activity
        if ($isTeacher) {
            $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
            $stmtRecent = $db->prepare("
                SELECT b.id, b.booked_for_name, u.fullname, r.name as resource_name, b.status, b.created_at 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN resources r ON b.resource_id = r.id 
                WHERE b.resource_id IN ($placeholders)
                ORDER BY b.created_at DESC 
                LIMIT 10
            ");
            $stmtRecent->execute($resourceIds);
            $recentActivity = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $recentActivity = $db->query("
                SELECT b.id, b.booked_for_name, u.fullname, r.name as resource_name, b.status, b.created_at 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN resources r ON b.resource_id = r.id 
                ORDER BY b.created_at DESC 
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        $stats = [
            'overview' => [
                'total_bookings' => (int)$res['total'],
                'today_count' => $todayCount,
                'yesterday_count' => $yesterdayCount,
                'pending_requests' => (int)$res['pending'],
                'approved_requests' => (int)$res['approved'],
                'rejected_requests' => (int)$res['rejected'],
                'total_users' => $totalUsers
            ],
            'daily_stats' => $dailyStats,
            'user_type_distribution' => $userTypeDist,
            'top_users' => $topUsers,
            'top_resources' => $topResources,
            'slot_usage_stats' => $slotUsageStats,
            'recent_activity' => $recentActivity
        ];
        
        return $this->success($stats, "Lấy dữ liệu báo cáo thành công");
    }
}