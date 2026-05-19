<?php
class Request {
    // Lấy phương thức hiện tại (GET, POST...)
    public function getMethod() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    // Lấy đường dẫn URL
    public function getPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    // Lấy toàn bộ dữ liệu gửi lên (tự động xử lý JSON)
    public function getBody() {
        $body = [];
        if ($this->getMethod() === 'get') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($this->getMethod() === 'post') {
            // Xử lý dữ liệu JSON từ body
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $body = $input;
            } else {
                foreach ($_POST as $key => $value) {
                    $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                }
            }
        }
        return $body;
    }
}