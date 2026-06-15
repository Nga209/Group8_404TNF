<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DEVELOPMENT_MODE', true); // Đặt là true để kiểm thử ở localhost (Mock mode). Đặt thành false khi chạy thực tế (Production).

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); 
define('DB_NAME', 'campus_booking');

// Thêm INS3064 vào đây
define('BASE_URL', 'http://localhost/INS3064/campus_services_booking/backend/public');

// Cấu hình gửi Mail (SMTP Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'campusbook.test.vn@gmail.com'); // Gmail gửi thư
define('SMTP_PASS', 'your_app_password_here');      // Mật khẩu ứng dụng Gmail (16 ký tự)
define('SMTP_SECURE', 'tls');                      // Phương thức bảo mật: tls hoặc ssl
define('SMTP_FROM', 'campusbook.test.vn@gmail.com');
define('SMTP_FROM_NAME', 'CampusBook Service');

// Cấu hình API Key cho Trợ lý AI (Gemini 2.5 Flash)
// Bạn có thể lấy API Key miễn phí tại Google AI Studio: https://aistudio.google.com/
define('GEMINI_API_KEY', 'AIzaSyDxS0Em7KpgVJUuuuvGAtog8AkWTPx4REQ');