<?php

class Controller {
    // Gọi Model
    public function model($model) {
        require_once '../app/models/' . $model . '.php';
        return new $model();
    }

    // Gọi View
    public function view($view, $data = []) {
        require_once '../app/views/' . $view . '.php';
    }

    // Chuyển hướng
    public function redirect($url) {
        header('Location: ' . BASE_URL . $url);
        exit();
    }
}
