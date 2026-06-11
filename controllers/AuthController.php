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
            
            // Sinh mã xác nhận (OTP) 6 chữ số ngẫu nhiên
            $otpCode = (string)rand(100000, 999999);
            
            // Cập nhật mã xác nhận và thời gian hết hạn (5 phút) vào DB
            $stmtUpdate = $db->prepare("UPDATE users SET reset_code = ?, reset_code_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?");
            $success = $stmtUpdate->execute([$otpCode, $user['id']]);
            
            if ($success) {
                // Tạo thông báo hoạt động cho user đó
                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                $stmtNotif->execute([$user['id'], "Mã xác thực khôi phục mật khẩu của bạn đã được gửi."]);
                
                // Chuẩn bị nội dung email gửi đến người dùng
                $subject = "[CampusBook] Mã xác nhận khôi phục mật khẩu";
                $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                        <h2 style='color: #8b5cf6; margin-top: 0;'>Khôi phục mật khẩu tài khoản</h2>
                        <p>Chào <strong>" . htmlspecialchars($user['fullname']) . "</strong>,</p>
                        <p>Bạn đã yêu cầu khôi phục mật khẩu trên hệ thống đặt lịch dịch vụ CampusBook.</p>
                        <p>Mã OTP xác nhận của bạn là:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <span style='font-family: monospace; font-size: 2rem; font-weight: bold; color: #8b5cf6; background: #f3e8ff; padding: 10px 30px; border-radius: 8px; border: 1px dashed #8b5cf6; letter-spacing: 4px; display: inline-block;'>$otpCode</span>
                        </div>
                        <p style='color: #ef4444;'>Mã này có hiệu lực trong vòng 5 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai khác.</p>
                        <p style='margin-top: 30px; font-size: 0.9rem; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 15px;'>
                            Nếu bạn không yêu cầu hành động này, vui lòng bỏ qua email hoặc liên hệ với ban quản trị hệ thống.
                        </p>
                        <p style='font-weight: bold; margin-bottom: 0;'>Đội ngũ CampusBook</p>
                    </div>
                ";
                
                // Thực hiện gửi email
                $mailResult = Mailer::send($email, $subject, $body);
                
                if ($mailResult['success']) {
                    echo json_encode([
                        "status" => "success",
                        "message" => "Mã xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra inbox (và hòm thư spam).",
                        "mode" => $mailResult['mode'],
                        // Nếu là chế độ giả lập, trả về code để frontend hiển thị hỗ trợ kiểm thử tiện lợi
                        "code" => ($mailResult['mode'] === 'mock') ? $otpCode : null
                    ]);
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Lỗi khi gửi email qua SMTP: " . $mailResult['error']
                    ]);
                }
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Lỗi hệ thống, không thể tạo mã xác thực khôi phục mật khẩu!"
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Lỗi kết nối database: " . $e->getMessage()
            ]);
        }
    }

    public function resetPassword() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $code = $input['code'] ?? '';
        $newPassword = $input['password'] ?? '';
        
        if (empty($email) || empty($code) || empty($newPassword)) {
            echo json_encode([
                "status" => "error",
                "message" => "Vui lòng điền đầy đủ các thông tin: Email, mã xác thực và mật khẩu mới!"
            ]);
            return;
        }
        
        try {
            $db = (new Database())->connect();
            
            // Kiểm tra xem mã xác thực có đúng và còn hiệu lực (chưa quá 5 phút)
            $stmt = $db->prepare("SELECT id, fullname FROM users WHERE email = ? AND reset_code = ? AND reset_code_expires > NOW()");
            $stmt->execute([$email, $code]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Mã xác thực không chính xác hoặc đã hết hạn!"
                ]);
                return;
            }
            
            // Cập nhật mật khẩu mới và xóa mã OTP
            $hashedPass = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtUpdate = $db->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE id = ?");
            $success = $stmtUpdate->execute([$hashedPass, $user['id']]);
            
            if ($success) {
                // Tạo thông báo hoạt động cho user
                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                $stmtNotif->execute([$user['id'], "Khôi phục mật khẩu thành công bằng mã xác thực email. Mật khẩu mới đã được cập nhật."]);
                
                echo json_encode([
                    "status" => "success",
                    "message" => "Đặt lại mật khẩu mới thành công! Vui lòng đăng nhập bằng mật khẩu mới của bạn."
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