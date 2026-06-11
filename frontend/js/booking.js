/**
 * File: frontend/assets/js/booking.js
 */

let allResources = []; // Lưu trữ cache để tìm kiếm/lọc nhanh

document.addEventListener('DOMContentLoaded', async () => {
    // Tải lần đầu
    await hienThiDanhSachPhong();
    await taiDanhSachCa();

    // Hiển thị nút thêm tài nguyên nếu là admin
    const user = typeof layUser === 'function' ? layUser() : null;
    if (user && user.role === 'admin') {
        const adminBtn = document.getElementById('btn-admin-add-resource');
        if (adminBtn) adminBtn.style.display = 'block';
    }

    // Thêm listener cho tìm kiếm
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            renderResources(filterResources(e.target.value, 'all'));
        });
    }

    // Tự động cập nhật mỗi 30 giây
    setInterval(async () => {
        if (window.location.pathname.includes('resources.html')) {
            await hienThiDanhSachPhong();
        }
    }, 30000);
});

async function hienThiDanhSachPhong() {
    const ketQua = await goiApi('/resources', 'GET');
    if (ketQua.status === 'success') {
        allResources = ketQua.data;
        renderResources(allResources);
    } else {
        const container = document.getElementById('resource-list');
        if (container) container.innerHTML = `<p style="color:red;">Lỗi kết nối: ${ketQua.message}</p>`;
    }
}

function renderResources(danhSach) {
    const container = document.getElementById('resource-list');
    if (!container) return;

    if (danhSach.length === 0) {
        container.innerHTML = '<p style="text-align: center; grid-column: 1/-1; padding: 50px; color: #64748b;">Không tìm thấy không gian nào khớp với yêu cầu.</p>';
        return;
    }

    const user = typeof layUser === 'function' ? layUser() : null;

    container.innerHTML = danhSach.map(item => {
        let icon = 'fas fa-laptop-code';
        let iconClass = 'icon-blue';
        const nameLower = item.name.toLowerCase();
        
        if (nameLower.includes('sân')) {
            icon = 'fas fa-futbol';
            iconClass = 'icon-green';
        }
        else if (nameLower.includes('hội trường') || nameLower.includes('họp')) {
            icon = 'fas fa-users';
            iconClass = 'icon-purple';
        }
        else if (nameLower.includes('studio') || nameLower.includes('media')) {
            icon = 'fas fa-camera-retro';
            iconClass = 'icon-purple';
        }
        else if (nameLower.includes('máy')) {
            icon = 'fas fa-desktop';
            iconClass = 'icon-blue';
        }
        else if (nameLower.includes('tự học') || nameLower.includes('nhóm')) {
            icon = 'fas fa-book-reader';
            iconClass = 'icon-blue';
        }

        const statusClass = item.is_available == 1 ? 'status-available' : 'status-busy';
        const statusText = item.is_available == 1 ? '' : 'Đang bận/Bảo trì';

        let adminActionsHtml = '';
        if (user && user.role === 'admin') {
            adminActionsHtml = `
                <div style="display: flex; gap: 8px; margin-bottom: 12px; width: 100%;">
                    <button class="btn-cancel-v2" style="flex: 1; padding: 10px; font-size: 0.85rem; border-color: rgba(79, 70, 229, 0.2); color: var(--primary);" onclick="event.stopPropagation(); moResourceModal(${item.id})">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn-cancel-v2" style="flex: 1; padding: 10px; font-size: 0.85rem; border-color: rgba(239, 68, 68, 0.2); color: var(--danger); background: #fef2f2;" onclick="event.stopPropagation(); xoaResource(${item.id})">
                        <i class="fas fa-trash-alt"></i> Xóa
                    </button>
                </div>
            `;
        }

        return `
            <div class="f-card resource-card">
                <div class="status-badge ${statusClass}">
                    <i></i> ${statusText}
                </div>
                
                <div class="resource-icon-v2 ${iconClass}">
                    <i class="${icon}"></i>
                </div>
                
                <h3 style="text-align: left; margin-bottom: 20px;">${item.name}</h3>
                
                <div class="resource-details">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span class="detail-label">Vị trí</span>
                        <span class="detail-value">${item.location}</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-users"></i>
                        <span class="detail-label">Sức chứa</span>
                        <span class="detail-value">${item.capacity} người</span>
                    </div>
                </div>

                ${adminActionsHtml}

                <button class="btn-confirm" 
                        onclick="moModalDatPhong(${item.id}, '${item.name}')"
                        ${item.is_available == 0 ? 'disabled' : ''}>
                    ${item.is_available == 1 ? 'Đặt Chỗ Ngay' : 'Tạm khóa'}
                </button>
            </div>
        `;
    }).join('');
}

function filterResources(keyword, category) {
    return allResources.filter(item => {
        const matchKeyword = item.name.toLowerCase().includes(keyword.toLowerCase()) || 
                           item.location.toLowerCase().includes(keyword.toLowerCase());
        
        let matchCategory = false;
        const nameLower = item.name.toLowerCase();
        
        if (category === 'all') {
            matchCategory = true;
        } else if (category === 'Phòng tự học nhóm' && (nameLower.includes('tự học') || nameLower.includes('nhóm') || nameLower.includes('thảo luận'))) {
            matchCategory = true;
        } else if (category === 'Sân bóng đá' && nameLower.includes('sân')) {
            matchCategory = true;
        } else if (category === 'Phòng máy tính' && nameLower.includes('máy')) {
            matchCategory = true;
        } else if (category === 'Hội trường/Phòng họp' && (nameLower.includes('hội trường') || nameLower.includes('họp'))) {
            matchCategory = true;
        } else if (category === 'Phòng Studio/Media' && (nameLower.includes('studio') || nameLower.includes('media'))) {
            matchCategory = true;
        }

        return matchKeyword && matchCategory;
    });
}

function filterByCategory(category, btn) {
    // Update UI buttons
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const keyword = document.getElementById('search-input')?.value || '';
    renderResources(filterResources(keyword, category));
}

let allTimeSlots = []; // Cache lưu trữ tất cả khung giờ lấy từ DB

async function taiDanhSachCa() {
    const ketQua = await goiApi('/time-slots', 'GET');
    if (ketQua.status === 'success') {
        allTimeSlots = ketQua.data;
    }
}

function moModalDatPhong(id, tenPhong) {
    const modal = document.getElementById('booking-modal');
    if (modal) {
        document.getElementById('modal-title').innerText = "Đặt lịch: " + tenPhong;
        document.getElementById('target-resource-id').value = id;
        
        // Thiết lập ngày tối thiểu là hôm nay và tự động chọn hôm nay
        const inputDate = document.getElementById('input-date');
        if (inputDate) {
            const today = new Date();
            const yyyy = today.getFullYear();
            let mm = today.getMonth() + 1;
            let dd = today.getDate();
            
            if (dd < 10) dd = '0' + dd;
            if (mm < 10) mm = '0' + mm;
            
            const todayStr = `${yyyy}-${mm}-${dd}`;
            inputDate.min = todayStr;
            inputDate.value = todayStr;
        }
        
        // Tìm thông tin phòng để xác định loại ca phù hợp
        const resource = allResources.find(item => item.id == id);
        const slotsGridContainer = document.getElementById('slots-grid-container');
        const hiddenSelectSlot = document.getElementById('select-slot');
        
        if (slotsGridContainer && hiddenSelectSlot) {
            if (allTimeSlots.length === 0) {
                slotsGridContainer.innerHTML = '<p style="grid-column: 1/-1; font-size: 0.85rem; color: var(--slate);">Đang tải danh sách ca...</p>';
                hiddenSelectSlot.value = '';
            } else {
                // Xác định xem có phải phòng thể thao/sân bóng không
                const isSportResource = resource && (
                    resource.slot_type === 'sport' || 
                    resource.name.toLowerCase().includes('sân') || 
                    resource.name.toLowerCase().includes('cầu lông') ||
                    resource.name.toLowerCase().includes('bóng rổ') ||
                    resource.name.toLowerCase().includes('thể thao') ||
                    resource.name.toLowerCase().includes('thể dục') ||
                    resource.location.toLowerCase().includes('thể thao') ||
                    resource.location.toLowerCase().includes('nhà thi đấu')
                );
                
                // Lọc ca: phòng thể thao chỉ hiện ca 'sport'; các phòng khác chỉ hiện ca 'academic'
                const filteredSlots = allTimeSlots.filter(ca => {
                    const isSportSlot = ca.slot_type === 'sport' || ca.label.toLowerCase().includes('thể thao');
                    return isSportResource ? isSportSlot : !isSportSlot;
                });
                
                if (filteredSlots.length === 0) {
                    slotsGridContainer.innerHTML = '<p style="grid-column: 1/-1; font-size: 0.85rem; color: var(--slate);">Không có ca phù hợp cho không gian này</p>';
                    hiddenSelectSlot.value = '';
                    capNhatGhiChuKhungGio('');
                } else {
                    slotsGridContainer.innerHTML = filteredSlots.map(ca => {
                        const timeStr = ca.start_time.substring(0, 5) + ' - ' + ca.end_time.substring(0, 5);
                        
                        // Loại bỏ text thừa trong label để hiển thị ngắn gọn, thanh lịch hơn
                        let cleanLabel = ca.label;
                        cleanLabel = cleanLabel.replace(/\s*\(Giờ cao điểm\)/gi, '');
                        cleanLabel = cleanLabel.replace(/\s*\(giờ cao điểm\)/gi, '');
                        cleanLabel = cleanLabel.trim();
                        
                        return `
                            <div class="slot-pill" data-id="${ca.id}" onclick="chonSlotPill(this, ${ca.id})">
                                <div class="slot-label">${cleanLabel}</div>
                                <div class="slot-time"><i class="far fa-clock"></i> ${timeStr}</div>
                                ${ca.is_peak == 1 ? '<span class="slot-peak-tag"><i class="fas fa-exclamation-circle"></i> Cao điểm</span>' : ''}
                            </div>
                        `;
                    }).join('');
                    
                    // Tự động chọn ca đầu tiên
                    const firstPill = slotsGridContainer.querySelector('.slot-pill');
                    if (firstPill) {
                        firstPill.click();
                    } else {
                        hiddenSelectSlot.value = '';
                        capNhatGhiChuKhungGio('');
                    }
                }
            }
        }

        // Tự động kiểm tra hiển thị trường lớp học cho Giảng viên
        const user = typeof layUser === 'function' ? layUser() : null;
        const teacherClassGroup = document.getElementById('teacher-class-group');
        const inputBookedName = document.getElementById('input-booked-name');

        if (user) {
            inputBookedName.value = user.fullname;
            if (user.role === 'teacher') {
                if (teacherClassGroup) teacherClassGroup.style.display = 'block';
            } else {
                if (teacherClassGroup) teacherClassGroup.style.display = 'none';
            }
        }
        
        modal.style.display = 'block';
    }
}

window.chonSlotPill = function(element, slotId) {
    // Xóa active khỏi tất cả các pill
    const container = document.getElementById('slots-grid-container');
    if (container) {
        container.querySelectorAll('.slot-pill').forEach(pill => {
            pill.classList.remove('active');
        });
    }
    
    // Thêm active vào pill hiện tại
    element.classList.add('active');
    
    // Cập nhật giá trị cho input hidden để form lấy dữ liệu
    const hiddenSelectSlot = document.getElementById('select-slot');
    if (hiddenSelectSlot) {
        hiddenSelectSlot.value = slotId;
    }
    
    // Cập nhật ghi chú khung giờ
    capNhatGhiChuKhungGio(slotId);
}

function capNhatGhiChuKhungGio(slotId) {
    const noteContainer = document.getElementById('slot-info-note');
    if (!noteContainer) return;
    
    if (!slotId) {
        noteContainer.innerHTML = '';
        return;
    }
    
    const ca = allTimeSlots.find(c => c.id == slotId);
    if (!ca) {
        noteContainer.innerHTML = '';
        return;
    }
    
    // Tạo style keyframe nếu chưa tồn tại
    if (!document.getElementById('fadeIn-style')) {
        const style = document.createElement('style');
        style.id = 'fadeIn-style';
        style.innerHTML = `
            @keyframes fadeInNote {
                from { opacity: 0; transform: translateY(-4px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
    }
    
    if (ca.is_peak == 1) {
        noteContainer.innerHTML = `
            <div class="peak-note-badge" style="background: rgba(239, 68, 68, 0.05); color: #991b1b; border: 1px solid rgba(239, 68, 68, 0.15); padding: 10px 14px; border-radius: 10px; display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.8rem; animation: fadeInNote 0.3s ease; line-height: 1.4;">
                <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 0.95rem;"></i>
                <span>Khung giờ cao điểm: Áp dụng quy định giới hạn lượt đặt của Nhà trường.</span>
            </div>
        `;
    } else {
        noteContainer.innerHTML = `
            <div class="normal-note-badge" style="background: rgba(16, 185, 129, 0.04); color: #065f46; border: 1px solid rgba(16, 185, 129, 0.12); padding: 10px 14px; border-radius: 10px; display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.8rem; animation: fadeInNote 0.3s ease; line-height: 1.4;">
                <i class="fas fa-info-circle" style="color: #10b981; font-size: 0.95rem;"></i>
                <span>Khung giờ tiêu chuẩn: Không giới hạn chính sách đặt chỗ.</span>
            </div>
        `;
    }
}

function dongModal() {
    const modal = document.getElementById('booking-modal');
    if (modal) modal.style.display = 'none';
    const noteContainer = document.getElementById('slot-info-note');
    if (noteContainer) noteContainer.innerHTML = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const bookingModal = document.getElementById('booking-modal');
    const resourceModal = document.getElementById('resource-modal');
    if (event.target == bookingModal) {
        dongModal();
    }
    if (event.target == resourceModal) {
        dongResourceModal();
    }
}

const formDatCho = document.getElementById('form-dat-cho');
if (formDatCho) {
    formDatCho.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const resourceId = document.getElementById('target-resource-id').value;
        const bookingDate = document.getElementById('input-date').value;
        const slotId = document.getElementById('select-slot').value;
        let bookedForName = document.getElementById('input-booked-name').value;
        const teacherClassInput = document.getElementById('input-teacher-class');

        const user = layUser();
        if (!user) {
            showToast("Vui lòng đăng nhập để đặt phòng!", "error");
            return;
        }

        // Nếu là giảng viên và có điền tên lớp
        if (user.role === 'teacher' && teacherClassInput && teacherClassInput.value.trim() !== '') {
            bookedForName = `Lớp ${teacherClassInput.value.trim()} (GV ${bookedForName})`;
        }

        const data = {
            resource_id: resourceId,
            booking_date: bookingDate,
            slot_id: slotId,
            user_id: user.id,
            booked_for_name: bookedForName,
            reason: "Đặt lịch học tập/sinh hoạt/giảng dạy"
        };

        const response = await goiApi('/bookings', 'POST', data);
        if (response.status === 'success') {
            showToast(response.message || "Đặt chỗ thành công!");
            dongModal();
            formDatCho.reset();
            await hienThiDanhSachPhong();
        } else {
            showToast("Lỗi: " + response.message, "error");
        }
    });
}

/* =========================================================================
   CÁC HÀM QUẢN LÝ TÀI NGUYÊN (DÀNH CHO ADMIN)
   ========================================================================= */

function moResourceModal(id = null) {
    const modal = document.getElementById('resource-modal');
    if (!modal) return;
    
    const form = document.getElementById('form-resource');
    form.reset();
    
    if (id) {
        const item = allResources.find(r => r.id == id);
        if (item) {
            document.getElementById('resource-modal-title').innerText = "Sửa thông tin tài nguyên";
            document.getElementById('resource-id').value = item.id;
            document.getElementById('res-name').value = item.name;
            document.getElementById('res-category').value = item.category_id;
            document.getElementById('res-location').value = item.location;
            document.getElementById('res-capacity').value = item.capacity;
            document.getElementById('res-description').value = item.description || '';
            document.getElementById('res-slot-type').value = item.slot_type;
            document.getElementById('res-available').value = item.is_available;
        }
    } else {
        document.getElementById('resource-modal-title').innerText = "Thêm tài nguyên mới";
        document.getElementById('resource-id').value = '';
    }

    // Đồng bộ lại tất cả custom dropdowns sau khi reset form hoặc set value
    form.querySelectorAll('.select-filter.customized').forEach(select => {
        select.dispatchEvent(new Event('change'));
    });

    modal.style.display = 'block';
}

function dongResourceModal() {
    const modal = document.getElementById('resource-modal');
    if (modal) modal.style.display = 'none';
}

async function xoaResource(id) {
    if (!confirm("Bạn có chắc chắn muốn xóa tài nguyên này không? Hành động này không thể hoàn tác!")) return;
    
    try {
        const resp = await goiApi('/admin/resources', 'DELETE', { id });
        if (resp.status === 'success') {
            showToast("Xóa tài nguyên thành công!");
            await hienThiDanhSachPhong();
        } else {
            showToast(resp.message, 'error');
        }
    } catch (e) {
        showToast("Lỗi kết nối máy chủ", 'error');
    }
}

// Lắng nghe sự kiện submit của form tài nguyên
const formResource = document.getElementById('form-resource');
if (formResource) {
    formResource.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('resource-id').value;
        const name = document.getElementById('res-name').value;
        const category_id = document.getElementById('res-category').value;
        const location = document.getElementById('res-location').value;
        const capacity = document.getElementById('res-capacity').value;
        const description = document.getElementById('res-description').value;
        const slot_type = document.getElementById('res-slot-type').value;
        const is_available = document.getElementById('res-available').value;

        const data = {
            id,
            name,
            category_id,
            location,
            capacity,
            description,
            slot_type,
            is_available
        };

        let resp;
        if (id) {
            resp = await goiApi('/admin/resources', 'PUT', data);
        } else {
            resp = await goiApi('/admin/resources', 'POST', data);
        }

        if (resp.status === 'success') {
            showToast(id ? "Cập nhật tài nguyên thành công!" : "Thêm tài nguyên thành công!");
            dongResourceModal();
            await hienThiDanhSachPhong();
        } else {
            showToast(resp.message, 'error');
        }
    });
}