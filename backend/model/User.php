<?php
// File: User.php

class User {
    // Các thuộc tính phải khớp 100% với tên cột trong phpMyAdmin (hình image_a38e25.png)
    public $id;
    public $username;
    public $password;
    public $email;
    public $fullname; // Đã sửa từ full_name thành fullname cho khớp DB
    public $role;     // admin, student, lecturer (như đã thống nhất thay cho user/teacher)
    public $status;
    public $created_at;

    /**
     * Hàm xử lý đăng nhập logic: Kiểm tra Email hoặc Username
     * Giúp user dùng "tên đăng ký" vẫn vào được hệ thống
     */
    public function login($db, $identity, $password) {
        try {
            // Câu lệnh SQL chuẩn để tránh lỗi "Unknown column"
            $sql = "SELECT * FROM users WHERE email = :identity OR username = :identity LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':identity', $identity);
            $stmt->execute();
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData && $password === $userData['password']) {
                // Đổ dữ liệu vào object hiện tại
                $this->id = $userData['id'];
                $this->username = $userData['username'];
                $this->fullname = $userData['fullname'];
                $this->role = $userData['role'];
                
                // Lưu vào Session để khi "Book phòng" sẽ tự hiện tên thật
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $this->id;
                $_SESSION['fullname'] = $this->fullname; // Tên thật dùng để hiển thị
                $_SESSION['role'] = $this->role;

                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Lỗi Login: " . $e->getMessage());
            return false;
        }
    }
}
?>