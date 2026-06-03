-- =========================================================
-- 1. KHỞI TẠO DATABASE
-- =========================================================
CREATE DATABASE IF NOT EXISTS campus_booking
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_booking;

-- =========================================================
-- 2. BẢNG NGƯỜI DÙNG
-- =========================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user', 'staff', 'teacher') DEFAULT 'user',
    status TINYINT DEFAULT 1, 
    reset_code VARCHAR(10) NULL,
    reset_code_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 3. DANH MỤC TÀI NGUYÊN
-- =========================================================
CREATE TABLE resource_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_class VARCHAR(50) 
) ENGINE=InnoDB;

-- =========================================================
-- 4. TÀI NGUYÊN (ĐÃ SỬA: THÊM SLOT_TYPE)
-- =========================================================
CREATE TABLE resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    capacity INT DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255),
    slot_type VARCHAR(20) DEFAULT 'academic', -- Thêm cột để phân biệt loại khung giờ
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES resource_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 5. KHUNG GIỜ (ĐÃ SỬA: THÊM IS_PEAK)
-- =========================================================
CREATE TABLE time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    label VARCHAR(50), 
    slot_type VARCHAR(20) DEFAULT 'academic', -- Phân loại: academic (tiết học) hoặc sport (thể thao)
    is_peak BOOLEAN DEFAULT FALSE,           -- Đánh dấu giờ cao điểm
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 6. CHÍNH SÁCH ĐẶT CHỖ
-- =========================================================
CREATE TABLE booking_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    user_role ENUM('user', 'staff', 'teacher') NOT NULL,
    max_slots_per_week INT DEFAULT 3,
    requires_approval BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES resource_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 7. BẢNG BOOKINGS
-- =========================================================
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    resource_id INT NOT NULL,
    slot_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booked_for_name VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,

    CONSTRAINT unique_booking UNIQUE (resource_id, slot_id, booking_date),
    INDEX idx_booking_user (user_id),
    INDEX idx_booking_resource (resource_id),
    INDEX idx_booking_date (booking_date)
) ENGINE=InnoDB;

-- (Các bảng 8, 9, 10, 11 giữ nguyên như cũ...)
CREATE TABLE approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    admin_id INT NOT NULL,
    status ENUM('approved', 'rejected') NOT NULL,
    note TEXT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cancellations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    reason TEXT,
    cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE usage_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_id INT NOT NULL,
    total_bookings INT DEFAULT 0,
    total_hours FLOAT DEFAULT 0,
    report_month TINYINT,
    report_year INT,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 12. DỮ LIỆU MẪU (SEED DATA)
-- =========================================================

INSERT INTO users (username, password, fullname, email, role) VALUES 
('admin', '123456', 'Quản trị viên', 'admin@campus.edu.vn', 'admin'),
('user', '123456', 'Người dùng Thử nghiệm', 'user@campus.edu.vn', 'user'),
('teacher01', '123456', 'Giảng viên Nguyễn Văn A', 'teacher@campus.edu.vn', 'teacher');

INSERT INTO resource_categories (name, icon_class) VALUES 
('Phòng tự học nhóm', 'fas fa-users'),
('Sân bóng đá', 'fas fa-futbol'),
('Phòng máy tính', 'fas fa-desktop'),
('Hội trường/Phòng họp', 'fas fa-users-viewfinder'),
('Phòng Studio/Media', 'fas fa-video');

INSERT INTO resources (category_id, name, location, capacity, description, image_url, slot_type, is_available) VALUES 
(3, 'Phòng Máy 01', 'Tòa A, Tầng 2', 40, NULL, NULL, 'academic', 1),
(2, 'Sân Bóng Đá A', 'Khu thể thao', 22, NULL, NULL, 'sport', 1),
(4, 'Hội trường A1', 'Tòa A, Tầng 1', 150, NULL, NULL, 'academic', 1),
(5, 'Phòng Studio', 'Tòa C, Tầng 4', 10, NULL, NULL, 'academic', 1),
(3, 'Phòng Máy 02', 'Tòa A, Tầng 2', 30, NULL, NULL, 'academic', 1),
(3, 'Phòng Máy Thực Hành Đồ Họa', 'Tòa A, Tầng 3', 45, NULL, NULL, 'academic', 1),
(3, 'Phòng Lab Nghiên Cứu AI & Data', 'Tòa A, Tầng 4', 25, NULL, NULL, 'academic', 1),
(1, 'Phòng Giảng Đường B2.01', 'Tòa B, Tầng 2', 80, NULL, NULL, 'academic', 1),
(1, 'Phòng Giảng Đường C1.02', 'Tòa C, Tầng 1', 120, NULL, NULL, 'academic', 1),
(1, 'Phòng Thảo Luận Nhóm 101', 'Thư viện, Tầng 2', 10, NULL, NULL, 'academic', 1),
(1, 'Phòng Thảo Luận Nhóm 102', 'Thư viện, Tầng 2', 12, NULL, NULL, 'academic', 1),
(1, 'Không Gian Tự Học Co-working', 'Thư viện, Tầng 1', 150, NULL, NULL, 'academic', 1),
(5, 'Phòng Sinh Hoạt Câu Lạc Bộ', 'Nhà văn hóa SV, Tầng 1', 40, NULL, NULL, 'academic', 1),
(4, 'Hội Trường Lớn Khu Trung Tâm', 'Tòa Trung Tâm, Tầng 1', 500, NULL, NULL, 'academic', 1),
(4, 'Phòng Hội Thảo Quốc Tế', 'Tòa Trung Tâm, Tầng 3', 70, NULL, NULL, 'academic', 1),
(5, 'Phòng Studio Podcast & Media', 'Tòa C, Tầng 4', 6, NULL, NULL, 'academic', 1),
(2, 'Sân Bóng Đá Nhân Tạo B', 'Khu thể thao ngoài trời', 22, NULL, NULL, 'sport', 1),
(2, 'Sân Bóng Rổ Sinh Viên', 'Khu thể thao ngoài trời', 15, NULL, NULL, 'sport', 1),
(2, 'Sân Cầu Lông Trong Nhà A', 'Nhà thi đấu, Tầng 1', 4, NULL, NULL, 'sport', 1),
(2, 'Sân Cầu Lông Trong Nhà B', 'Nhà thi đấu, Tầng 1', 4, NULL, NULL, 'sport', 1);

INSERT INTO time_slots (start_time, end_time, label, slot_type, is_peak) VALUES 
-- Khung giờ học tập
('07:30:00', '09:30:00', 'Ca 1 (Sáng)', 'academic', FALSE),
('09:45:00', '11:45:00', 'Ca 2 (Sáng)', 'academic', TRUE),
('13:30:00', '15:30:00', 'Ca 3 (Chiều)', 'academic', FALSE),
('15:45:00', '17:45:00', 'Ca 4 (Chiều)', 'academic', TRUE);

-- =========================================================
-- 13. CHÍNH SÁCH MẪU
-- =========================================================
INSERT INTO booking_policies (category_id, user_role, max_slots_per_week, requires_approval) VALUES 
(1, 'user', 5, FALSE), -- Phòng tự học: không cần duyệt
(3, 'user', 2, TRUE);  -- Phòng Lab/Máy tính: CẦN PHÊ DUYỆT
