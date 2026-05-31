<?php
class Response {
    public function setStatusCode(int $code) {
        http_response_code($code);
    }

    public function json($data) {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}