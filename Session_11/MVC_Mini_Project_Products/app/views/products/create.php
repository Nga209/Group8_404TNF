<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm mới | MVC Project</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <header>
            <div>
                <h1>Thêm Sản Phẩm</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Điền thông tin để đăng ký sản phẩm mới</p>
            </div>
        </header>

        <form action="<?= BASE_URL ?>/product/create" method="POST">
            <div class="form-group">
                <label for="name">Tên sản phẩm</label>
                <input type="text" id="name" name="name" placeholder="Ví dụ: Bàn phím cơ AKKO" required>
            </div>

            <div class="form-group">
                <label for="category">Danh mục</label>
                <select id="category" name="category" required>
                    <option value="" disabled selected>Chọn danh mục</option>
                    <option value="Điện thoại">Điện thoại</option>
                    <option value="Laptop">Laptop</option>
                    <option value="Phụ kiện">Phụ kiện</option>
                    <option value="Khác">Khác</option>
                </select>
            </div>

            <div class="form-group">
                <label for="price">Giá bán (VNĐ)</label>
                <input type="number" id="price" name="price" placeholder="Nhập giá bán" required min="0">
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/product" class="btn btn-outline">Hủy bỏ</a>
                <button type="submit" class="btn btn-primary">Lưu sản phẩm</button>
            </div>
        </form>
    </div>
</body>
</html>
