<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/functions.php';
$pos = new POSFunctions();

$message = '';
$message_type = 'success';
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if ($pos->createProduct($_POST['name'], $_POST['price'], $_POST['stock'], $_POST['category_id'] ?? null)) {
                    $message = 'Produk ditambahkan!';
                }
                break;
            case 'edit':
                if ($pos->updateProduct($_POST['id'], $_POST['name'], $_POST['price'], $_POST['stock'], $_POST['category_id'] ?? null)) {
                    $message = 'Produk diupdate!';
                }
                break;
            case 'quick_update':
                $price = max(0, (float) ($_POST['price'] ?? 0));
                $stock = max(0, (int) ($_POST['stock'] ?? 0));
                if ($pos->updateProductPriceStock($_POST['id'], $price, $stock)) {
                    $message = 'Harga dan stok produk diupdate!';
                }
                break;
            case 'delete':
                if ($pos->deleteProduct($_POST['id'])) {
                    $message = 'Produk dihapus!';
                } else {
                    $message = 'Produk tidak bisa dihapus karena sudah digunakan dalam transaksi. Ubah stok atau data produk jika diperlukan.';
                    $message_type = 'danger';
                }
                break;
            case 'add_cat':
                if ($pos->createCategory($_POST['cat_name'])) {
                    $message = 'Kategori ditambahkan!';
                }
                break;
            case 'edit_cat':
                if ($pos->updateCategory($_POST['cat_id'], $_POST['cat_name'])) {
                    $message = 'Kategori diupdate!';
                }
                break;
        }
    }
}

$edit_prod = null;
if (isset($_GET['edit'])) {
    $edit_prod = $pos->getProduct($_GET['edit']);
}
$edit_cat = null;
if (isset($_GET['edit_cat'])) {
    $edit_cat = $pos->getCategory($_GET['edit_cat']);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #1abc9c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(90deg, var(--primary-color) 0%, #34495e 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-custom .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 1px;
        }

        .navbar-custom .nav-link {
            margin: 0 10px;
            transition: all 0.3s ease;
            border-radius: 5px;
        }

        .navbar-custom .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .page-header {
            background: linear-gradient(135deg, var(--info-color) 0%, #16a085 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .page-header p {
            opacity: 0.9;
            margin: 0;
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 20px;
            border: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #ecf0f1;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-custom {
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-success-custom {
            background: linear-gradient(90deg, var(--success-color) 0%, #229954 100%);
            color: white;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
            color: white;
        }

        .btn-primary-custom {
            background: linear-gradient(90deg, var(--accent-color) 0%, #2980b9 100%);
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
            color: white;
        }

        .btn-warning-custom {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning-custom:hover {
            color: white;
        }

        .btn-danger-custom {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger-custom:hover {
            color: white;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background-color: #f8f9fa;
        }

        .table thead th {
            border: none;
            color: var(--primary-color);
            font-weight: 700;
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            vertical-align: middle;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-stock {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-habis {
            background-color: var(--danger-color);
            color: white;
        }

        .badge-available {
            background-color: var(--success-color);
            color: white;
        }

        .quick-edit-field {
            min-width: 120px;
            max-width: 150px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            padding: 8px 10px;
            font-weight: 600;
        }

        .quick-edit-field:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.18);
            outline: none;
        }

        .quick-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .btn-save-inline {
            background-color: var(--success-color);
            color: white;
            border: 0;
        }

        .btn-save-inline:hover {
            background-color: #229954;
            color: white;
        }

        .category-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .category-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .category-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .category-name {
            font-weight: 600;
            color: var(--primary-color);
        }

        .btn-sm-custom {
            padding: 5px 10px;
            font-size: 0.85rem;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #2980b9;
            color: white;
        }

        .btn-remove {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-remove:hover {
            background-color: #c0392b;
            color: white;
        }

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .form-group-custom {
            margin-bottom: 15px;
        }

        .form-group-custom label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
            display: block;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.75rem;
            }

            .category-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Mutiara Tani
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">
                            <i class="bi bi-boxes"></i> Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="bi bi-receipt"></i> Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">
                            <i class="bi bi-truck"></i> Supplier
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> Kelola User
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-lg">
            <h1><i class="bi bi-boxes"></i> Manajemen Produk & Kategori</h1>
            <p>Kelola daftar produk dan kategori toko Anda</p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container-lg" style="padding-bottom: 40px;">
        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['delete_cat']) && $pos->deleteCategory($_GET['delete_cat'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Kategori dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add/Edit Product Section -->
            <div class="col-lg-6">
                <div class="form-section">
                    <h5 style="margin-bottom: 20px; color: var(--primary-color); font-weight: 700;">
                        <i class="bi bi-<?= $edit_prod ? 'pencil-square' : 'plus-circle' ?>"></i>
                        <?= $edit_prod ? 'Edit Produk' : 'Tambah Produk Baru' ?>
                    </h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $edit_prod ? 'edit' : 'add' ?>">
                        <?php if ($edit_prod): ?>
                            <input type="hidden" name="id" value="<?= $edit_prod['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group-custom">
                            <label><i class="bi bi-tag"></i> Nama Produk</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($edit_prod['name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group-custom">
                            <label><i class="bi bi-folder"></i> Kategori</label>
                            <select class="form-select" name="category_id">
                                <option value="">-- Tanpa Kategori --</option>
                                <?php foreach ($pos->getCategories() as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($edit_prod['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group-custom">
                            <label><i class="bi bi-currency-dollar"></i> Harga (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="price" value="<?= $edit_prod['price'] ?? '' ?>" required>
                        </div>

                        <div class="form-group-custom">
                            <label><i class="bi bi-archive"></i> Stok</label>
                            <input type="number" class="form-control" name="stock" value="<?= $edit_prod['stock'] ?? '' ?>" required>
                        </div>

                        <button type="submit" class="btn btn-custom btn-success-custom w-100">
                            <i class="bi bi-<?= $edit_prod ? 'check-circle' : 'plus-circle' ?>"></i>
                            <?= $edit_prod ? 'Update Produk' : 'Tambah Produk' ?>
                        </button>
                        <?php if ($edit_prod): ?>
                            <a href="products.php" class="btn btn-custom w-100" style="margin-top: 8px; background-color: #95a5a6; color: white;">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Add Category Section -->
            <div class="col-lg-6">
                <div class="form-section">
                    <h5 style="margin-bottom: 20px; color: var(--primary-color); font-weight: 700;">
                        <i class="bi bi-<?= $edit_cat ? 'pencil-square' : 'plus-circle' ?>"></i> <?= $edit_cat ? 'Edit Kategori' : 'Tambah Kategori' ?>
                    </h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $edit_cat ? 'edit_cat' : 'add_cat' ?>">
                        <?php if ($edit_cat): ?>
                            <input type="hidden" name="cat_id" value="<?= $edit_cat['id'] ?>">
                        <?php endif; ?>
                        <div class="form-group-custom">
                            <label><i class="bi bi-folder-plus"></i> Nama Kategori</label>
                            <input type="text" class="form-control" name="cat_name" value="<?= htmlspecialchars($edit_cat['name'] ?? '') ?>" required>
                        </div>
                        <button type="submit" class="btn btn-custom btn-primary-custom w-100">
                            <i class="bi bi-<?= $edit_cat ? 'check-circle' : 'plus-lg' ?>"></i> <?= $edit_cat ? 'Update Kategori' : 'Tambah Kategori' ?>
                        </button>
                        <?php if ($edit_cat): ?>
                            <a href="products.php" class="btn btn-custom w-100" style="margin-top: 8px; background-color: #95a5a6; color: white;">
                                <i class="bi bi-x-circle"></i> Batal Edit Kategori
                            </a>
                        <?php endif; ?>
                    </form>

                    <div style="margin-top: 25px;">
                        <h6 style="color: var(--primary-color); font-weight: 700; margin-bottom: 15px;">
                            <i class="bi bi-list"></i> Kategori Terdaftar
                        </h6>
                        <div class="category-list">
                            <?php if ($pos->getCategories()): ?>
                                <?php foreach ($pos->getCategories() as $cat): ?>
                                    <div class="category-item">
                                        <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                        <span style="display: flex; gap: 6px;">
                                            <a href="?edit_cat=<?= $cat['id'] ?>" class="btn-sm-custom btn-edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="#" class="btn-sm-custom btn-remove" onclick="if(confirm('Hapus kategori ini?')) { location.href='?delete_cat=<?= $cat['id'] ?>'; return false; }">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #7f8c8d; text-align: center; grid-column: 1/-1;">Belum ada kategori</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-table"></i>
                    Daftar Produk
                    <span class="badge bg-info" style="margin-left: auto;"><?= count($pos->getProducts()) ?> Produk</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> ID</th>
                                <th><i class="bi bi-tag"></i> Nama</th>
                                <th><i class="bi bi-folder"></i> Kategori</th>
                                <th><i class="bi bi-currency-dollar"></i> Harga</th>
                                <th><i class="bi bi-archive"></i> Stok</th>
                                <th style="text-align: center;"><i class="bi bi-gear"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $products = $pos->getProducts(); ?>
                            <?php if ($products): ?>
                                <?php foreach ($products as $prod): ?>
                                    <?php $quickFormId = 'quickUpdateForm' . (int) $prod['id']; ?>
                                    <tr>
                                        <td><strong><?= $prod['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($prod['name']) ?></td>
                                        <td>
                                            <?php if ($prod['category']): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($prod['category']) ?></span>
                                            <?php else: ?>
                                                <span style="color: #7f8c8d;">Tanpa Kategori</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm" style="min-width: 170px;">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" name="price" form="<?= $quickFormId ?>" class="form-control quick-edit-field" min="0" step="100" value="<?= htmlspecialchars((string) (float) $prod['price']) ?>" required>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="stock" form="<?= $quickFormId ?>" class="form-control quick-edit-field" min="0" step="1" value="<?= htmlspecialchars((string) (int) $prod['stock']) ?>" required>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="quick-actions">
                                                <form method="POST" id="<?= $quickFormId ?>">
                                                    <input type="hidden" name="action" value="quick_update">
                                                    <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                                    <button type="submit" class="btn-sm-custom btn-save-inline">
                                                        <i class="bi bi-check2-circle"></i> Simpan
                                                    </button>
                                                </form>
                                                <a href="?edit=<?= $prod['id'] ?>" class="btn-sm-custom btn-edit">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="#" class="btn-sm-custom btn-remove" onclick="if(confirm('Hapus <?= htmlspecialchars($prod['name']) ?>?')) { 
                            document.deleteForm.id.value='<?= $prod['id'] ?>'; document.deleteForm.submit(); 
                        } return false;">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                        <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                        Belum ada produk. Tambahkan produk baru untuk memulai.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" style="display:none;" name="deleteForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
