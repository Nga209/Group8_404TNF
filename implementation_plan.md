# Kế hoạch triển khai Bộ lọc Tháng cho Báo cáo & Khắc phục hiển thị Thống kê Dashboard

Kế hoạch này chi tiết việc sửa lỗi các số liệu thống kê tự động quay về 0 khi sang tháng mới và thêm bộ lọc tháng ở trang Báo cáo.

## Nội dung thay đổi đề xuất

### 1. Backend Changes

#### [MODIFY] [ReportController.php](file:///c:/huyenthuong/hthuong/htdocs/Group8_404TNF/campus_services_booking/backend/app/controllers/ReportController.php)
Cập nhật phương thức `getSummary()`:
- Đọc tham số truy vấn `month` từ URL (ví dụ: `?month=2026-05`). Nếu không có, mặc định sử dụng tháng hiện tại (`YEAR(CURDATE())` và `MONTH(CURDATE())`).
- Áp dụng tham số lọc năm/tháng vào các câu lệnh SQL thống kê thay vì sử dụng cứng `YEAR(CURDATE())` và `MONTH(CURDATE())`.
- **Tách biệt thống kê trạng thái "Đang chờ duyệt" (Pending):** Đếm tất cả các đơn có trạng thái `pending` của mọi thời gian (hoặc của các tháng trước) để Admin/Teacher không bị bỏ sót các đơn chưa duyệt khi sang tháng mới.
- Đảm bảo các thống kê khác như: `daily_stats`, `user_type_distribution`, `top_users`, `top_resources`, `slot_usage_stats` đều lọc đúng theo tháng được chọn.

---

### 2. Frontend Changes

#### [MODIFY] [reports.html](file:///c:/huyenthuong/hthuong/htdocs/Group8_404TNF/campus_services_booking/frontend/pages/reports.html)
- Thêm một thẻ chọn tháng `<input type="month" id="month-filter">` trên phần tiêu đề của trang Báo cáo.
- Khi tải trang:
  - Tự động điền giá trị mặc định cho ô chọn là tháng hiện tại (dạng `YYYY-MM`).
  - Lắng nghe sự kiện `change` trên ô chọn. Khi người dùng thay đổi tháng, gọi lại hàm `initReport(selectedMonth)` để tải lại dữ liệu mới và cập nhật các biểu đồ.
- Sửa lại hàm `initReport(selectedMonth)` để gửi kèm tham số `?month=selectedMonth` khi gọi API `/admin/stats`.

#### [MODIFY] [dashboard.js](file:///c:/huyenthuong/hthuong/htdocs/Group8_404TNF/campus_services_booking/frontend/assets/js/dashboard.js)
- Cập nhật hàm gọi API `/admin/stats` trên Dashboard:
  - Thẻ **Đang chờ duyệt** sẽ hiển thị tổng số đơn chờ duyệt trên toàn hệ thống (All-time) để tránh bị sót đơn của tháng trước.

---

## Kịch bản Kiểm thử & Xác minh

### 1. Xác minh trên trang Dashboard (Tổng quan)
- Đăng nhập tài khoản Admin.
- Truy cập trang Dashboard.
- Xác nhận thẻ **Đang chờ duyệt** hiển thị đúng số lượng đơn chờ duyệt thực tế (kể cả các đơn từ tháng 5/2026).

### 2. Xác minh trên trang Báo cáo tổng hợp
- Truy cập trang Báo cáo.
- Xác nhận ô chọn tháng mặc định hiển thị tháng hiện tại (Tháng 6/2026) và các số liệu ban đầu là 0 (hoặc khớp với tháng 6).
- Thay đổi ô chọn tháng sang **Tháng 5/2026**.
- Xác nhận:
  - Các số liệu tổng quát cập nhật đúng số đơn của tháng 5.
  - Các biểu đồ (Hiệu suất phòng & sân, Tỉ lệ phản hồi, Khung giờ cao điểm) vẽ lại chính xác dữ liệu của tháng 5/2026.