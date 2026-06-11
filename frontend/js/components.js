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
// Hàm tạo SVG lõi AI động làm điểm nhấn đặc biệt
function getAICoreSVG(size = '100%') {
    return `
        <svg class="ai-core-svg" viewBox="0 0 100 100" style="width: ${size}; height: ${size}; display: block; overflow: visible;">
            <defs>
                <linearGradient id="aiGradOuter" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ec4899" />
                    <stop offset="100%" stop-color="#8b5cf6" />
                </linearGradient>
                <linearGradient id="aiGradMiddle" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#a855f7" />
                    <stop offset="100%" stop-color="#6366f1" />
                </linearGradient>
                <linearGradient id="aiGradInner" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#6366f1" />
                    <stop offset="100%" stop-color="#3b82f6" />
                </linearGradient>
                <filter id="aiGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="4" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>
            <!-- Vòng ngoài xoay xuôi -->
            <circle class="ring ring-outer" cx="50" cy="50" r="40" fill="none" stroke="url(#aiGradOuter)" stroke-width="3" stroke-dasharray="120 60" stroke-linecap="round" />
            <!-- Vòng giữa xoay ngược -->
            <circle class="ring ring-middle" cx="50" cy="50" r="28" fill="none" stroke="url(#aiGradMiddle)" stroke-width="2.5" stroke-dasharray="70 40" stroke-linecap="round" />
            <!-- Vòng trong xoay xuôi -->
            <circle class="ring ring-inner" cx="50" cy="50" r="18" fill="none" stroke="url(#aiGradInner)" stroke-width="2.5" stroke-dasharray="30 20" stroke-linecap="round" />
            <!-- Nhân sáng ở trung tâm -->
            <circle class="core-pulse" cx="50" cy="50" r="7" fill="#ffffff" filter="url(#aiGlow)" />
        </svg>
    `;
}

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
            background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4), 0 4px 10px rgba(236, 72, 153, 0.2);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 99999;
            animation: aiFloat 4s ease-in-out infinite;
        }
        #ai-chat-button:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.6), 0 8px 15px rgba(236, 72, 153, 0.3);
        }
        #ai-chat-button:hover #ai-chat-tooltip {
            opacity: 1;
            transform: translateX(-10px);
        }
        #ai-chat-tooltip {
            position: absolute;
            right: 70px;
            background: rgba(15, 23, 42, 0.9);
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        #ai-chat-tooltip::after {
            content: '';
            position: absolute;
            right: -5px;
            top: 50%;
            transform: translateY(-50%);
            border-width: 5px 0 5px 5px;
            border-style: solid;
            border-color: transparent transparent transparent rgba(15, 23, 42, 0.9);
        }
        #ai-chat-button::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899);
            z-index: -1;
            opacity: 0.4;
            filter: blur(8px);
            animation: auraPulse 3s ease-in-out infinite alternate;
        }
        @keyframes auraPulse {
            0% { transform: scale(0.95); opacity: 0.3; }
            100% { transform: scale(1.1); opacity: 0.6; }
        }
        @keyframes aiFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        /* AI Core SVG Animations */
        .ai-core-svg {
            transform: translateZ(0);
        }
        .ai-core-svg .ring {
            transform-origin: 50px 50px;
        }
        .ai-core-svg .ring-outer {
            animation: rotateOuter 10s linear infinite;
        }
        .ai-core-svg .ring-middle {
            animation: rotateMiddle 8s linear infinite reverse;
        }
        .ai-core-svg .ring-inner {
            animation: rotateInner 5s linear infinite;
        }
        .ai-core-svg .core-pulse {
            animation: pulseCore 2s ease-in-out infinite alternate;
            filter: drop-shadow(0 0 6px #a855f7) drop-shadow(0 0 12px #6366f1);
            transform-origin: 50px 50px;
        }
        @keyframes rotateOuter {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes rotateMiddle {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }
        @keyframes rotateInner {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes pulseCore {
            0% { transform: scale(0.8); opacity: 0.8; }
            100% { transform: scale(1.2); opacity: 1; }
        }

        #ai-chat-window {
            position: fixed;
            bottom: 105px;
            right: 30px;
            width: 400px;
            height: 580px;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 25px 60px rgba(99, 102, 241, 0.15), 0 5px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: scale(0.85) translateY(40px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 99999;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        #ai-chat-window.open {
            transform: scale(1) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        #ai-chat-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed, #db2777);
            background-size: 200% 200%;
            animation: gradientMove 6s ease infinite;
            color: white;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            position: relative;
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.15);
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        #ai-chat-header .header-avatar-container {
            position: relative;
            margin-right: 14px;
        }
        #ai-chat-header .header-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        #ai-chat-header .header-info {
            display: flex;
            flex-direction: column;
        }
        #ai-chat-header .header-title {
            font-weight: 700;
            font-size: 16px;
            letter-spacing: -0.3px;
        }
        #ai-chat-header .header-status {
            font-size: 11px;
            opacity: 0.85;
            display: flex;
            align-items: center;
            margin-top: 2.5px;
            font-weight: 600;
            color: #e0e7ff;
        }
        #ai-chat-header .header-status::before {
            content: '✨';
            margin-right: 5px;
            font-size: 10px;
        }
        #ai-chat-header .header-actions {
            position: absolute;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        #ai-chat-header .header-action-btn,
        #ai-chat-header .ai-chat-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.85;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        #ai-chat-header .header-action-btn:hover,
        #ai-chat-header .ai-chat-close-btn:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.08);
        }
        #ai-chat-header .ai-chat-close-btn:hover {
            transform: scale(1.08) rotate(90deg);
        }

        #ai-chat-messages {
            flex: 1;
            padding: 24px 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 18px;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(243, 232, 255, 0.45) 0px, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(224, 242, 254, 0.3) 0px, transparent 50%);
            scroll-behavior: smooth;
        }
        #ai-chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        #ai-chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }
        #ai-chat-messages::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 10px;
        }
        #ai-chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.5);
        }

        .chat-msg-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            max-width: 85%;
            animation: msgFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes msgFadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .chat-msg-wrapper.bot-wrapper {
            align-self: flex-start;
        }
        .chat-msg-wrapper.user-wrapper {
            align-self: flex-end;
            justify-content: flex-end;
        }
        .chat-msg-wrapper .msg-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(143, 168, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(139, 92, 246, 0.08);
            border: 1px solid rgba(139, 92, 246, 0.15);
        }
        
        .chat-msg {
            padding: 12px 18px;
            border-radius: 20px;
            font-size: 14px;
            line-height: 1.6;
            word-wrap: break-word;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }
        .chat-msg.user {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }
        .chat-msg.bot {
            background: white;
            color: #1e293b;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-bottom-left-radius: 4px;
        }
        .chat-msg p {
            margin: 0 0 10px 0;
        }
        .chat-msg p:last-child {
            margin-bottom: 0;
        }

        #ai-chat-chips {
            padding: 12px 20px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            background: #f8fafc;
            border-top: 1px dashed rgba(226, 232, 240, 0.8);
            scrollbar-width: none;
        }
        #ai-chat-chips::-webkit-scrollbar {
            display: none;
        }
        .chat-chip {
            white-space: nowrap;
            padding: 8px 16px;
            background: rgba(243, 232, 255, 0.75);
            color: #7c3aed;
            border: 1px solid rgba(216, 180, 254, 0.5);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(5px);
        }
        .chat-chip:hover {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
        }

        #ai-chat-footer-wrapper {
            background: white;
            padding: 4px 0;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
        }
        #ai-chat-footer {
            margin: 10px 18px 14px 18px;
            padding: 6px 6px 6px 18px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            display: flex;
            gap: 8px;
            align-items: center;
            transition: all 0.3s ease;
        }
        #ai-chat-footer:focus-within {
            border-color: #8b5cf6;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.15);
            background: white;
        }
        #ai-chat-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 8px 0;
            font-size: 14px;
            outline: none;
            color: #1e293b;
            font-family: inherit;
        }
        #ai-chat-input::placeholder {
            color: #94a3b8;
        }
        #ai-chat-send {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
        }
        #ai-chat-send:hover {
            background: linear-gradient(135deg, #5a5fec, #7c3aed);
            transform: scale(1.05) rotate(10deg);
            box-shadow: 0 6px 14px rgba(99, 102, 241, 0.35);
        }
        #ai-chat-send i {
            font-size: 14px;
            transition: transform 0.3s;
        }
        #ai-chat-send:hover i {
            transform: translate(2px, -2px);
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 12px 18px !important;
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-bottom-left-radius: 4px;
            box-shadow: none !important;
        }
        .typing-indicator span {
            width: 6px;
            height: 6px;
            background: #94a3b8;
            border-radius: 50%;
            animation: bounce 1.4s infinite both;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
            45% { transform: scale(1); opacity: 1; transform: translateY(-5px); }
        }
        .chat-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px dashed #6366f1;
            transition: all 0.2s;
        }
        .chat-link:hover {
            color: #4f46e5;
            border-bottom-style: solid;
        }
    `;
    document.head.appendChild(style);

    // Chèn HTML Chatbot vào Body
    const chatContainer = document.createElement('div');
    chatContainer.id = 'ai-chat-container';
    chatContainer.innerHTML = `
        <div id="ai-chat-button">
            ${getAICoreSVG('42px')}
            <span id="ai-chat-tooltip">Hỏi Trợ lý AI ✨</span>
        </div>
        <div id="ai-chat-window">
            <div id="ai-chat-header">
                <div class="header-avatar-container">
                    <div class="header-icon">${getAICoreSVG('36px')}</div>
                </div>
                <div class="header-info">
                    <span class="header-title">Trợ Lý AI CampusBook</span>
                    <span class="header-status">Hoạt động trực tuyến</span>
                </div>
                <div class="header-actions">
                    <button class="header-action-btn" id="ai-chat-clear" title="Xóa cuộc trò chuyện"><i class="fas fa-rotate-left"></i></button>
                    <button class="ai-chat-close-btn" id="ai-chat-close" title="Đóng"><i class="fas fa-xmark"></i></button>
                </div>
            </div>
            <div id="ai-chat-messages"></div>
            <div id="ai-chat-chips"></div>
            <div id="ai-chat-footer-wrapper">
                <div id="ai-chat-footer">
                    <input type="text" id="ai-chat-input" placeholder="Hỏi tôi gì đó..." autocomplete="off">
                    <button id="ai-chat-send"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(chatContainer);

    // Lấy DOM Elements
    const chatBtn = document.getElementById('ai-chat-button');
    const chatWin = document.getElementById('ai-chat-window');
    const closeBtn = document.getElementById('ai-chat-close');
    const clearBtn = document.getElementById('ai-chat-clear');
    const sendBtn = document.getElementById('ai-chat-send');
    const chatInput = document.getElementById('ai-chat-input');
    const messagesContainer = document.getElementById('ai-chat-messages');

    // Hàm mở/đóng cửa sổ chat
    chatBtn.addEventListener('click', (e) => {
        if (e.target.id === 'ai-chat-tooltip') return;
        chatWin.classList.toggle('open');
        if (chatWin.classList.contains('open')) {
            chatInput.focus();
        }
    });

    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        chatWin.classList.remove('open');
    });

    // Lời chào mở đầu
    const user = typeof layUser === 'function' ? layUser() : null;
    const name = user ? user.fullname : 'bạn';
    const welcomeMsg = `👋 Xin chào **${name}**! Tôi là **Trợ lý AI CampusBook** 🤖.\n\nTôi có thể giúp bạn tra cứu nhanh danh sách phòng/sân học, kiểm tra lịch đặt chỗ cá nhân hoặc giải đáp các chính sách của trường. Bạn muốn hỏi tôi điều gì hôm nay?`;
    
    appendMessage(welcomeMsg, 'bot');
    renderChips(["🔍 Phòng & Sân có sẵn", "📅 Lịch đặt của tôi", "📜 Hướng dẫn đặt chỗ", "❌ Cách hủy lịch đặt"]);

    // Reset cuộc trò chuyện
    clearBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        messagesContainer.innerHTML = '';
        appendMessage(welcomeMsg, 'bot');
        renderChips(["🔍 Phòng & Sân có sẵn", "📅 Lịch đặt của tôi", "📜 Hướng dẫn đặt chỗ", "❌ Cách hủy lịch đặt"]);
    });

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
        const wrapper = document.createElement('div');
        wrapper.className = `chat-msg-wrapper ${sender}-wrapper`;
        
        if (sender === 'bot') {
            wrapper.innerHTML = `
                <div class="msg-avatar">
                    ${getAICoreSVG('22px')}
                </div>
                <div class="chat-msg bot">${formatMarkdown(text)}</div>
            `;
        } else {
            wrapper.innerHTML = `
                <div class="chat-msg user">${formatMarkdown(text)}</div>
            `;
        }
        
        messagesContainer.appendChild(wrapper);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        return wrapper;
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
        const typingWrapper = document.createElement('div');
        typingWrapper.className = 'chat-msg-wrapper bot-wrapper typing-wrapper';
        typingWrapper.innerHTML = `
            <div class="msg-avatar">
                ${getAICoreSVG('22px')}
            </div>
            <div class="chat-msg bot typing-indicator">
                <span></span><span></span><span></span>
            </div>
        `;
        messagesContainer.appendChild(typingWrapper);
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
            typingWrapper.remove(); // Xóa typing indicator

            if (data.status === 'success') {
                appendMessage(data.data.reply, 'bot');
                renderChips(data.data.suggested_chips);
            } else {
                appendMessage(`❌ Lỗi hệ thống: ${data.message}`, 'bot');
            }
        } catch (error) {
            typingWrapper.remove();
            appendMessage('❌ Không thể kết nối với dịch vụ AI. Vui lòng kiểm tra lại kết nối mạng.', 'bot');
            console.error("AI Chat Error:", error);
        }
    }
}

// Tự động tiêm CSS của Custom Dropdown vào Head
(function injectCustomDropdownCSS() {
    const style = document.createElement('style');
    style.innerHTML = `
        /* Custom Dropdown Styling to make it look premium and NOT basic/native */
        .custom-dropdown {
            position: relative;
            display: inline-block;
            cursor: pointer;
            font-family: inherit;
            user-select: none;
            text-align: left;
        }
        .custom-dropdown.custom-dropdown-block {
            display: block;
            width: 100%;
        }
        .custom-dropdown .dropdown-selected {
            padding: 12px 35px 12px 20px;
            border-radius: 12px;
            border: 1px solid rgba(143, 168, 255, 0.15);
            background: white;
            font-weight: 600;
            color: var(--dark, #0f172a);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        .custom-dropdown.custom-dropdown-block .dropdown-selected {
            height: 50px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
        }
        .custom-dropdown.open .dropdown-selected {
            border-color: var(--primary, #6366f1);
            box-shadow: 0 0 0 4px rgba(143, 168, 255, 0.15);
        }
        .custom-dropdown .dropdown-options {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            min-width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(143, 168, 255, 0.12);
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(143, 168, 255, 0.16);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.95);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            padding: 5px;
            box-sizing: border-box;
        }
        .custom-dropdown.open .dropdown-options {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        .custom-dropdown .dropdown-option {
            padding: 10px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
            border-radius: 9px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .custom-dropdown .dropdown-option:hover {
            background: rgba(99, 102, 241, 0.08);
            color: var(--primary, #6366f1);
        }
        .custom-dropdown .dropdown-option.selected {
            background: var(--primary, #6366f1);
            color: white;
        }
    `;
    document.head.appendChild(style);
})();

// Hàm chuyển đổi select native thành Custom Dropdown xịn sò, mượt mà
function convertSelectsToCustom(container = document) {
    if (window.location.pathname.includes('users.html')) return;
    const selects = container.querySelectorAll('.select-filter:not(.customized)');
    selects.forEach(select => {
        // Bỏ qua select có thuộc tính multiple (không phù hợp làm dropdown đơn)
        if (select.hasAttribute('multiple')) return;

        select.classList.add('customized');
        select.style.display = 'none'; // Ẩn select gốc đi

        // Tạo container mới cho custom dropdown
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-dropdown';
        
        // Nếu select gốc có class form-input-v2, biến custom dropdown thành full-width block để khớp form layout
        if (select.classList.contains('form-input-v2')) {
            wrapper.classList.add('custom-dropdown-block');
        }
        
        // Đồng bộ các style inline (như padding, height, font-size nếu có)
        if (select.getAttribute('style')) {
            const inlineStyles = select.getAttribute('style');
            const heightMatch = inlineStyles.match(/height:\s*([^;]+)/);
            const paddingMatch = inlineStyles.match(/padding:\s*([^;]+)/);
            const fontSizeMatch = inlineStyles.match(/font-size:\s*([^;]+)/);
            let extraStyles = '';
            if (heightMatch) extraStyles += `height: ${heightMatch[1]}; `;
            if (paddingMatch) extraStyles += `padding: ${paddingMatch[1]}; `;
            if (fontSizeMatch) extraStyles += `font-size: ${fontSizeMatch[1]}; `;
            if (extraStyles) {
                wrapper.setAttribute('style', extraStyles);
            }
        }

        // Lấy tất cả options
        const options = Array.from(select.options);
        const selectedOption = select.options[select.selectedIndex] || select.options[0];
        
        // Phần tử hiển thị giá trị được chọn
        const selectedDiv = document.createElement('div');
        selectedDiv.className = 'dropdown-selected';
        selectedDiv.textContent = selectedOption ? selectedOption.textContent : '';
        wrapper.appendChild(selectedDiv);

        // Container chứa danh sách options
        const optionsDiv = document.createElement('div');
        optionsDiv.className = 'dropdown-options';

        options.forEach(opt => {
            const optDiv = document.createElement('div');
            optDiv.className = 'dropdown-option';
            if (selectedOption && opt.value === selectedOption.value) optDiv.classList.add('selected');
            optDiv.setAttribute('data-value', opt.value);
            optDiv.textContent = opt.textContent;

            optDiv.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // Cập nhật class active
                optionsDiv.querySelectorAll('.dropdown-option').forEach(o => o.classList.remove('selected'));
                optDiv.classList.add('selected');
                
                // Cập nhật nhãn hiển thị và value của select gốc
                selectedDiv.textContent = opt.textContent;
                select.value = opt.value;
                
                // Đóng dropdown
                wrapper.classList.remove('open');
                
                // Kích hoạt sự kiện onchange gốc
                select.dispatchEvent(new Event('change'));
            });

            optionsDiv.appendChild(optDiv);
        });

        wrapper.appendChild(optionsDiv);

        // Lắng nghe sự kiện change trên select gốc để đồng bộ ngược lại custom dropdown (ví dụ khi cập nhật bằng JS)
        select.addEventListener('change', () => {
            const currentVal = select.value;
            const correspondingOpt = options.find(opt => opt.value === currentVal);
            selectedDiv.textContent = correspondingOpt ? correspondingOpt.textContent : '';
            
            optionsDiv.querySelectorAll('.dropdown-option').forEach(o => {
                if (o.getAttribute('data-value') === currentVal) {
                    o.classList.add('selected');
                } else {
                    o.classList.remove('selected');
                }
            });
        });

        // Toggle đóng mở
        selectedDiv.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // Đóng tất cả dropdown khác trước khi mở
            document.querySelectorAll('.custom-dropdown.open').forEach(d => {
                if (d !== wrapper) d.classList.remove('open');
            });
            
            wrapper.classList.toggle('open');
        });

        // Chèn wrapper vào ngay sau select gốc
        select.parentNode.insertBefore(wrapper, select.nextSibling);
    });
}

// Sự kiện click ra ngoài để đóng tất cả các custom dropdown
document.addEventListener('click', () => {
    document.querySelectorAll('.custom-dropdown.open').forEach(d => {
        d.classList.remove('open');
    });
});

// Chạy tự động sau khi DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    convertSelectsToCustom();
});
