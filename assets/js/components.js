/* ================================================================
   FILE: components.js - QUẢN LÝ COMPONENT VÀ UI UTILITIES
   ================================================================ */

// 1. Hàm nạp Sidebar dùng chung
async function loadSidebar(activeId) {
    try {
        const response = await fetch('../components/sidebar.html?v=9.0');
        const html = await response.text();
        
        // Tìm container sidebar cũ hoặc chèn vào vị trí phù hợp
        let sidebarWrapper = document.getElementById('sidebar-wrapper');
        if (!sidebarWrapper) {
            sidebarWrapper = document.createElement('div');
            sidebarWrapper.id = 'sidebar-wrapper';
            document.body.prepend(sidebarWrapper);
        }
        sidebarWrapper.innerHTML = html;

        // Đánh dấu menu đang hoạt động
        if (activeId) {
            const menus = document.querySelectorAll('.sidebar-menu a');
            menus.forEach(menu => {
                if (menu.id === `menu-${activeId}` || menu.getAttribute('href').includes(activeId)) {
                    menu.classList.add('active');
                }
            });
        }
        
        // Ẩn các menu theo vai trò người dùng
        const user = typeof layUser === 'function' ? layUser() : null;
        const role = user ? user.role : null;

        const menuDashboard = document.getElementById('menu-dashboard');
        const menuApprovals = document.getElementById('menu-approvals');
        const menuReports = document.getElementById('menu-reports');
        const menuUsers = document.getElementById('menu-users');
        const menuMyBookings = document.getElementById('menu-my-bookings');

        if (role === 'user') {
            if (menuDashboard) menuDashboard.style.display = 'none';
            if (menuApprovals) menuApprovals.style.display = 'none';
            if (menuReports) menuReports.style.display = 'none';
            if (menuUsers) menuUsers.style.display = 'none';
        } else if (role === 'teacher') {
            if (menuUsers) menuUsers.style.display = 'none';
        } else if (role !== 'admin') {
            // Không đăng nhập hoặc vai trò lạ: ẩn các mục admin/teacher/bookings
            if (menuDashboard) menuDashboard.style.display = 'none';
            if (menuApprovals) menuApprovals.style.display = 'none';
            if (menuReports) menuReports.style.display = 'none';
            if (menuUsers) menuUsers.style.display = 'none';
            if (menuMyBookings) menuMyBookings.style.display = 'none';
        }

        // Cập nhật thông tin User trên Sidebar (Avatar, Tên, Vai trò)
        const avatarEl = document.getElementById('sidebar-user-avatar');
        const nameEl = document.getElementById('sidebar-user-name');
        const roleEl = document.getElementById('sidebar-user-role');
        const profileLinkEl = document.getElementById('sidebar-profile-link');
        
        if (user) {
            const roleLabels = {
                'admin': 'Quản trị viên',
                'teacher': 'Giảng viên',
                'user': 'Sinh viên'
            };
            
            if (nameEl) nameEl.innerText = user.fullname || user.username || user.email || 'User';
            if (roleEl) roleEl.innerText = roleLabels[user.role] || user.role;
            
            if (avatarEl) {
                const BASE_API_URL = window.DUONG_DAN_GOC || 'http://localhost/Group8_404TNF/campus_services_booking/backend/public';
                avatarEl.src = user.avatar ? `${BASE_API_URL}${user.avatar}` : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
            }
            
            if (profileLinkEl) {
                if (user.role === 'admin') {
                    profileLinkEl.setAttribute('href', 'javascript:void(0)');
                    profileLinkEl.style.cursor = 'default';
                } else {
                    profileLinkEl.setAttribute('href', 'my-bookings.html?tab=profile');
                }
            }
        } else {
            if (nameEl) nameEl.innerText = 'Chưa đăng nhập';
            if (roleEl) roleEl.innerText = 'Khách';
            if (avatarEl) avatarEl.src = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
            if (profileLinkEl) profileLinkEl.setAttribute('href', 'login.html');
        }
        
        // Khởi tạo Chatbot AI sau khi Sidebar nạp xong
        initAIChatbot();
    } catch (error) {
        console.error("Không thể tải Sidebar:", error);
    }
}

// 2. Hệ thống thông báo Toast chuyên nghiệp
window.showToast = function(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas ${icon}" style="color: ${type === 'success' ? '#10b981' : '#ef4444'}"></i>
        <span style="font-weight: 500; color: #1e293b;">${message}</span>
    `;

    container.appendChild(toast);

    // Tự động xóa sau 3 giây
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = '0.5s';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
};

// ================================================================
// 3. HÀM KHỞI TẠO TRỢ LÝ AI CHATBOT
// ================================================================
function initAIChatbot() {
    if (document.getElementById('ai-chat-container')) return; // Tránh chèn trùng lặp

    // Thêm CSS của Chatbot AI
    const style = document.createElement('style');
    style.innerHTML = `
        #ai-chat-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 99999;
        }
        #ai-chat-button:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.6);
        }
        #ai-chat-button .pulse-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
            animation: aiPulse 2s infinite;
        }
        @keyframes aiPulse {
            0% { transform: scale(0.9); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.6; }
            100% { transform: scale(0.9); opacity: 1; }
        }

        #ai-chat-window {
            position: fixed;
            bottom: 105px;
            right: 30px;
            width: 380px;
            height: 520px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: scale(0.9) translateY(20px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 99999;
        }
        #ai-chat-window.open {
            transform: scale(1) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        #ai-chat-header {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            position: relative;
        }
        #ai-chat-header .header-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 12px;
        }
        #ai-chat-header .header-info {
            display: flex;
            flex-direction: column;
        }
        #ai-chat-header .header-title {
            font-weight: 700;
            font-size: 15px;
        }
        #ai-chat-header .header-status {
            font-size: 11px;
            opacity: 0.9;
            display: flex;
            align-items: center;
        }
        #ai-chat-header .header-status::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 5px;
        }
        #ai-chat-header .close-btn {
            position: absolute;
            right: 20px;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.8;
            transition: 0.2s;
        }
        #ai-chat-header .close-btn:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        #ai-chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #f8fafc;
        }
        .chat-msg {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 13.5px;
            line-height: 1.5;
            word-wrap: break-word;
            animation: msgFadeIn 0.3s ease forwards;
        }
        @keyframes msgFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .chat-msg.user {
            align-self: flex-end;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .chat-msg.bot {
            align-self: flex-start;
            background: white;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }

        #ai-chat-chips {
            padding: 10px 20px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            background: #f8fafc;
            border-top: 1px dashed #e2e8f0;
            scrollbar-width: none; /* Firefox */
        }
        #ai-chat-chips::-webkit-scrollbar {
            display: none; /* Safari & Chrome */
        }
        .chat-chip {
            white-space: nowrap;
            padding: 6px 12px;
            background: #f3e8ff;
            color: #8b5cf6;
            border: 1px solid #d8b4fe;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .chat-chip:hover {
            background: #8b5cf6;
            color: white;
            transform: translateY(-1px);
        }

        #ai-chat-footer {
            padding: 12px 16px;
            background: white;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        #ai-chat-input {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 13px;
            outline: none;
            transition: 0.2s;
        }
        #ai-chat-input:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12);
        }
        #ai-chat-send {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 10px;
            background: #8b5cf6;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        #ai-chat-send:hover {
            background: #7c3aed;
            transform: scale(1.05);
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 8px 12px !important;
        }
        .typing-indicator span {
            width: 5px;
            height: 5px;
            background: #94a3b8;
            border-radius: 50%;
            animation: bounce 1.3s infinite both;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.6; }
            40% { transform: scale(1); opacity: 1; transform: translateY(-4px); }
        }
        .chat-link {
            color: #8b5cf6;
            text-decoration: underline;
            font-weight: 600;
        }
    `;
    document.head.appendChild(style);

    // Chèn HTML Chatbot vào Body
    const chatContainer = document.createElement('div');
    chatContainer.id = 'ai-chat-container';
    chatContainer.innerHTML = `
        <div id="ai-chat-button">
            <i class="fas fa-robot"></i>
            <span class="pulse-dot"></span>
        </div>
        <div id="ai-chat-window">
            <div id="ai-chat-header">
                <div class="header-icon"><i class="fas fa-robot"></i></div>
                <div class="header-info">
                    <span class="header-title">Trợ Lý AI CampusBook</span>
                    <span class="header-status">Hoạt động trực tuyến</span>
                </div>
                <div class="close-btn" id="ai-chat-close"><i class="fas fa-times"></i></div>
            </div>
            <div id="ai-chat-messages"></div>
            <div id="ai-chat-chips"></div>
            <div id="ai-chat-footer">
                <input type="text" id="ai-chat-input" placeholder="Hỏi tôi gì đó..." autocomplete="off">
                <button id="ai-chat-send"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    `;
    document.body.appendChild(chatContainer);

    // Lấy DOM Elements
    const chatBtn = document.getElementById('ai-chat-button');
    const chatWin = document.getElementById('ai-chat-window');
    const closeBtn = document.getElementById('ai-chat-close');
    const sendBtn = document.getElementById('ai-chat-send');
    const chatInput = document.getElementById('ai-chat-input');
    const messagesContainer = document.getElementById('ai-chat-messages');

    // Hàm mở/đóng cửa sổ chat
    chatBtn.addEventListener('click', () => {
        chatWin.classList.toggle('open');
        if (chatWin.classList.contains('open')) {
            chatInput.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        chatWin.classList.remove('open');
    });

    // Lời chào mở đầu
    const user = typeof layUser === 'function' ? layUser() : null;
    const name = user ? user.fullname : 'bạn';
    const welcomeMsg = `👋 Xin chào **${name}**! Tôi là **Trợ lý AI CampusBook** 🤖.\n\nTôi có thể giúp bạn tra cứu nhanh danh sách phòng/sân học, kiểm tra lịch đặt chỗ cá nhân hoặc giải đáp các chính sách của trường. Bạn muốn hỏi tôi điều gì hôm nay?`;
    
    appendMessage(welcomeMsg, 'bot');
    renderChips(["🔍 Phòng & Sân có sẵn", "📅 Lịch đặt của tôi", "📜 Hướng dẫn đặt chỗ", "❌ Cách hủy lịch đặt"]);

    // Sự kiện gửi tin nhắn
    sendBtn.addEventListener('click', () => {
        const text = chatInput.value.trim();
        if (text) {
            sendChatMessage(text);
            chatInput.value = '';
        }
    });

    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const text = chatInput.value.trim();
            if (text) {
                sendChatMessage(text);
                chatInput.value = '';
            }
        }
    });

    // Hàm chèn tin nhắn vào khung chat
    function appendMessage(text, sender) {
        const bubble = document.createElement('div');
        bubble.className = `chat-msg ${sender}`;
        bubble.innerHTML = formatMarkdown(text);
        messagesContainer.appendChild(bubble);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        return bubble;
    }

    // Hàm định dạng Markdown cơ bản thành HTML
    function formatMarkdown(text) {
        if (!text) return "";
        return text
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" class="chat-link">$1</a>');
    }

    // Hàm hiển thị phím gợi ý
    function renderChips(chipsArray) {
        const chipsContainer = document.getElementById('ai-chat-chips');
        chipsContainer.innerHTML = '';
        if (!chipsArray || chipsArray.length === 0) {
            chipsContainer.style.display = 'none';
            return;
        }
        chipsContainer.style.display = 'flex';
        chipsArray.forEach(chipText => {
            const chip = document.createElement('div');
            chip.className = 'chat-chip';
            chip.innerText = chipText;
            chip.addEventListener('click', () => {
                sendChatMessage(chipText);
            });
            chipsContainer.appendChild(chip);
        });
    }

    // Hàm gửi tin nhắn tới API và nhận câu trả lời
    async function sendChatMessage(text) {
        appendMessage(text, 'user');

        // Tạo bong bóng typing
        const typingBubble = document.createElement('div');
        typingBubble.className = 'chat-msg bot typing-indicator';
        typingBubble.innerHTML = '<span></span><span></span><span></span>';
        messagesContainer.appendChild(typingBubble);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        const BASE_API_URL = window.DUONG_DAN_GOC || 'http://localhost/Group8_404TNF/campus_services_booking/backend/public';

        try {
            const response = await fetch(`${BASE_API_URL}/ai/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });
            
            const data = await response.json();
            typingBubble.remove(); // Xóa typing indicator

            if (data.status === 'success') {
                appendMessage(data.data.reply, 'bot');
                renderChips(data.data.suggested_chips);
            } else {
                appendMessage(`❌ Lỗi hệ thống: ${data.message}`, 'bot');
            }
        } catch (error) {
            typingBubble.remove();
            appendMessage('❌ Không thể kết nối với dịch vụ AI. Vui lòng kiểm tra lại kết nối mạng.', 'bot');
            console.error("AI Chat Error:", error);
        }
    }
}
