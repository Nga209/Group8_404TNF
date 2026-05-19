<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sản phẩm | MVC Project</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>Danh Sách Sản Phẩm</h1>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Quản lý kho hàng của bạn một cách thông minh</p>
            </div>
            <a href="<?= BASE_URL ?>/product/create" class="btn btn-primary">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 8px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Thêm sản phẩm
            </a>
        </header>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá bán</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['products'] as $product): ?>
                <tr>
                    <td><span style="color: var(--text-muted);">#<?= $product['id'] ?></span></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($product['category']) ?></span></td>
                    <td><span class="price"><?= number_format($product['price'], 0, ',', '.') ?> VNĐ</span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($data['products'])): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);">Chưa có sản phẩm nào.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
