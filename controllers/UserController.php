<?php
// Thêm 2 dòng này để VS Code hiểu các Class nằm ở đâu
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class UserController extends Controller {
    private $repo;

    public function __construct() {
        // Khởi tạo Repository
        $this->repo = new UserRepository();
    }

    public function index() {
        // Chỉ Admin mới được xem danh sách tất cả tài khoản
        $this->checkRole(['admin']);
        
        $users = $this->repo->getAll();

        if ($users !== false) {
            return $this->success($users, "Lấy danh sách người dùng thành công");
        } else {
            return $this->error("Không thể lấy dữ liệu người dùng", 500);
        }
    }

    public function updateProfile() {
        $user = $this->checkRole(['user', 'teacher', 'admin']);
        $userId = $user['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $fullname = $input['fullname'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $avatar = $input['avatar'] ?? null;

        if (empty($fullname) || empty($email)) {
            return $this->error("Họ tên và email không được bỏ trống");
        }

        $db = (new Database())->connect();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetchColumn() > 0) {
            return $this->error("Email đã được sử dụng bởi người dùng khác");
        }

        $hashedPassword = null;
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($this->repo->updateProfile($userId, $fullname, $email, $hashedPassword, $avatar)) {
            $_SESSION['user']['fullname'] = $fullname;
            if ($avatar) {
                $_SESSION['user']['avatar'] = $avatar;
            }

            // Gửi thông báo hoạt động
            $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, 'Hồ sơ cá nhân của bạn đã được cập nhật thành công.', 0, NOW())");
            $stmtNotif->execute([$userId]);

            return $this->success([
                'id' => $userId,
                'username' => $_SESSION['user']['username'],
                'role' => $_SESSION['user']['role'],
                'fullname' => $fullname,
                'email' => $email,
                'avatar' => $avatar ?: ($_SESSION['user']['avatar'] ?? null)
            ], "Cập nhật hồ sơ thành công");
        }

        return $this->error("Cập nhật hồ sơ thất bại");
    }

    public function uploadAvatar() {
        $user = $this->checkRole(['user', 'teacher', 'admin']);
        $userId = $user['id'];

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            return $this->error("Không có file nào được tải lên hoặc có lỗi xảy ra");
        }

        $file = $_FILES['avatar'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmpName = $file['tmp_name'];

        // Kiểm tra định dạng ảnh
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions)) {
            return $this->error("Định dạng file không hợp lệ. Chỉ chấp nhận JPG, JPEG, PNG, GIF, WEBP");
        }

        // Kiểm tra dung lượng (giới hạn 2MB)
        if ($fileSize > 2 * 1024 * 1024) {
            return $this->error("Dung lượng ảnh vượt quá giới hạn 2MB");
        }

        // Tạo thư mục lưu trữ nếu chưa có
        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Sinh tên file ngẫu nhiên để tránh trùng lặp
        $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpName, $destPath)) {
            $avatarUrl = '/uploads/avatars/' . $newFileName;

            // Cập nhật vào DB
            $db = (new Database())->connect();
            $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            if ($stmt->execute([$avatarUrl, $userId])) {
                $_SESSION['user']['avatar'] = $avatarUrl;

                // Gửi thông báo hoạt động
                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, 'Ảnh đại diện của bạn đã được cập nhật thành công.', 0, NOW())");
                $stmtNotif->execute([$userId]);

                return $this->success([
                    'avatar' => $avatarUrl
                ], "Tải ảnh đại diện thành công!");
            }
            return $this->error("Lỗi khi lưu ảnh vào cơ sở dữ liệu");
        }

        return $this->error("Không thể di chuyển file đã upload");
    }

    public function updateStatus() {
        $this->checkRole(['admin']);

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $status = $input['status'] ?? null;

        if (!$userId || $status === null) {
            return $this->error("Thiếu thông tin user_id hoặc status");
        }

        if ($this->repo->updateStatus($userId, $status)) {
            return $this->success([], "Cập nhật trạng thái người dùng thành công");
        }
        return $this->error("Cập nhật trạng thái thất bại");
    }

    public function updateRole() {
        $this->checkRole(['admin']);

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $role = $input['role'] ?? null;

        if (!$userId || !$role) {
            return $this->error("Thiếu thông tin user_id hoặc role");
        }

        if ($role !== 'admin' && $role !== 'teacher' && $role !== 'user') {
            return $this->error("Vai trò không hợp lệ");
        }

        if ($this->repo->updateRole($userId, $role)) {
            return $this->success([], "Cập nhật vai trò người dùng thành công");
        }
        return $this->error("Cập nhật vai trò thất bại");
    }

    public function create() {
        $this->checkRole(['admin']);

        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $fullname = $input['fullname'] ?? '';
        $role = $input['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($password) || empty($fullname)) {
            return $this->error("Vui lòng nhập đầy đủ các trường thông tin");
        }

        if ($this->repo->usernameExists($username)) {
            return $this->error("Tên đăng nhập đã được sử dụng");
        }

        if ($this->repo->emailExists($email)) {
            return $this->error("Email đã được sử dụng");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $success = $this->repo->create([
            'username' => $username,
            'password' => $hashedPassword,
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role
        ]);

        if ($success) {
            return $this->success([], "Tạo tài khoản người dùng thành công");
        }
        return $this->error("Lỗi khi tạo tài khoản");
    }

    public function getTeacherResources() {
        $this->checkRole(['admin']);
        
        $teacherId = $_GET['teacher_id'] ?? null;
        if (!$teacherId) {
            return $this->error("Thiếu thông tin teacher_id");
        }

        $db = (new Database())->connect();
        $stmt = $db->prepare("SELECT resource_id FROM teacher_resources WHERE teacher_id = ?");
        $stmt->execute([$teacherId]);
        $resourceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $this->success($resourceIds, "Lấy danh sách tài nguyên phân công thành công");
    }

    public function assignTeacherResources() {
        $this->checkRole(['admin']);

        $input = json_decode(file_get_contents('php://input'), true);
        $teacherId = $input['teacher_id'] ?? null;
        $resourceIds = $input['resource_ids'] ?? [];

        if (!$teacherId) {
            return $this->error("Thiếu thông tin teacher_id");
        }

        $db = (new Database())->connect();
        try {
            $db->beginTransaction();

            $stmtDel = $db->prepare("DELETE FROM teacher_resources WHERE teacher_id = ?");
            $stmtDel->execute([$teacherId]);

            if (!empty($resourceIds)) {
                $stmtIns = $db->prepare("INSERT INTO teacher_resources (teacher_id, resource_id) VALUES (?, ?)");
                foreach ($resourceIds as $resId) {
                    $stmtIns->execute([$teacherId, $resId]);
                }
            }

            $db->commit();
            return $this->success([], "Cập nhật phân công tài nguyên cho Giảng viên thành công");
        } catch (Exception $e) {
            $db->rollBack();
            return $this->error("Lỗi hệ thống khi cập nhật phân công: " . $e->getMessage(), 500);
        }
    }
}