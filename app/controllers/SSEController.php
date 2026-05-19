<?php

class SSEController extends Controller {
    public function stream() {
        // Thiết lập header cho SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Tắt buffering cho Nginx
        
        // Vô hiệu hóa output buffering của Apache/PHP
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);

        // Giải phóng session lock
        if (session_id()) session_write_close();

        // Đẩy 4KB padding để vượt qua bộ đệm ban đầu của một số web server
        echo str_repeat(' ', 4096) . "\n";

        $db = (new Database())->connect();
        $lastState = "";
        $counter = 0;

        while (true) {
            // Lấy số lượng theo từng trạng thái để phát hiện cả việc thêm mới và thay đổi trạng thái duyệt
            $stmt = $db->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
            $currentState = json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

            if ($currentState !== $lastState) {
                // Nếu có thay đổi, gửi tín hiệu "update" về client
                echo "event: update\n";
                echo "data: " . json_encode(['timestamp' => time(), 'state' => $currentState]) . "\n\n";
                $lastState = $currentState;
            } else {
                // Gửi comment ping định kỳ để giữ kết nối không bị đóng và ép xả buffer
                // Chỉ gửi ping mỗi 2 giây (4 vòng lặp x 500ms) để đỡ rác log
                if ($counter % 4 == 0) {
                    echo ": ping\n\n";
                }
            }

            // Ép PHP xả buffer
            while (ob_get_level() > 0) ob_end_flush();
            flush();

            // Nghỉ 500ms
            usleep(500000); 
            $counter++;
        }
    }
}
