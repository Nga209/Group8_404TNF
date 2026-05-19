<?php

class Router {
    private $routes = [];

    public function get($uri, $callback) {
        $this->routes['GET'][$uri] = $callback;
    }

    public function post($uri, $callback) {
        $this->routes['POST'][$uri] = $callback;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // --- ĐOẠN SỬA LẠI ĐỂ CHẠY ỔN ĐỊNH ---
        // 1. Ưu tiên lấy từ PATH_INFO (nếu gọi kiểu index.php/route)
        if (isset($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        } else {
            // 2. Nếu không có PATH_INFO, lấy từ REQUEST_URI và cắt bỏ phần dư thừa
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            $publicPos = strpos($uri, '/public');
            if ($publicPos !== false) {
                $uri = substr($uri, $publicPos + strlen('/public'));
            }
            
            $uri = str_replace('/index.php', '', $uri);
        }

        // 3. Chuẩn hóa: Luôn có dấu / ở đầu, và xóa dấu / dư thừa ở cuối
        $uri = '/' . trim($uri, '/');
        // -------------------------------------

        if (isset($this->routes[$method][$uri])) {
            $callback = $this->routes[$method][$uri];
            if (is_string($callback)) {
                $parts = explode('@', $callback);
                $controllerName = $parts[0];
                $methodName = $parts[1];

                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    $controller->$methodName();
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(["error" => "Khong tim thay class $controllerName"]);
                }
            } elseif (is_callable($callback)) {
                call_user_func($callback);
            }
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error", 
                "message" => "Route $uri not found",
                "debug_info" => [
                    "method" => $method,
                    "final_uri" => $uri
                ]
            ]);
        }
    }
}