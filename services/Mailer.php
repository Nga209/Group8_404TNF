<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

class Mailer {
    /**
     * Send email via SMTP
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML supported)
     * @return array Array containing 'success' (bool) and 'mode' (string: 'smtp' or 'mock') and optionally 'error'
     */
    public static function send($to, $subject, $body) {
        // Kiểm tra chế độ chạy. Chỉ cho phép giả lập (Mock mode) khi DEVELOPMENT_MODE = true
        $isDevMode = defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true;

        if ($isDevMode && (empty(SMTP_PASS) || SMTP_PASS === 'your_app_password_here')) {
            // Chỉ ghi lại email vào Hòm thư giả lập khi chạy ở chế độ thử nghiệm (Mock mode)
            self::saveToMockInbox($to, $subject, $body);
            return [
                'success' => true,
                'mode' => 'mock',
                'message' => 'Hệ thống đang chạy ở chế độ thử nghiệm (Chưa cấu hình mật khẩu ứng dụng Gmail).'
            ];
        }

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            // Disable TLS/SSL verification warnings for localhost development if needed
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return [
                'success' => true,
                'mode' => 'smtp',
                'message' => 'Email đã được gửi thành công qua SMTP.'
            ];
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return [
                'success' => false,
                'mode' => 'smtp',
                'error' => $mail->ErrorInfo
            ];
        }
    }

    /**
     * Ghi lại email gửi đi vào file mock_emails.json để hiển thị trên Trình giả lập hòm thư
     */
    private static function saveToMockInbox($to, $subject, $body) {
        try {
            $file = __DIR__ . '/../../public/mock_emails.json';
            $emails = [];
            
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                if (is_array($data)) {
                    $emails = $data;
                }
            }
            
            $newEmail = [
                'id' => uniqid(),
                'from' => defined('SMTP_FROM') ? SMTP_FROM : 'noreply@campusbook.vn',
                'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'CampusBook Service',
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'sent_at' => date('Y-m-d H:i:s')
            ];
            
            array_unshift($emails, $newEmail);
            
            // Giới hạn tối đa 30 email gần nhất
            $emails = array_slice($emails, 0, 30);
            
            file_put_contents($file, json_encode($emails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            error_log("Failed to save mock email to log file: " . $e->getMessage());
        }
    }
}
