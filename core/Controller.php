<?php
class Controller {
    // Chứa logic chung cho các Controller con
    // Kiểm tra quyền hạn của người dùng (Admin/Teacher/Student)
    public function checkRole($allowedRoles = []) {
        if (!isset($_SESSION['user'])) {
            $this->error("Vui lòng đăng nhập để thực hiện hành động này", 401);
            exit();
        }

        $userRole = $_SESSION['user']['role'];
        if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
            $this->error("Bạn không có quyền thực hiện hành động này", 403);
            exit();
        }

        return $_SESSION['user'];
    }

    public function validate($data, $rules) {
        // Bạn có thể viết logic kiểm tra dữ liệu trống ở đây
        foreach ($rules as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    // Gửi phản hồi thành công nhanh
    public function success($data = [], $message = "Success") {
        $response = new Response();
        $response->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    // Gửi phản hồi lỗi nhanh
    public function error($message = "Error", $code = 400) {
        $response = new Response();
        $response->setStatusCode($code);
        $response->json([
            'status' => 'error',
            'message' => $message
        ]);
    }
}