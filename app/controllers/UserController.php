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
        // Gọi hàm getAll từ Repository
        $users = $this->repo->getAll();

        if ($users !== false) {
            return $this->success($users, "Lấy danh sách người dùng thành công");
        } else {
            return $this->error("Không thể lấy dữ liệu người dùng", 500);
        }
    }
}