<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/functions.php';
$pos = new POSFunctions();
$products = $pos->getProducts();
$message = '';
$message_type = 'success';

if ($_POST && isset($_POST['action']) && $_POST['action'] == 'delete_transaction') {
    if ($pos->deleteTransaction((int) ($_POST['id'] ?? 0))) {
        $message = 'Transaksi dibatalkan dan stok barang sudah dikembalikan.';
        $message_type = 'success';
        $products = $pos->getProducts();
    } else {
        $message = 'Transaksi gagal dibatalkan.';
        $message_type = 'danger';
    }
}

if ($_POST && isset($_POST['action']) && $_POST['action'] == 'process_sale') {
    $details = [];
    foreach (($_POST['items'] ?? []) as $item) {
        if (!empty($item['prod_id']) && $item['qty'] > 0) {
            $prod = $pos->getProduct($item['prod_id']);
            if ($prod && $prod['stock'] >= $item['qty']) {
                $details[] = [
                    'id' => $item['prod_id'],
                    'name' => $prod['name'],
                    'price' => $prod['price'],
                    'qty' => $item['qty']
                ];
            }
        }
    }
    if (!empty($details)) {
        $total = array_sum(array_map(fn($d) => $d['price'] * $d['qty'], $details));
        $paid_amount = (float) preg_replace('/[^\d]/', '', $_POST['paid_amount'] ?? '0');
        if ($paid_amount < $total) {
            $message = 'Error: Nominal uang pelanggan kurang dari total belanja.';
            $message_type = 'danger';
        } else {
            $change_amount = $paid_amount - $total;
            $payment_method = $_POST['payment_method'] ?? 'Tunai';
            $trans_id = $pos->createTransaction($total, $details, $payment_method, $paid_amount);
            $message = "Transaksi #{$trans_id} berhasil! Total: Rp " . number_format($total, 0, ',', '.') . " | Metode: " . htmlspecialchars($payment_method) . " | Bayar: Rp " . number_format($paid_amount, 0, ',', '.') . " | Kembalian: Rp " . number_format($change_amount, 0, ',', '.') . " <a class=\"alert-link\" target=\"_blank\" href=\"print_invoice.php?id={$trans_id}&autoprint=1\">Cetak struk</a>";
            $message_type = 'success';
        }
    } else {
        $message = 'Error: Tidak ada item valid atau stok kurang.';
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Kasir</title>
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
            border-radius: 8px;
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
            border-radius: 8px 8px 0 0 !important;
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
            padding: 10px 20px;
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

        .btn-danger-custom {
            background: var(--danger-color);
            color: white;
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .btn-danger-custom:hover {
            background: #c0392b;
            color: white;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0;
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

        .cart-row {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) 130px 96px 140px 42px;
            gap: 10px;
            align-items: center;
            margin-bottom: 12px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ecf0f1;
            transition: all 0.3s ease;
        }

        .cart-row:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-search-wrap {
            position: relative;
        }

        .product-suggestions {
            position: absolute;
            z-index: 20;
            left: 0;
            right: 0;
            top: calc(100% + 4px);
            max-height: 260px;
            overflow-y: auto;
            background: white;
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            box-shadow: 0 12px 28px rgba(44, 62, 80, 0.16);
            display: none;
        }

        .product-suggestion {
            width: 100%;
            border: 0;
            background: white;
            padding: 10px 12px;
            text-align: left;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .product-suggestion:hover,
        .product-suggestion:focus {
            background: #f1f8fc;
            outline: none;
        }

        .product-suggestion .name {
            font-weight: 700;
            color: var(--primary-color);
        }

        .product-suggestion .meta {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .product-suggestion .stock {
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .product-suggestion.disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .price-display {
            font-weight: 600;
            color: var(--accent-color);
            min-width: 120px;
            text-align: right;
        }

        .subtotal-display {
            font-weight: 700;
            color: var(--success-color);
            min-width: 120px;
            text-align: right;
        }

        .total-box {
            background: linear-gradient(135deg, var(--success-color) 0%, #229954 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
        }

        .payment-panel {
            display: grid;
            grid-template-columns: minmax(180px, 0.8fr) minmax(220px, 1fr) minmax(220px, 1fr);
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 18px;
            margin-top: 18px;
        }

        .payment-panel label {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .change-box {
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-radius: 8px;
            padding: 10px 15px;
            background: white;
            border: 2px solid #ecf0f1;
        }

        .change-box strong {
            color: var(--success-color);
            font-size: 1.25rem;
        }

        .change-box.short strong {
            color: var(--danger-color);
        }

        .total-box .label {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .total-box .amount {
            font-size: 2.5rem;
            font-weight: 800;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.75rem;
            }

            .cart-row {
                grid-template-columns: 1fr;
            }

            .price-display,
            .subtotal-display {
                width: 100%;
                margin-top: 5px;
                text-align: left;
            }

            .payment-panel {
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
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-boxes"></i> Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="transactions.php">
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
            <h1><i class="bi bi-cash-register"></i> Sistem Kasir</h1>
            <p>Proses penjualan dengan cepat dan mudah</p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container-lg" style="padding-bottom: 40px;">
        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message_type == 'success' ? $message : htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Transaction Form Card -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-cart-plus"></i>
                    Transaksi Baru
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="saleForm">
                    <input type="hidden" name="action" value="process_sale">

                    <!-- Cart Items -->
                    <div id="cartItems">
                        <div class="cart-row" id="row0">
                            <div class="product-search-wrap">
                                <input type="hidden" name="items[0][prod_id]" id="prod0" required>
                                <input type="text" class="form-control product-search" id="search0" placeholder="Cari nama barang atau kategori..." autocomplete="off" oninput="searchProducts(0)" onfocus="searchProducts(0)" required>
                                <div class="product-suggestions" id="suggestions0"></div>
                            </div>
                            <div class="price-display" id="price0">Rp 0</div>
                            <input type="number" name="items[0][qty]" class="form-control" min="1" value="1" onchange="updateSubtotal(0)" onkeyup="updateSubtotal(0)" required>
                            <div class="subtotal-display" id="sub0">Rp 0</div>
                            <button type="button" class="btn btn-danger-custom" onclick="removeRow(this)" style="display:none;" id="removeBtn0">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Add Item Button -->
                    <button type="button" class="btn btn-primary-custom btn-custom" onclick="addRow()" style="margin-top: 15px;">
                        <i class="bi bi-plus-circle"></i> Tambah Item
                    </button>

                    <!-- Total Box -->
                    <div class="total-box">
                        <div class="label">Total Penjualan</div>
                        <div class="amount" id="total">Rp 0</div>
                    </div>

                    <div class="payment-panel">
                        <div>
                            <label for="paymentMethod"><i class="bi bi-credit-card"></i> Metode Pembayaran</label>
                            <select name="payment_method" id="paymentMethod" class="form-select" required>
                                <option value="Tunai">Tunai</option>
                                <option value="Transfer">Transfer</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Debit">Debit</option>
                            </select>
                        </div>
                        <div>
                            <label for="paidAmount"><i class="bi bi-wallet2"></i> Uang Dibayar Pelanggan</label>
                            <input type="text" name="paid_amount" id="paidAmount" class="form-control" placeholder="Contoh: 50000" inputmode="numeric" oninput="handlePaidInput()" required>
                        </div>
                        <div>
                            <label><i class="bi bi-arrow-return-left"></i> Kembalian</label>
                            <div class="change-box" id="changeBox">
                                <span id="changeLabel">Masukkan nominal bayar</span>
                                <strong id="changeAmount">Rp 0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-success-custom btn-custom w-100" style="margin-top: 20px; padding: 12px; font-size: 1.1rem;">
                        <i class="bi bi-check-circle"></i> Proses Penjualan
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-clock-history"></i>
                    Transaksi Terbaru
                    <span class="badge bg-info" style="margin-left: auto;">10 Transaksi Terbaru</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> ID</th>
                                <th><i class="bi bi-calendar3"></i> Tanggal & Waktu</th>
                                <th><i class="bi bi-bag"></i> Jumlah Item</th>
                                <th><i class="bi bi-credit-card"></i> Metode</th>
                                <th style="text-align: right;"><i class="bi bi-currency-dollar"></i> Total</th>
                                <th style="text-align: center;"><i class="bi bi-printer"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $transactions = $pos->getRecentTransactions(); ?>
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $trans): ?>
                                    <tr>
                                        <td><strong>#<?= $trans['id'] ?></strong></td>
                                        <td><?= $trans['trans_date'] ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= number_format($trans['item_count'], 0, ',', '.') ?> Item</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($trans['payment_method'] ?? 'Tunai') ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <strong style="color: var(--success-color);">Rp <?= number_format($trans['total'], 0, ',', '.') ?></strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                <a href="print_invoice.php?id=<?= $trans['id'] ?>" target="_blank" class="btn btn-sm" style="background-color: var(--accent-color); color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                                    <i class="bi bi-printer"></i> Cetak
                                                </a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Batalkan transaksi #<?= $trans['id'] ?>? Stok barang akan dikembalikan.');">
                                                    <input type="hidden" name="action" value="delete_transaction">
                                                    <input type="hidden" name="id" value="<?= $trans['id'] ?>">
                                                    <button type="submit" class="btn btn-sm" style="background-color: var(--danger-color); color: white; padding: 6px 12px; border-radius: 5px; display: inline-flex; align-items: center; gap: 5px; border: 0;">
                                                        <i class="bi bi-x-circle"></i> Batal
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-data">
                                        <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let rowIndex = 1;
        const products = <?= json_encode(array_map(function ($p) {
            return [
                'id' => (int) $p['id'],
                'name' => $p['name'],
                'category' => $p['category'] ?? 'Umum',
                'price' => (float) $p['price'],
                'stock' => (int) $p['stock'],
            ];
        }, $products), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const currency = new Intl.NumberFormat('id-ID');

        function rupiah(amount) {
            return 'Rp ' + currency.format(Math.max(0, Number(amount) || 0));
        }

        function normalizeText(value) {
            return String(value || '').toLowerCase();
        }

        function getSelectedProduct(idx) {
            const prodInput = document.getElementById(`prod${idx}`);
            if (!prodInput || !prodInput.value) {
                return null;
            }
            return products.find(product => String(product.id) === String(prodInput.value)) || null;
        }

        function addRow() {
            const cartItems = document.getElementById('cartItems');
            const newRow = document.createElement('div');
            newRow.className = 'cart-row';
            newRow.id = `row${rowIndex}`;

            newRow.innerHTML = `
                <div class="product-search-wrap">
                    <input type="hidden" name="items[${rowIndex}][prod_id]" id="prod${rowIndex}" required>
                    <input type="text" class="form-control product-search" id="search${rowIndex}" placeholder="Cari nama barang atau kategori..." autocomplete="off" oninput="searchProducts(${rowIndex})" onfocus="searchProducts(${rowIndex})" required>
                    <div class="product-suggestions" id="suggestions${rowIndex}"></div>
                </div>
                <div class="price-display" id="price${rowIndex}">Rp 0</div>
                <input type="number" name="items[${rowIndex}][qty]" class="form-control" min="1" value="1" onchange="updateSubtotal(${rowIndex})" onkeyup="updateSubtotal(${rowIndex})" required>
                <div class="subtotal-display" id="sub${rowIndex}">Rp 0</div>
                <button type="button" class="btn btn-danger-custom" onclick="removeRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;

            cartItems.appendChild(newRow);
            rowIndex++;
            updateRemoveButtons();
        }

        function searchProducts(idx) {
            const searchInput = document.getElementById(`search${idx}`);
            const prodInput = document.getElementById(`prod${idx}`);
            const suggestions = document.getElementById(`suggestions${idx}`);
            const term = normalizeText(searchInput.value);

            if (searchInput.value !== (searchInput.dataset.selectedLabel || '')) {
                prodInput.value = '';
                updatePrice(idx);
            }

            const matches = products
                .filter(product => {
                    const haystack = normalizeText(`${product.name} ${product.category}`);
                    return !term || haystack.includes(term);
                })
                .slice(0, 12);

            if (!matches.length) {
                suggestions.innerHTML = '<div class="product-suggestion disabled">Barang tidak ditemukan</div>';
                suggestions.style.display = 'block';
                return;
            }

            suggestions.innerHTML = matches.map(product => `
                <button type="button" class="product-suggestion ${product.stock <= 0 ? 'disabled' : ''}" ${product.stock <= 0 ? 'disabled' : ''} onclick="selectProduct(${idx}, ${product.id})">
                    <span>
                        <span class="name">${escapeHtml(product.name)}</span>
                        <span class="meta d-block">${escapeHtml(product.category)} - ${rupiah(product.price)}</span>
                    </span>
                    <span class="stock">Stok: ${product.stock}</span>
                </button>
            `).join('');
            suggestions.style.display = 'block';
        }

        function selectProduct(idx, productId) {
            const product = products.find(item => item.id === productId);
            if (!product) {
                return;
            }

            document.getElementById(`prod${idx}`).value = product.id;
            const searchInput = document.getElementById(`search${idx}`);
            searchInput.value = `${product.name} (${product.category})`;
            searchInput.dataset.selectedLabel = searchInput.value;
            document.getElementById(`suggestions${idx}`).style.display = 'none';
            updatePrice(idx);
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        }

        function removeRow(btn) {
            btn.closest('.cart-row').remove();
            updateRemoveButtons();
            calcTotal();
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.cart-row');
            rows.forEach(row => {
                const removeBtn = row.querySelector('button[type="button"]');
                if (removeBtn) {
                    removeBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
                }
            });
        }

        function updatePrice(idx) {
            const priceCell = document.getElementById(`price${idx}`);
            const product = getSelectedProduct(idx);
            if (product) {
                priceCell.textContent = rupiah(product.price);
                updateSubtotal(idx);
            } else {
                priceCell.textContent = 'Rp 0';
                const subCell = document.getElementById(`sub${idx}`);
                if (subCell) {
                    subCell.textContent = 'Rp 0';
                }
            }
            calcTotal();
        }

        function updateSubtotal(idx) {
            const qtyInput = document.querySelector(`input[name="items[${idx}][qty]"]`);
            const subCell = document.getElementById(`sub${idx}`);
            const product = getSelectedProduct(idx);
            
            if (product && qtyInput.value > 0) {
                const qty = parseInt(qtyInput.value);
                const subtotal = product.price * qty;
                subCell.textContent = rupiah(subtotal);
            } else {
                subCell.textContent = 'Rp 0';
            }
            calcTotal();
        }

        function calcTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.cart-row');
            
            rows.forEach((row, idx) => {
                const prodInput = row.querySelector('input[type="hidden"]');
                const qtyInput = row.querySelector('input[type="number"]');
                const product = prodInput ? products.find(item => String(item.id) === String(prodInput.value)) : null;
                
                if (product && qtyInput && qtyInput.value > 0) {
                    const qty = parseInt(qtyInput.value);
                    total += product.price * qty;
                }
            });

            document.getElementById('total').textContent = rupiah(total);
            updateChange(total);
            return total;
        }

        function parseMoney(value) {
            return Number(String(value || '').replace(/[^\d]/g, '')) || 0;
        }

        function handlePaidInput() {
            const input = document.getElementById('paidAmount');
            const value = parseMoney(input.value);
            input.value = value ? currency.format(value) : '';
            updateChange(calcTotal());
        }

        function updateChange(total = null) {
            const currentTotal = total === null ? calcTotal() : total;
            const paid = parseMoney(document.getElementById('paidAmount').value);
            const change = paid - currentTotal;
            const changeBox = document.getElementById('changeBox');
            const changeLabel = document.getElementById('changeLabel');
            const changeAmount = document.getElementById('changeAmount');

            if (!paid) {
                changeBox.classList.remove('short');
                changeLabel.textContent = 'Masukkan nominal bayar';
                changeAmount.textContent = 'Rp 0';
                return;
            }

            if (change < 0) {
                changeBox.classList.add('short');
                changeLabel.textContent = 'Kurang bayar';
                changeAmount.textContent = rupiah(Math.abs(change));
                return;
            }

            changeBox.classList.remove('short');
            changeLabel.textContent = 'Kembalian';
            changeAmount.textContent = rupiah(change);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateRemoveButtons();
            calcTotal();

            document.addEventListener('click', function(event) {
                if (!event.target.closest('.product-search-wrap')) {
                    document.querySelectorAll('.product-suggestions').forEach(list => {
                        list.style.display = 'none';
                    });
                }
            });

            document.getElementById('saleForm').addEventListener('submit', function(event) {
                const total = calcTotal();
                const paidInput = document.getElementById('paidAmount');
                const emptyProduct = Array.from(document.querySelectorAll('.cart-row')).find(row => {
                    const searchInput = row.querySelector('.product-search');
                    const prodInput = row.querySelector('input[type="hidden"]');
                    return searchInput && searchInput.value.trim() && prodInput && !prodInput.value;
                });

                if (emptyProduct) {
                    event.preventDefault();
                    const searchInput = emptyProduct.querySelector('.product-search');
                    searchInput.focus();
                    searchInput.setCustomValidity('Pilih barang dari daftar hasil pencarian.');
                    searchInput.reportValidity();
                    searchInput.addEventListener('input', () => searchInput.setCustomValidity(''), { once: true });
                    return;
                }

                if (parseMoney(paidInput.value) < total) {
                    event.preventDefault();
                    paidInput.focus();
                    updateChange(total);
                }
            });
        });
    </script>
</body>

</html>
