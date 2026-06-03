<?php

class UserRepository {
    /** @var PDO */
    private $db;

    public function __construct() {
        $database = new Database();
        // Đã sửa từ getConnection() thành connect() cho khớp với file Database.php của bạn
        $this->db = $database->connect(); 
    }

    /**
     * Lấy tất cả người dùng (Dùng cho trang quản lý)
     */
    public function getAll() {
        try {
            $query = "SELECT u.id, u.username, u.fullname, u.email, u.role, u.status, u.avatar, u.created_at,
                        (SELECT GROUP_CONCAT(r.name SEPARATOR ', ') 
                         FROM teacher_resources tr 
                         JOIN resources r ON tr.resource_id = r.id 
                         WHERE tr.teacher_id = u.id) as assigned_resources
                      FROM users u";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * HÀM QUAN TRỌNG: Tìm theo Email và Role
     * Giúp đăng nhập đúng vai trò đã chọn trên giao diện
     */
    public function findByEmailAndRole($email, $role) {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1";
            $stmt = $this->db->prepare($query);
            
            // Thực thi với tham số an toàn chống SQL Injection
            $stmt->execute([
                ':email' => $email,
                ':role'  => $role 
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Tìm theo Username (Dùng khi cần kiểm tra trùng lặp hoặc lấy profile)
     */
    public function findByUsername($username) {
        try {
            $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':username' => $username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Tạo tài khoản người dùng mới
     */
    public function create($data) {
        try {
            $query = "INSERT INTO users (username, password, fullname, email, role, status, avatar) 
                      VALUES (:username, :password, :fullname, :email, :role, 1, :avatar)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':username' => $data['username'],
                ':password' => $data['password'], // đã hash ở controller/service
                ':fullname' => $data['fullname'],
                ':email'    => $data['email'],
                ':role'     => $data['role'] ?? 'user',
                ':avatar'   => $data['avatar'] ?? null
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Kiểm tra trùng Email
     */
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Kiểm tra trùng Username
     */
    public function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile($userId, $fullname, $email, $hashedPassword = null, $avatar = null) {
        try {
            $fields = "fullname = :fullname, email = :email";
            $params = [
                ':fullname' => $fullname,
                ':email'    => $email,
                ':id'       => $userId
            ];

            if ($hashedPassword) {
                $fields .= ", password = :password";
                $params[':password'] = $hashedPassword;
            }

            if ($avatar) {
                $fields .= ", avatar = :avatar";
                $params[':avatar'] = $avatar;
            }

            $query = "UPDATE users SET $fields WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Cập nhật trạng thái người dùng (Khóa/Mở khóa)
     */
    public function updateStatus($userId, $status) {
        try {
            $query = "UPDATE users SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':status' => intval($status),
                ':id'     => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Cập nhật vai trò người dùng (Chỉ dành cho Admin)
     */
    public function updateRole($userId, $role) {
        try {
            $query = "UPDATE users SET role = :role WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':role' => $role,
                ':id'   => $userId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
}