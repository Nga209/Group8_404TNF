<?php

class AuthService {
    private $userRepo;

    public function __construct() {
        // Khởi tạo Repository để truy vấn dữ liệu
        $this->userRepo = new UserRepository();
    }

    /**
     * Xác thực: nhận Email, Mật khẩu và Role từ Frontend gửi lên
     */
    public function authenticate($email, $password, $role) {
        // Tìm user theo Email và Role trong Database
        $user = $this->userRepo->findByEmailAndRole($email, $role);

        // So sánh trực tiếp mật khẩu thuần vì bạn đang lưu '123456'
        if ($user && $password === $user['password']) {
            unset($user['password']); // Xóa pass trước khi trả về để bảo mật
            return $user;
        }

        return false;
    }
}