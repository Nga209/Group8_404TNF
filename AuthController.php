<?php
class AuthController {
    public function login() {
        // Luôn trả về định dạng JSON
        header('Content-Type: application/json');
        
        // 1. LẤY DỮ LIỆU: Đọc từ JSON Body (do Frontend gửi qua fetch/axios)
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Hỗ trợ cả trường hợp gửi qua Form Data truyền thống
        $email = $input['email'] ?? ($_POST['email'] ?? '');
        $password = $input['password'] ?? ($_POST['password'] ?? '');
        $role = $input['role'] ?? ($_POST['role'] ?? '');

        // Kiểm tra dữ liệu đầu vào cơ bản
        if (empty($email) || empty($password)) {
            echo json_encode([
                "status" => "error", 
                "message" => "Vui lòng nhập đầy đủ email và mật khẩu!"
            ]);
            return;
        }

        $userRepo = new UserRepository();
        
        // 2. TÌM KIẾM: Sử dụng hàm findByEmailAndRole bạn đã viết
        // Nó sẽ check đúng Email và đúng cái Role (Sinh viên/Giảng viên) bạn chọn
        $user = $userRepo->findByEmailAndRole($email, $role);

        // 3. KIỂM TRA MẬT KHẨU
        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            // Đăng nhập thành công - Lưu vào Session
            $_SESSION['user'] = [
                "id" => $user['id'],
                "username" => $user['username'],
                "role" => $user['role'],
                "fullname" => $user['fullname']
            ];

            echo json_encode([
                "status" => "success",
                "message" => "Chào mừng " . $user['fullname'],
                "user" => $_SESSION['user']
            ]);
        } else {
            // Thất bại
            echo json_encode([
                "status" => "error", 
                "message" => "Đăng nhập thất bại: Sai tài khoản, mật khẩu hoặc vai trò!"
            ]);
        }
    }
}