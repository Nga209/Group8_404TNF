<?php

class Product {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Khởi tạo dữ liệu mẫu nếu chưa có
        if (!isset($_SESSION['products'])) {
            $_SESSION['products'] = [
                ['id' => 1, 'name' => 'iPhone 15 Pro', 'price' => 28000000, 'category' => 'Điện thoại'],
                ['id' => 2, 'name' => 'MacBook Air M2', 'price' => 24000000, 'category' => 'Laptop'],
                ['id' => 3, 'name' => 'Sony WH-1000XM5', 'price' => 8500000, 'category' => 'Tai nghe']
            ];
        }
    }

    public function getAll() {
        return $_SESSION['products'];
    }

    public function create($data) {
        $newId = count($_SESSION['products']) > 0 ? max(array_column($_SESSION['products'], 'id')) + 1 : 1;
        $data['id'] = $newId;
        $_SESSION['products'][] = $data;
        return true;
    }
}
