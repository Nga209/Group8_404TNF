<?php

require_once '../core/Controller.php';

class ProductController extends Controller {
    private $productModel;

    public function __construct() {
        $this->productModel = $this->model('Product');
    }

    // GET /products
    public function index() {
        $products = $this->productModel->getAll();
        $this->view('products/index', ['products' => $products]);
    }

    // GET /products/create OR POST /products/create
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý lưu sau khi submit form
            $data = [
                'name' => $_POST['name'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'category' => $_POST['category'] ?? ''
            ];

            if ($this->productModel->create($data)) {
                $this->redirect('/product');
            }
        } else {
            // Hiển thị form tạo mới
            $this->view('products/create');
        }
    }
}
