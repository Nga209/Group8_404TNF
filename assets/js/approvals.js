/* ================================================================
   FILE: approvals.js - QUẢN LÝ PHÊ DUYỆT ĐƠN ĐẶT PHÒNG
   ================================================================ */

document.addEventListener('DOMContentLoaded', () => {
    taiDanhSachDuyet();

    // Tự động làm mới danh sách duyệt mỗi 30 giây để cập nhật đơn mới
    setInterval(() => {
        if (window.location.pathname.includes('approvals.html')) {
            taiDanhSachDuyet();
        }
    }, 30000);
});

async function taiDanhSachDuyet() {
    const container = document.getElementById('danh-sach-duyet');
    if (!container) return;

    try {
        const phanHoi = await goiApi('/approvals', 'GET');
        
        if (phanHoi.status === 'success') {
            const data = phanHoi.data;
            if (data.length === 0) {
                container.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color: #64748b;">Không có đơn nào đang chờ duyệt.</td></tr>';
                return;
            }

            container.innerHTML = data.map(item => `
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;">${item.booked_for_name || item.fullname}</div>
                        <div style="font-size: 0.8rem; color: #94a3b8;">Người đặt: ${item.fullname} (MSV: ${item.username || 'N/A'})</div>
                    </td>
                    <td style="color: #475569; font-weight: 500;">${item.resource_name}</td>
                    <td>
                        <div style="color: #1e293b;">${formatDate(item.booking_date)}</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Slot: ${item.slot_id}</div>
                    </td>
                    <td><span class="badge badge-${item.status}">${translateStatus(item.status)}</span></td>
                    <td>
                        ${item.status === 'pending' ? `
                            <div class="action-btns">
                                <button class="btn-icon btn-approve" onclick="xuLyDuyet(${item.id}, 'approved')" title="Duyệt"><i class="fas fa-check"></i></button>
                                <button class="btn-icon btn-reject" onclick="xuLyDuyet(${item.id}, 'rejected')" title="Từ chối"><i class="fas fa-times"></i></button>
                            </div>
                        ` : '<span style="color: #94a3b8; font-size: 0.85rem; font-style: italic;">Đã xử lý</span>'}
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error("Lỗi tải danh sách duyệt:", error);
    }
}

// Hàm hỗ trợ định dạng ngày
function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('vi-VN');
}

// Hàm hỗ trợ dịch trạng thái
function translateStatus(status) {
    switch(status) {
        case 'pending': return 'Chờ duyệt';
        case 'approved': return 'Đã duyệt';
        case 'rejected': return 'Từ chối';
        default: return status;
    }
}

async function xuLyDuyet(bookingId, status) {
    const actionText = status === 'approved' ? 'DUYỆT' : 'TỪ CHỐI';
    if (!confirm(`Bạn có chắc chắn muốn ${actionText} yêu cầu này không?`)) return;

    try {
        const result = await goiApi('/bookings/approve', 'POST', {
            booking_id: bookingId,
            status: status
        });

        if (result.status === 'success') {
            showToast(`Đã ${actionText.toLowerCase()} thành công!`);
            taiDanhSachDuyet(); // Làm mới danh sách ngay lập tức
        } else {
            showToast("Lỗi: " + result.message, 'error');
        }
    } catch (error) {
        showToast("Lỗi kết nối máy chủ", 'error');
    }
}
