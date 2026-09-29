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

if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_purchase') {
        $status = $_POST['status'] ?? 'dipesan';
        if ($pos->createPurchase($_POST['supplier_name'], $_POST['supplier_contact'] ?? '', (int) $_POST['product_id'], (int) $_POST['qty'], (float) $_POST['unit_cost'], $status)) {
            $message = $status === 'diterima' ? 'Pembelian disimpan dan stok produk bertambah.' : 'Pembelian dari supplier disimpan.';
        } else {
            $message = 'Pembelian gagal disimpan.';
            $message_type = 'danger';
        }
    }

    if ($_POST['action'] === 'edit_purchase') {
        $status = $_POST['status'] ?? 'dipesan';
        if ($pos->updatePurchase((int) $_POST['id'], $_POST['supplier_name'], $_POST['supplier_contact'] ?? '', (int) $_POST['product_id'], (int) $_POST['qty'], (float) $_POST['unit_cost'], $status)) {
            $message = 'Riwayat pembelian diupdate.';
        } else {
            $message = 'Riwayat pembelian gagal diupdate.';
            $message_type = 'danger';
        }
    }

    if ($_POST['action'] === 'receive_purchase') {
        if ($pos->receivePurchase((int) $_POST['id'])) {
            $message = 'Barang diterima dan stok produk bertambah.';
        } else {
            $message = 'Barang gagal diterima atau sudah diterima sebelumnya.';
            $message_type = 'danger';
        }
    }

    if ($_POST['action'] === 'delete_purchase') {
        if ($pos->deletePurchase((int) $_POST['id'])) {
            $message = 'Riwayat pembelian dihapus.';
        } else {
            $message = 'Riwayat pembelian gagal dihapus.';
            $message_type = 'danger';
        }
    }
}

$products = $pos->getProducts();
$edit_purchase = null;
if (isset($_GET['edit'])) {
    $edit_purchase = $pos->getPurchase((int) $_GET['edit']);
}
$purchases = $pos->getPurchases(100);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier & Pembelian Barang</title>
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
        * { box-sizing: border-box; }
        body { background: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: linear-gradient(90deg, var(--primary-color) 0%, #34495e 100%); box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        .navbar-custom .navbar-brand { font-weight: 700; font-size: 1.5rem; letter-spacing: 1px; }
        .navbar-custom .nav-link { margin: 0 10px; transition: all .3s ease; border-radius: 5px; }
        .navbar-custom .nav-link:hover { background-color: rgba(255,255,255,.1); transform: translateY(-2px); }
        .page-header { background: linear-gradient(135deg, var(--info-color) 0%, #16a085 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
        .page-header h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 10px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.08); margin-bottom: 30px; }
        .card-header { background: linear-gradient(90deg, var(--primary-color) 0%, #34495e 100%); color: white; border-radius: 8px 8px 0 0 !important; padding: 20px; border: none; }
        .card-header h5 { margin: 0; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .form-control, .form-select { border-radius: 8px; border: 2px solid #ecf0f1; padding: 10px 15px; }
        .product-search-wrap { position: relative; }
        .product-suggestions { position: absolute; z-index: 20; left: 0; right: 0; top: calc(100% + 4px); max-height: 260px; overflow-y: auto; background: white; border: 1px solid #dfe6e9; border-radius: 8px; box-shadow: 0 12px 28px rgba(44, 62, 80, .16); display: none; }
        .product-suggestion { width: 100%; border: 0; background: white; padding: 10px 12px; text-align: left; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .product-suggestion:hover, .product-suggestion:focus { background: #f1f8fc; outline: none; }
        .product-suggestion .name { font-weight: 700; color: var(--primary-color); }
        .product-suggestion .meta { color: #7f8c8d; font-size: .85rem; }
        .table thead { background-color: #f8f9fa; }
        .table thead th { border: none; color: var(--primary-color); font-weight: 700; padding: 15px; text-transform: uppercase; font-size: .85rem; }
        .table tbody td { vertical-align: middle; padding: 15px; border-bottom: 1px solid #ecf0f1; }
        .btn-custom { border-radius: 8px; padding: 10px 20px; font-weight: 600; border: none; }
        .btn-success-custom { background: linear-gradient(90deg, var(--success-color) 0%, #229954 100%); color: white; }
        .btn-primary-custom { background: linear-gradient(90deg, var(--accent-color) 0%, #2980b9 100%); color: white; }
        @media (max-width: 768px) { .page-header h1 { font-size: 1.75rem; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php"><i class="bi bi-shop"></i> Mutiara Tani</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-boxes"></i> Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="transactions.php"><i class="bi bi-receipt"></i> Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Laporan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="expenses.php"><i class="bi bi-truck"></i> Supplier</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> Kelola User</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container-lg">
            <h1><i class="bi bi-truck"></i> Supplier</h1>
            <p>Input pembelian dari supplier, terima barang, dan pantau riwayat pembelian</p>
        </div>
    </div>

    <div class="container-lg" style="padding-bottom: 40px;">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-<?= $edit_purchase ? 'pencil-square' : 'plus-circle' ?>"></i> <?= $edit_purchase ? 'Edit Riwayat Pembelian' : 'Input Pembelian dari Supplier' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="<?= $edit_purchase ? 'edit_purchase' : 'add_purchase' ?>">
                    <?php if ($edit_purchase): ?>
                        <input type="hidden" name="id" value="<?= (int) $edit_purchase['id'] ?>">
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Supplier</label>
                        <input type="text" name="supplier_name" class="form-control" value="<?= htmlspecialchars($edit_purchase['supplier_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Kontak Supplier</label>
                        <input type="text" name="supplier_contact" class="form-control" value="<?= htmlspecialchars($edit_purchase['supplier_contact'] ?? '') ?>" placeholder="No. HP / email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Produk</label>
                        <div class="product-search-wrap">
                            <input type="hidden" name="product_id" id="productId" value="<?= htmlspecialchars($edit_purchase['product_id'] ?? '') ?>" required>
                            <input type="text" id="productSearch" class="form-control" placeholder="Ketik nama produk atau kategori..." autocomplete="off" value="<?= htmlspecialchars($edit_purchase['product_name'] ?? '') ?>" oninput="searchProducts()" onfocus="searchProducts()" required>
                            <div class="product-suggestions" id="productSuggestions"></div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Qty</label>
                        <input type="number" name="qty" class="form-control" min="1" value="<?= htmlspecialchars($edit_purchase['qty'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Harga Beli</label>
                        <input type="number" name="unit_cost" class="form-control" min="0" step="100" value="<?= htmlspecialchars($edit_purchase['unit_cost'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="dipesan" <?= ($edit_purchase['status'] ?? '') === 'dipesan' ? 'selected' : '' ?>>Dipesan</option>
                            <option value="diterima" <?= ($edit_purchase['status'] ?? '') === 'diterima' ? 'selected' : '' ?>>Diterima</option>
                        </select>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <button type="submit" class="btn btn-success-custom btn-custom w-100">
                            <i class="bi bi-save"></i> <?= $edit_purchase ? 'Update Pembelian' : 'Simpan Pembelian' ?>
                        </button>
                    </div>
                    <?php if ($edit_purchase): ?>
                        <div class="col-12">
                            <a href="expenses.php" class="btn btn-secondary btn-custom w-100"><i class="bi bi-x-circle"></i> Batal Edit</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-clock-history"></i> Riwayat Pembelian</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Supplier</th>
                                <th>Kontak</th>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Beli</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">Belum ada pembelian supplier.</td></tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($purchase['purchase_date'])) ?></td>
                                        <td><?= htmlspecialchars($purchase['supplier_name']) ?></td>
                                        <td><?= htmlspecialchars($purchase['supplier_contact'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($purchase['product_name']) ?></td>
                                        <td class="text-end"><?= number_format($purchase['qty'], 0, ',', '.') ?></td>
                                        <td class="text-end">Rp <?= number_format($purchase['unit_cost'], 0, ',', '.') ?></td>
                                        <td class="text-end">Rp <?= number_format($purchase['total_cost'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge <?= $purchase['status'] === 'diterima' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= htmlspecialchars(ucfirst($purchase['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                <a href="?edit=<?= (int) $purchase['id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <?php if ($purchase['status'] === 'dipesan'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="receive_purchase">
                                                    <input type="hidden" name="id" value="<?= $purchase['id'] ?>">
                                                    <button type="submit" class="btn btn-primary-custom btn-custom btn-sm">
                                                        <i class="bi bi-box-arrow-in-down"></i> Terima Barang
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus riwayat pembelian ini? Stok akan disesuaikan jika barang sudah diterima.');">
                                                    <input type="hidden" name="action" value="delete_purchase">
                                                    <input type="hidden" name="id" value="<?= $purchase['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const products = <?= json_encode(array_map(function ($product) {
            return [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'category' => $product['category'] ?? 'Umum',
                'stock' => (int) $product['stock'],
            ];
        }, $products), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const productSearch = document.getElementById('productSearch');
        const productId = document.getElementById('productId');
        const productSuggestions = document.getElementById('productSuggestions');
        if (productSearch.value && productId.value) {
            productSearch.dataset.selectedLabel = productSearch.value;
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        }

        function normalizeText(value) {
            return String(value || '').toLowerCase();
        }

        function searchProducts() {
            const term = normalizeText(productSearch.value);
            if (productSearch.value !== (productSearch.dataset.selectedLabel || '')) {
                productId.value = '';
            }

            const matches = products.filter(product => {
                return normalizeText(`${product.name} ${product.category}`).includes(term);
            }).slice(0, 12);

            if (!matches.length) {
                productSuggestions.innerHTML = '<div class="product-suggestion">Produk tidak ditemukan</div>';
                productSuggestions.style.display = 'block';
                return;
            }

            productSuggestions.innerHTML = matches.map(product => `
                <button type="button" class="product-suggestion" onclick="selectProduct(${product.id})">
                    <span>
                        <span class="name">${escapeHtml(product.name)}</span>
                        <span class="meta d-block">${escapeHtml(product.category)}</span>
                    </span>
                    <span class="meta">Stok: ${product.stock}</span>
                </button>
            `).join('');
            productSuggestions.style.display = 'block';
        }

        function selectProduct(id) {
            const product = products.find(item => item.id === id);
            if (!product) {
                return;
            }

            productId.value = product.id;
            productSearch.value = `${product.name} (${product.category})`;
            productSearch.dataset.selectedLabel = productSearch.value;
            productSuggestions.style.display = 'none';
        }

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.product-search-wrap')) {
                productSuggestions.style.display = 'none';
            }
        });

        document.querySelector('form').addEventListener('submit', function(event) {
            if (!productId.value) {
                event.preventDefault();
                productSearch.focus();
                productSearch.setCustomValidity('Pilih produk dari daftar pencarian.');
                productSearch.reportValidity();
                productSearch.addEventListener('input', () => productSearch.setCustomValidity(''), { once: true });
            }
        });
    </script>
</body>
</html>
