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
        
        // Kiểm tra email tồn tại trước
        if (!$userRepo->emailExists($email)) {
            echo json_encode([
                "status" => "error", 
                "message" => "Tài khoản không tồn tại. Vui lòng đăng ký trước."
            ]);
            return;
        }
        
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
                "fullname" => $user['fullname'],
                "avatar" => $user['avatar'] ?? null
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
                "message" => "Đăng nhập thất bại: Sai mật khẩu hoặc vai trò đã chọn!"
            ]);
        }
    }

    public function register() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $fullname = $input['fullname'] ?? '';
        $role = $input['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($password) || empty($fullname)) {
            echo json_encode([
                "status" => "error",
                "message" => "Vui lòng nhập đầy đủ các trường thông tin!"
            ]);
            return;
        }

        // Chỉ cho phép đăng ký vai trò 'user' hoặc 'teacher'
        if ($role !== 'user' && $role !== 'teacher') {
            $role = 'user';
        }

        $userRepo = new UserRepository();

        if ($userRepo->usernameExists($username)) {
            echo json_encode([
                "status" => "error",
                "message" => "Tên đăng nhập đã được sử dụng!"
            ]);
            return;
        }

        if ($userRepo->emailExists($email)) {
            echo json_encode([
                "status" => "error",
                "message" => "Email đã được đăng ký!"
            ]);
            return;
        }

        // Mã hóa mật khẩu an toàn
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $success = $userRepo->create([
            'username' => $username,
            'password' => $hashedPassword,
            'fullname' => $fullname,
            'email'    => $email,
            'role'     => $role
        ]);

        if ($success) {
            echo json_encode([
                "status" => "success",
                "message" => "Đăng ký tài khoản thành công!"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Đã xảy ra lỗi trong quá trình tạo tài khoản!"
            ]);
        }
    }

    public function forgotPassword() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        
        if (empty($email)) {
            echo json_encode([
                "status" => "error",
                "message" => "Vui lòng nhập email tài khoản!"
            ]);
            return;
        }
        
        try {
            $db = (new Database())->connect();
            $stmt = $db->prepare("SELECT id, fullname FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Không tìm thấy tài khoản nào được đăng ký với email này!"
                ]);
                return;
            }
            
            // Sinh mật khẩu mới ngẫu nhiên
            $newPass = "Campus@" . rand(1000, 9999);
            $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
            
            $stmtUpdate = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $success = $stmtUpdate->execute([$hashedPass, $user['id']]);
            
            if ($success) {
                // Tạo thông báo hoạt động cho user đó
                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                $stmtNotif->execute([$user['id'], "Hệ thống đã reset mật khẩu của bạn thành mật khẩu tạm mới theo yêu cầu khôi phục."]);
                
                echo json_encode([
                    "status" => "success",
                    "message" => "Khôi phục mật khẩu thành công!",
                    "new_password" => $newPass
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Lỗi hệ thống, không thể cập nhật mật khẩu mới!"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Lỗi kết nối database: " . $e->getMessage()
            ]);
        }
    }
}