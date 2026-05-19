<?php

class BookingController extends Controller {
    private $service;

    public function __construct() {
        $this->service = new BookingService();
    }

    public function store() {
        // Lấy dữ liệu từ Request
        $request = new Request();
        $input = $request->getBody();

        // Kiểm tra đầu vào cơ bản
        if (!isset($input['resource_id'], $input['booking_date'], $input['slot_id'], $input['user_id'])) {
            return $this->error("Dữ liệu không đầy đủ (cần resource_id, booking_date, slot_id, user_id)");
        }

        // Đảm bảo user_id là số hợp lệ
        $input['user_id'] = intval($input['user_id']);
        if ($input['user_id'] <= 0) {
            return $this->error("user_id không hợp lệ. Vui lòng đăng nhập lại.");
        }

        // Gọi Service xử lý nghiệp vụ
        $result = $this->service->makeBooking($input);

        if ($result['status'] === 'success') {
            return $this->success([], $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
}