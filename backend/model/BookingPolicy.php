<?php
class BookingPolicy {
    public $id;
    public $resource_type;
    public $max_duration; // số phút tối đa
    public $min_notice;   // thời gian đặt trước tối thiểu
}