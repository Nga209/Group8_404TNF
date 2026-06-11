async function taiThongKeDashboard() {
    try {
        // 1. Kiểm tra quyền Admin trước khi gọi API (Sử dụng hàm từ auth.js)
        const user = layUser();
        const role = user ? user.role : null;
        
        if (role !== 'admin' && role !== 'teacher') {
            alert("Bạn không có quyền truy cập trang này!");
            window.location.href = '../index/index.html';
            return;
        }

        // Hiển thị lời chào cá nhân hóa
        const welcomeTitle = document.querySelector('.premium-header h1');
        if (welcomeTitle && user) {
            const roleLabels = {
                'admin': 'Quản trị viên',
                'teacher': 'Giảng viên',
                'user': 'Sinh viên'
            };
            const displayName = user.fullname || roleLabels[role] || 'Admin';
            welcomeTitle.innerHTML = `Chào mừng bạn quay lại, ${displayName}! ✨`;
        }

        // 2. Gọi đến API báo cáo/thống kê ở Backend
        const thongKe = await goiApi('/admin/stats'); 
        
        if (thongKe.status === 'success') {
            const data = thongKe.data;
            // Cập nhật các con số tổng quát
            document.getElementById('tong-don-dat').innerText = data.overview.total_bookings;
            document.getElementById('don-cho-duyet').innerText = data.overview.pending_requests;
            document.getElementById('so-tai-nguyen').innerText = data.overview.approved_requests; // Đổi thành Đơn đã duyệt cho ý nghĩa
            const newUsersEl = document.getElementById('so-nguoi-dung');
            if (newUsersEl) {
                newUsersEl.innerText = data.overview.total_users;
            }

            const recentList = document.getElementById('recent-activity-list');
            if (recentList && data.recent_activity) {
                if (data.recent_activity.length === 0) {
                    recentList.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 20px;">Chưa có hoạt động nào.</p>';
                    return;
                }

                recentList.innerHTML = data.recent_activity.map(act => {
                    const name = act.booked_for_name || act.fullname || 'User';
                    const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random&color=fff`;
                    const statusClass = act.status === 'pending' ? 'bg-warning-light' : (act.status === 'approved' ? 'bg-success-light' : 'bg-danger-light');
                    const statusText = act.status === 'pending' ? 'Mới' : (act.status === 'approved' ? 'Đã duyệt' : 'Từ chối');
                    
                    const dateObj = new Date(act.created_at);
                    const formattedTime = dateObj.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    const formattedDate = dateObj.toLocaleDateString('vi-VN');
                    const formattedDateTime = `${formattedTime} - ${formattedDate}`;
                    
                    return `
                        <div class="activity-item">
                            <img src="${avatarUrl}" class="user-circle">
                            <div class="act-info">
                                <p><strong>${name}</strong> đặt ${act.resource_name}</p>
                                <span>${formattedDateTime}</span>
                            </div>
                            <span class="badge ${statusClass}">${statusText}</span>
                        </div>
                    `;
                }).join('');
            }

            // 3. Biểu đồ thống kê tuần này dạng động
            const ctx = document.getElementById('proChart').getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
            gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

            const trendLabels = ['Th 2', 'Th 3', 'Th 4', 'Th 5', 'Th 6', 'Th 7', 'CN'];
            const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const trendData = dayOrder.map(d => {
                const found = data.daily_stats ? data.daily_stats.find(s => s.day === d) : null;
                return found ? parseInt(found.count) : 0;
            });

            const maxVal = Math.max(...trendData, 5);
            const yMax = Math.ceil(maxVal * 1.2);

            if (window.myDashboardChart) {
                window.myDashboardChart.destroy();
            }

            window.myDashboardChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Số đơn đặt',
                        data: trendData,
                        borderColor: '#6366f1',
                        borderWidth: 4,
                        fill: true,
                        backgroundColor: gradient,
                        tension: 0.35,
                        pointRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 3,
                        pointHoverRadius: 9,
                        pointHoverBackgroundColor: '#6366f1',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13, weight: '600' },
                            bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                            padding: 12,
                            displayColors: false,
                            cornerRadius: 8,
                            boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: yMax,
                            grid: { 
                                color: 'rgba(226, 232, 240, 0.8)', 
                                drawBorder: false,
                                borderDash: [5, 5]
                            },
                            ticks: { 
                                stepSize: Math.ceil(yMax / 5) || 1,
                                precision: 0,
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '550' },
                                color: '#64748b'
                            }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: {
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '550' },
                                color: '#64748b'
                            }
                        }
                    }
                }
            });
        } else {
            console.error("Lỗi lấy thống kê:", thongKe.message);
        }
    } catch (error) {
        console.error("Lỗi kết nối hệ thống:", error);
    }
}

// 3. Kết nối Real-time SSE để cập nhật dưới 1 giây
function ketNoiRealtime() {
    const sseSource = new EventSource(`${DUONG_DAN_GOC}/sse/updates`, { withCredentials: true });

    sseSource.addEventListener('update', (e) => {
        console.log("⚡ Nhận tín hiệu cập nhật Real-time:", e.data);
        taiThongKeDashboard();
    });

    sseSource.onerror = () => {
        console.warn("Mất kết nối SSE, đang thử kết nối lại...");
        sseSource.close();
        setTimeout(ketNoiRealtime, 3000); // Thử lại sau 3 giây nếu lỗi
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('dashboard.html')) {
        taiThongKeDashboard();
        ketNoiRealtime();
    }
});