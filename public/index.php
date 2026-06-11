<?php
session_start();
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");

if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
} else {
    header("Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With");
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';

// 1. Tự động nạp các Class (Sửa đường dẫn tìm trong app/core)
spl_autoload_register(function ($class_name) {
    $backendRoot = realpath(__DIR__ . '/../');
    $dirs = [
        $backendRoot . '/app/core/',        // Đã sửa
        $backendRoot . '/app/controllers/',
        $backendRoot . '/app/repositories/',
        $backendRoot . '/app/models/',
        $backendRoot . '/app/services/'
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 2. Tìm và nạp file Router.php (Sửa đường dẫn vào app/core)
$routerFile = realpath(__DIR__ . '/../app/core/Router.php');

if ($routerFile && file_exists($routerFile)) {
    require_once $routerFile;
} else {
    // Nếu vẫn lỗi, nó sẽ báo đường dẫn này để bạn kiểm tra folder
    die("Lỗi: Không tìm thấy file Router.php tại: " . __DIR__ . '/../app/core/Router.php');
}

// 3. Khởi tạo và chạy Router
$router = new Router();

// Load Routes (Đảm bảo file api.php nằm đúng trong backend/routes/)
require_once __DIR__ . '/../routes/api.php';

$router->dispatch();