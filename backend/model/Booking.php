<?php
class Booking {
    public $id;
    public $user_id;
    public $resource_id;
    public $slot_id;      // Thay cho start_time/end_time
    public $booking_date; // Thay cho start_time/end_time
    public $status;       // pending, approved, rejected, cancelled
    public $reason;       // Trong ảnh phpMyAdmin của bạn có cột này nè
    public $created_at;
}