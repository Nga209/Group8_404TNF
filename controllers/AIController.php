<?php

class AIController extends Controller {
    public function chat() {
        header('Content-Type: application/json');
        
        // 1. LẤY DỮ LIỆU ĐẦU VÀO
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        
        if (empty($message)) {
            return $this->error("Nội dung câu hỏi không được để trống!");
        }
        
        try {
            $db = (new Database())->connect();
            $user = $_SESSION['user'] ?? null;
            
            // ========================================================
            // A. THU THẬP NGỮ CẢNH DỮ LIỆU (CONTEXT) TỪ DATABASE
            // ========================================================
            
            // 1. Danh sách phòng & sân học khả dụng
            $stmtRes = $db->query("
                SELECT r.name, r.location, r.capacity, c.name as category 
                FROM resources r 
                JOIN resource_categories c ON r.category_id = c.id 
                WHERE r.is_available = 1 
                ORDER BY c.name, r.name
            ");
            $dbResources = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
            $contextResources = "";
            foreach ($dbResources as $res) {
                $contextResources .= "- " . $res['name'] . " (" . $res['location'] . ") | Sức chứa: " . $res['capacity'] . " người | Nhóm: " . $res['category'] . "\n";
            }
            
            // 2. Lịch sử đặt chỗ của người dùng đang đăng nhập
            $contextUserBookings = "Người dùng chưa đăng nhập hệ thống.";
            if ($user) {
                $stmtBook = $db->prepare("
                    SELECT b.booking_date, r.name as resource_name, ts.label as slot_label, b.status 
                    FROM bookings b 
                    JOIN resources r ON b.resource_id = r.id 
                    JOIN time_slots ts ON b.slot_id = ts.id 
                    WHERE b.user_id = ? 
                    ORDER BY b.booking_date DESC 
                    LIMIT 5
                ");
                $stmtBook->execute([$user['id']]);
                $userBookings = $stmtBook->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($userBookings)) {
                    $contextUserBookings = "Người dùng (" . $user['fullname'] . ") chưa có đơn đặt lịch nào.";
                } else {
                    $contextUserBookings = "Lịch đặt gần đây của " . $user['fullname'] . ":\n";
                    $statusMap = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối', 'cancelled' => 'Đã hủy'];
                    foreach ($userBookings as $b) {
                        $st = $statusMap[$b['status']] ?? $b['status'];
                        $contextUserBookings .= "- Ngày đặt: " . date('d/m/Y', strtotime($b['booking_date'])) . " | Tài nguyên: " . $b['resource_name'] . " | Khung giờ: " . $b['slot_label'] . " | Trạng thái: " . $st . "\n";
                    }
                }
            }
            
            // 3. Quy định chính sách đặt chỗ
            $stmtPol = $db->query("
                SELECT p.user_role, p.max_slots_per_week, p.requires_approval, c.name as category_name 
                FROM booking_policies p 
                JOIN resource_categories c ON p.category_id = c.id
            ");
            $dbPolicies = $stmtPol->fetchAll(PDO::FETCH_ASSOC);
            $contextPolicies = "Quy định chung:\n";
            foreach ($dbPolicies as $p) {
                $appr = $p['requires_approval'] ? "Cần duyệt" : "Tự động duyệt";
                $contextPolicies .= "- Vai trò: " . $p['user_role'] . " | Nhóm: " . $p['category_name'] . " | Đặt tối đa: " . $p['max_slots_per_week'] . " ca/tuần | Duyệt: " . $appr . "\n";
            }
            
            // ========================================================
            // B. KIỂM TRA & SỬ DỤNG GEMINI 1.5 FLASH NẾU CÓ API KEY
            // ========================================================
            if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
                $systemInstruction = "Bạn là Trợ lý AI thông minh tích hợp trong hệ thống đặt phòng CampusBook của trường học.\n"
                    . "Nhiệm vụ của bạn là giải đáp thắc mắc của người dùng dựa TRÊN THÔNG TIN THỰC TẾ dưới đây:\n\n"
                    . "🏢 DANH SÁCH PHÒNG & SÂN HỌC HIỆN CÓ:\n" . $contextResources . "\n"
                    . "📅 LỊCH ĐẶT CÁ NHÂN CỦA NGƯỜI DÙNG HIỆN TẠI:\n" . $contextUserBookings . "\n"
                    . "📜 CHÍNH SÁCH ĐẶT LỊCH:\n" . $contextPolicies . "\n\n"
                    . "YÊU CẦU TRẢ LỜI:\n"
                    . "1. Trả lời bằng tiếng Việt thân thiện, lịch sự, ngắn gọn trực tiếp vào câu hỏi.\n"
                    . "2. Có sử dụng Markdown (như in đậm **, danh sách -, v.v.) và các emoji để câu trả lời sinh động.\n"
                    . "3. Nếu người dùng hỏi số lượng (ví dụ: 'có mấy sân cầu lông'), hãy đếm chính xác từ danh sách phòng hiện có và liệt kê tên phòng đó ra.\n"
                    . "4. Hãy trả về kết quả định dạng JSON chuẩn theo mẫu sau, không ghi thêm bất kỳ chữ nào ngoài JSON (không bọc trong ```json):\n"
                    . '{"reply": "Nội dung trả lời...", "suggested_chips": ["🔍 Phòng & Sân có sẵn", "📅 Lịch đặt của tôi", "📜 Hướng dẫn đặt chỗ"]}' . "\n"
                    . "Lưu ý suggested_chips chỉ chứa tối đa 3-4 phím chọn nhanh ngắn gọn.";
                
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY;
                $payload = [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $systemInstruction . "\n\nUser Question: " . $message]
                            ]
                        ]
                    ]
                ];
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bỏ qua xác thực SSL trên localhost để tránh lỗi cert
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 && $result) {
                    $jsonRes = json_decode($result, true);
                    $rawText = $jsonRes['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $rawText = trim($rawText);
                    
                    // Loại bỏ ký tự bọc markdown json nếu có
                    if (strpos($rawText, '```json') === 0) {
                        $rawText = substr($rawText, 7);
                        if (substr($rawText, -3) === '```') {
                            $rawText = substr($rawText, 0, -3);
                        }
                    }
                    $rawText = trim($rawText);
                    
                    $aiData = json_decode($rawText, true);
                    if ($aiData && isset($aiData['reply'])) {
                        return $this->success($aiData, "Trợ lý AI Gemini phản hồi thành công");
                    }
                }
            }
            
            // ========================================================
            // C. BỘ XỬ LÝ CỤC BỘ (LOCAL FALLBACK ENGINE) NẾU KHÔNG CÓ API KEY
            // ========================================================
            $cleanMsg = mb_strtolower($message, 'UTF-8');
            $responseMessage = "";
            $suggestedChips = [];
            
            // 1. Chào hỏi
            if ($this->containsAny($cleanMsg, ['xin chào', 'hello', 'hi', 'chào', 'chào bạn', 'ai đấy', 'bot'])) {
                $name = $user ? $user['fullname'] : 'bạn';
                $responseMessage = "👋 Xin chào **$name**! Tôi là **Trợ lý AI CampusBook** 🤖.\n\nTôi có thể giúp bạn tra cứu nhanh danh sách phòng/sân học, kiểm tra lịch đặt chỗ cá nhân hoặc giải đáp các chính sách của trường. Bạn muốn hỏi tôi điều gì hôm nay?";
                $suggestedChips = ["🔍 Phòng & Sân có sẵn", "📅 Lịch đặt của tôi", "📜 Hướng dẫn đặt chỗ"];
            }
            
            // 2. Chính sách quy định
            elseif ($this->containsAny($cleanMsg, ['quy định', 'chính sách', 'nội quy', 'mấy lần', 'tối đa', 'cao điểm', 'luật'])) {
                $responseMessage = "📜 **Chính sách & Quy định đặt lịch trên CampusBook:**\n\n" .
                                   "1️⃣ **Hạn mức đặt chỗ:** Mỗi tài khoản sinh viên được đặt tối đa **3 ca học hoặc ca thể thao mỗi tuần** để đảm bảo công bằng tài nguyên.\n\n" .
                                   "2️⃣ **Quy trình phê duyệt:**\n" .
                                   "   - Các phòng tự học thông thường: Tự động phê duyệt ngay lập tức.\n" .
                                   "   - Các phòng Hội thảo lớn, phòng Studio, hoặc Sân bóng đá lớn: Cần có sự phê duyệt từ Giảng viên phụ trách hoặc Ban quản trị.\n\n" .
                                   "3️⃣ **Khung giờ cao điểm:** Các khung giờ từ 16:30 đến 20:30 (ca thể thao chiều tối) thường rất đông, vui lòng lên kế hoạch đặt lịch trước ít nhất 1-2 ngày.";
                $suggestedChips = ["🔍 Phòng & Sân có sẵn", "📅 Lịch đặt của tôi", "❌ Cách hủy lịch đặt"];
            }
            
            // 3. Tra cứu lịch đặt cá nhân
            elseif ($this->containsAny($cleanMsg, ['lịch đặt', 'lịch sử', 'đơn đặt', 'tôi đã đặt', 'lịch của tôi', 'kiểm tra lịch', 'xem lịch'])) {
                if (!$user) {
                    $responseMessage = "🔒 Bạn chưa đăng nhập hệ thống. Vui lòng [đăng nhập tại đây](login.html) để tôi có thể tra cứu lịch đặt chỗ của riêng bạn.";
                    $suggestedChips = ["Đăng nhập ngay"];
                } else {
                    $stmt = $db->prepare("
                        SELECT b.booking_date, r.name as resource_name, ts.label as slot_label, b.status 
                        FROM bookings b 
                        JOIN resources r ON b.resource_id = r.id 
                        JOIN time_slots ts ON b.slot_id = ts.id 
                        WHERE b.user_id = ? 
                        ORDER BY b.booking_date DESC 
                        LIMIT 5
                    ");
                    $stmt->execute([$user['id']]);
                    $myBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($myBookings)) {
                        $responseMessage = "📅 Tài khoản của bạn (**" . htmlspecialchars($user['fullname']) . "**) hiện tại chưa có bất kỳ đơn đặt chỗ nào.\n\nHãy thử đặt lịch cho ca học hoặc ca thể thao sắp tới nhé!";
                    } else {
                        $statusLabels = [
                            'pending' => '⏳ Chờ duyệt',
                            'approved' => '✅ Đã duyệt',
                            'rejected' => '❌ Bị từ chối',
                            'cancelled' => '🚫 Đã hủy'
                        ];
                        
                        $responseMessage = "📅 **Top 5 đơn đặt chỗ gần đây của bạn (" . htmlspecialchars($user['fullname']) . "):**\n\n";
                        foreach ($myBookings as $b) {
                            $statusLabel = $statusLabels[$b['status']] ?? $b['status'];
                            $dateFmt = date('d/m/Y', strtotime($b['booking_date']));
                            $responseMessage .= "📍 **" . htmlspecialchars($b['resource_name']) . "**\n   - Ngày: $dateFmt | Khung: " . htmlspecialchars($b['slot_label']) . "\n   - Trạng thái: **$statusLabel**\n\n";
                        }
                    }
                    $suggestedChips = ["🔍 Phòng & Sân có sẵn", "📜 Hướng dẫn đặt chỗ", "❌ Cách hủy lịch đặt"];
                }
            }
            
            // 4. Hướng dẫn hủy lịch đặt
            elseif ($this->containsAny($cleanMsg, ['hủy', 'hủy đặt', 'hủy lịch', 'hủy phòng', 'cancel'])) {
                $responseMessage = "❌ **Hướng dẫn hủy lịch đặt chỗ:**\n\n" .
                                   "Nếu lịch học/lịch tập thay đổi và bạn muốn hủy đặt chỗ để nhường phòng/sân cho các bạn khác, vui lòng làm theo các bước sau:\n\n" .
                                   "1️⃣ Nhấp vào mục **Lịch của tôi** (My Bookings) ở menu bên trái giao diện.\n" .
                                   "2️⃣ Tại tab **Lịch đặt của tôi**, bạn sẽ thấy danh sách các lịch đặt.\n" .
                                   "3️⃣ Tìm đến đơn đặt tương ứng và nhấp vào nút **Hủy lịch** màu đỏ 🔴.\n\n" .
                                   "⚠️ *Lưu ý: Bạn chỉ có thể hủy lịch đặt trước khi khung giờ (ca) đó bắt đầu.*";
                $suggestedChips = ["📅 Lịch đặt của tôi", "🔍 Phòng & Sân có sẵn", "📜 Hướng dẫn đặt chỗ"];
            }
            
            // 5. Tra cứu phòng/sân học trống
            elseif ($this->containsAny($cleanMsg, ['phòng', 'sân', 'tài nguyên', 'phòng trống', 'chỗ đặt', 'phòng học', 'cầu lông', 'bóng đá', 'bóng rổ', 'hội thảo', 'hội trường', 'tự học', 'máy tính', 'lớp học', 'studio'])) {
                $searchTerm = '';
                $searchLabel = '';
                if ($this->containsAny($cleanMsg, ['cầu lông'])) { $searchTerm = 'cầu lông'; $searchLabel = 'Sân Cầu Lông'; }
                elseif ($this->containsAny($cleanMsg, ['bóng đá'])) { $searchTerm = 'bóng đá'; $searchLabel = 'Sân Bóng Đá'; }
                elseif ($this->containsAny($cleanMsg, ['bóng rổ'])) { $searchTerm = 'bóng rổ'; $searchLabel = 'Sân Bóng Rổ'; }
                elseif ($this->containsAny($cleanMsg, ['hội thảo', 'hội trường'])) { $searchTerm = 'hội'; $searchLabel = 'Hội Trường/Phòng Hội Thảo'; }
                elseif ($this->containsAny($cleanMsg, ['tự học'])) { $searchTerm = 'tự học'; $searchLabel = 'Phòng Tự Học'; }
                elseif ($this->containsAny($cleanMsg, ['máy tính', 'lab'])) { $searchTerm = 'máy'; $searchLabel = 'Phòng Máy Tính/Phòng Lab'; }
                elseif ($this->containsAny($cleanMsg, ['studio', 'podcast', 'media'])) { $searchTerm = 'studio'; $searchLabel = 'Phòng Studio/Media'; }
                
                if (!empty($searchTerm)) {
                    $stmt = $db->prepare("
                        SELECT r.name, r.location, r.capacity, c.name as category 
                        FROM resources r 
                        JOIN resource_categories c ON r.category_id = c.id 
                        WHERE r.is_available = 1 AND (r.name LIKE ? OR c.name LIKE ?)
                        ORDER BY c.name, r.name
                    ");
                    $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
                } else {
                    $stmt = $db->query("
                        SELECT r.name, r.location, r.capacity, c.name as category 
                        FROM resources r 
                        JOIN resource_categories c ON r.category_id = c.id 
                        WHERE r.is_available = 1 
                        ORDER BY c.name, r.name
                    ");
                }
                
                $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($resources)) {
                    if (!empty($searchLabel)) {
                        $responseMessage = "🔍 Tôi không tìm thấy phòng hoặc sân nào thuộc nhóm **\"$searchLabel\"** đang hoạt động trên hệ thống.";
                    } else {
                        $responseMessage = "Hiện tại hệ thống chưa cập nhật danh sách phòng hoặc sân học khả dụng nào. Vui lòng liên hệ Admin.";
                    }
                } else {
                    $count = count($resources);
                    $hasHowMany = $this->containsAny($cleanMsg, ['mấy', 'bao nhiêu', 'số lượng', 'có bao nhiêu']);
                    
                    if (!empty($searchLabel)) {
                        if ($hasHowMany) {
                            $responseMessage = "🏸 Trên hệ thống hiện tại có **$count** $searchLabel:\n\n";
                        } else {
                            $responseMessage = "🔍 Tìm thấy **$count** $searchLabel khả dụng:\n\n";
                        }
                    } else {
                        if ($hasHowMany) {
                            $responseMessage = "🏢 Hệ thống hiện tại có tổng cộng **$count** phòng & sân học khả dụng:\n\n";
                        } else {
                            $responseMessage = "🏢 **Danh sách phòng & sân học khả dụng trên hệ thống:**\n\n";
                        }
                    }
                    
                    $currentCategory = "";
                    foreach ($resources as $res) {
                        if ($currentCategory !== $res['category'] && empty($searchLabel)) {
                            $currentCategory = $res['category'];
                            $responseMessage .= "\n🔹 **Nhóm: " . htmlspecialchars($currentCategory) . "**\n";
                        }
                        $responseMessage .= "- **" . htmlspecialchars($res['name']) . "** (" . htmlspecialchars($res['location']) . ") - Sức chứa: " . $res['capacity'] . " người\n";
                    }
                    $responseMessage .= "\n*Bạn có thể bấm vào mục **Phòng & Sân** ở thanh điều hướng để thực hiện đặt lịch ngay nhé!*";
                }
                $suggestedChips = ["📅 Lịch đặt của tôi", "📜 Hướng dẫn đặt chỗ", "❌ Cách hủy lịch đặt"];
            }
            
            // 6. Mặc định
            else {
                $responseMessage = "🤖 Tôi chưa hiểu câu hỏi của bạn. Hãy thử hỏi tôi các câu hỏi mẫu dưới đây hoặc click vào các phím chọn nhanh để tôi hỗ trợ nhé:\n\n" .
                                   "- *\"Hệ thống có những phòng nào?\"*\n" .
                                   "- *\"Lịch đặt của tôi hiện trạng thái thế nào?\"*\n" .
                                   "- *\"Mỗi tuần được đặt tối đa mấy lần?\"*\n" .
                                   "- *\"Tôi muốn hủy phòng đã đặt thì làm sao?\"*";
                $suggestedChips = ["🔍 Phòng & Sân có sẵn", "📅 Lịch đặt của tôi", "📜 Hướng dẫn đặt chỗ", "❌ Cách hủy lịch đặt"];
            }
            
            $data = [
                'reply' => $responseMessage,
                'suggested_chips' => $suggestedChips
            ];
            
            return $this->success($data, "Trợ lý AI cục bộ phản hồi thành công");
            
        } catch (Exception $e) {
            return $this->error("Lỗi khi trò chuyện với AI: " . $e->getMessage());
        }
    }
    
    private function containsAny($haystack, array $needles) {
        foreach ($needles as $needle) {
            if ($this->containsWord($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }
    
    private function containsWord($haystack, $word) {
        if (mb_strlen($word, 'UTF-8') <= 4) {
            $pattern = '/(?<=^|\s|[.,!?()\-+*\/])' . preg_quote($word, '/') . '(?=$|\s|[.,!?()\-+*\/])/ui';
            return preg_match($pattern, $haystack) === 1;
        }
        return mb_strpos($haystack, $word, 0, 'UTF-8') !== false;
    }
}
