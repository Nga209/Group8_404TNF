/* ================================================================
   FILE: api.js - NỀN TẢNG KẾT NỐI API DÙNG CHUNG
   ================================================================ */
// Sử dụng đường dẫn tương đối (từ thư mục /pages/ hoặc /index/ đi ngược lên)
// Điều này giúp loại bỏ 100% các lỗi liên quan đến CORS hay port của XAMPP.
// Tự động xác định đường dẫn gốc để chạy được trên cả Windows (INS3064) và MacBook (GROUP8_404TNF)
const currentPath = window.location.pathname;
const folderProject = "/campus_services_booking";
const viTriFolder = currentPath.indexOf(folderProject);
var DUONG_DAN_GOC = (viTriFolder !== -1) 
    ? currentPath.substring(0, viTriFolder + folderProject.length) + "/backend/public"
    : "/campus_services_booking/backend/public";

console.log("Hệ thống API đang kết nối tới:", DUONG_DAN_GOC);

async function goiApi(duongDan, phuongThuc = 'GET', duLieu = null) {
    // Tự động chuẩn hóa đường dẫn
    if (!duongDan.startsWith('/')) {
        duongDan = '/' + duongDan;
    }

    const cauHinh = {
        method: phuongThuc,
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include' // Bắt buộc phải có để gửi Session giữa các folder
    };

    if (duLieu && (phuongThuc === 'POST' || phuongThuc === 'PUT')) {
        cauHinh.body = JSON.stringify(duLieu);
    }
    
    try {
        const phanHoi = await fetch(`${DUONG_DAN_GOC}${duongDan}`, cauHinh);
        const textData = await phanHoi.text();
        
        if (!phanHoi.ok) {
            let errorData = {};
            try { errorData = JSON.parse(textData); } catch(e) {}
            return { 
                status: 'error', 
                message: errorData.message || 'Lỗi HTTP ' + phanHoi.status + '. Dữ liệu: ' + textData.substring(0, 50) 
            };
        }

        try {
            return JSON.parse(textData);
        } catch (parseError) {
            return {
                status: 'error',
                message: 'Máy chủ trả về dữ liệu không hợp lệ: ' + textData.substring(0, 100)
            };
        }
    } catch (loi) {
        console.error("Lỗi kết nối:", loi);
        return { 
            status: 'error', 
            message: 'Không thể kết nối XAMPP! Chi tiết: ' + loi.message 
        };
    }
}