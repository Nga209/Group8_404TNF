<?php

// 1. NHÓM TEST & DEBUG
$router->get('/', function () {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Backend is running! Thử truy cập /test-db xem sao.']);
});

$router->get('/test-db', function () {
    header('Content-Type: application/json');
    try {
        $db = new Database();
        $conn = $db->connect(); 
        $stmt = $conn->query("SELECT COUNT(*) AS total_users FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'status' => 'success', 
            'message' => 'Kết nối database thành công!',
            'total_users' => $result['total_users']
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Lỗi kết nối: ' . $e->getMessage()
        ]);
    }
});

$router->post('/mock-emails/clear', function () {
    header('Content-Type: application/json');
    $file = __DIR__ . '/../public/mock_emails.json';
    if (file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }
    echo json_encode(['status' => 'success', 'message' => 'Cleared mock emails successfully.']);
});

// 2. CHỨC NĂNG ĐẶT CHỖ (BOOKINGS)
$router->get('/available-slots', 'BookingController@getAvailableSlots');
$router->post('/bookings', 'BookingController@store');
$router->get('/my-bookings', 'BookingController@myBookings');
$router->post('/bookings/cancel', 'BookingController@cancel');

// 3. XÁC THỰC & THÔNG TIN CÁ NHÂN (AUTH & USER PROFILE)
$router->post('/login', 'AuthController@login');
$router->post('/register', 'AuthController@register');
$router->post('/user/update-profile', 'UserController@updateProfile');
$router->post('/user/upload-avatar', 'UserController@uploadAvatar');
$router->post('/forgot-password', 'AuthController@forgotPassword');
$router->post('/reset-password', 'AuthController@resetPassword');

// 4. TÀI NGUYÊN & KHUNG GIỜ (RESOURCES & TIME SLOTS)
$router->get('/resources', 'ResourceController@getAll');
$router->get('/time-slots', 'TimeSlotController@index');

// 5. ADMIN & BÁO CÁO
$router->get('/approvals', 'ApprovalController@index');
$router->post('/bookings/approve', 'ApprovalController@approve');
$router->get('/admin/stats', 'ReportController@getSummary');
$router->get('/reports/summary', 'ReportController@getSummary');

// Admin - Quản lý tài khoản
$router->get('/users', 'UserController@index');
$router->post('/admin/users/create', 'UserController@create');
$router->post('/admin/users/update-status', 'UserController@updateStatus');
$router->post('/admin/users/update-role', 'UserController@updateRole');
$router->get('/admin/teacher-resources', 'UserController@getTeacherResources');
$router->post('/admin/teacher-resources/assign', 'UserController@assignTeacherResources');

// Admin - Quản lý tài nguyên
$router->post('/admin/resources', 'ResourceController@store');
$router->post('/admin/resources/update', 'ResourceController@update');
$router->post('/admin/resources/delete', 'ResourceController@delete');

// 6. THÔNG BÁO (NOTIFICATIONS)
$router->get('/notifications', 'NotificationController@getMyNotifications');
$router->post('/notifications/read', 'NotificationController@markAsRead');

// 7. REAL-TIME (SSE)
$router->get('/sse/updates', 'SSEController@stream');