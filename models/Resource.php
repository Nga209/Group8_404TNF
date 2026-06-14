<?php
class Resource {
    public $id;
    public $category_id;
    public $name;
    public $location;
    public $capacity;    // Thêm cho khớp hình phpMyAdmin
    public $description;
    public $image_url;   // Thêm cho khớp hình phpMyAdmin
    public $is_available; // Sửa từ status thành is_available
}