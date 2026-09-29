<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/functions.php';
$pos = new POSFunctions();
$period = $_GET['period'] ?? null;
$month = $_GET['month'] ?? date('Y-m');

$monthDate = DateTime::createFromFormat('!Y-m', $month);
if (!$monthDate || $monthDate->format('Y-m') !== $month) {
    $month = date('Y-m');
    $monthDate = DateTime::createFromFormat('!Y-m', $month);
}

if ($period === null && isset($_GET['month']) && $month !== date('Y-m')) {
    $period = 'monthly';
}

if (!in_array($period, ['weekly', 'monthly'], true)) {
    $period = 'weekly';
}

$selectedWeek = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 4],
]);
if (!$selectedWeek) {
    $selectedWeek = $month === date('Y-m') ? min(4, (int) ceil((int) date('j') / 7)) : 1;
}

if ($period === 'monthly') {
    $startDate = $monthDate->format('Y-m-01 00:00:00');
    $endDate = $monthDate->format('Y-m-t 23:59:59');
    $periodLabel = 'Bulanan ' . $monthDate->format('m/Y');
} else {
    $startDay = (($selectedWeek - 1) * 7) + 1;
    $endDay = $selectedWeek === 4 ? (int) $monthDate->format('t') : $startDay + 6;
    $startDate = $monthDate->format('Y-m-') . str_pad((string) $startDay, 2, '0', STR_PAD_LEFT) . ' 00:00:00';
    $endDate = $monthDate->format('Y-m-') . str_pad((string) $endDay, 2, '0', STR_PAD_LEFT) . ' 23:59:59';
    $periodLabel = 'Mingguan Minggu ' . $selectedWeek . ' - ' . $monthDate->format('m/Y') . ' (' . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)) . ')';
}
$reportRows = $pos->getSalesReport($startDate, $endDate);
$reportSales = array_sum(array_column($reportRows, 'total'));
$reportPurchases = $pos->getPurchaseTotal($startDate, $endDate);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis</title>
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

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
        }

        .stat-value.success {
            color: var(--success-color);
        }

        .stat-value.info {
            color: var(--info-color);
        }

        .stat-value.accent {
            color: var(--accent-color);
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

        .badge-danger-custom {
            background-color: var(--danger-color);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-freq {
            background: linear-gradient(90deg, var(--accent-color) 0%, #2980b9 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .chart-wrap {
            position: relative;
            min-height: 360px;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #eef6fb;
            color: var(--accent-color);
            font-weight: 800;
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.75rem;
            }

            .stat-card {
                margin-bottom: 15px;
            }

            .chart-wrap {
                min-height: 300px;
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
            <h1><i class="bi bi-graph-up"></i> Laporan & Analisis</h1>
            <p>Data penjualan, stok, dan analisis pasar Anda</p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container-lg" style="padding-bottom: 40px;">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-printer"></i> Cetak Laporan Mingguan & Bulanan</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Jenis Laporan</label>
                        <select name="period" class="form-select">
                            <option value="weekly" <?= $period === 'weekly' ? 'selected' : '' ?>>Mingguan</option>
                            <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Bulan</label>
                        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Minggu</label>
                        <select name="week" class="form-select">
                            <?php for ($weekOption = 1; $weekOption <= 4; $weekOption++): ?>
                                <option value="<?= $weekOption ?>" <?= $selectedWeek === $weekOption ? 'selected' : '' ?>>Minggu <?= $weekOption ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Tampilkan</button>
                        <a target="_blank" class="btn btn-success flex-fill" href="print_report.php?period=<?= urlencode($period) ?>&month=<?= urlencode($month) ?>&week=<?= urlencode((string) $selectedWeek) ?>">
                            <i class="bi bi-printer"></i> Cetak
                        </a>
                    </div>
                </form>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-label"><?= htmlspecialchars($periodLabel) ?></div>
                            <p class="stat-value info"><?= count($reportRows) ?> Transaksi</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-label">Penjualan Periode Ini</div>
                            <p class="stat-value success">Rp <?= number_format($reportSales, 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-label">Pembelian Supplier</div>
                            <p class="stat-value" style="color: var(--warning-color);">Rp <?= number_format($reportPurchases, 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-label">Total Transaksi</div>
                    <p class="stat-value info"><?= $pos->getTransCount() ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="stat-label">Total Penjualan</div>
                    <p class="stat-value success">Rp <?= number_format($pos->getTotalSales(), 0, ',', '.') ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon accent">
                        <i class="bi bi-archive"></i>
                    </div>
                    <div class="stat-label">Total Produk</div>
                    <p class="stat-value accent"><?= count($pos->getProducts()) ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--danger-color);">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-label">Stok Kosong</div>
                    <p class="stat-value" style="color: var(--danger-color);"><?= count($pos->getEmptyStockProducts()) ?></p>
                </div>
            </div>
        </div>

        <!-- Top Sold Products Chart -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-bar-chart-line"></i>
                    Top 10 Barang Terbanyak Dibeli
                </h5>
            </div>
            <div class="card-body">
                <?php
                $topProducts = $pos->getTopSoldProducts(10, $startDate, $endDate);
                if (empty($topProducts)): ?>
                    <div class="no-data">
                        <i class="bi bi-bar-chart"></i>
                        <p>Belum ada data transaksi. Lakukan penjualan terlebih dahulu untuk melihat grafik barang terlaris.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-8">
                            <div class="chart-wrap">
                                <canvas id="topProductsChart"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 56px;">No</th>
                                            <th>Barang</th>
                                            <th style="text-align: right;">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProducts as $idx => $product): ?>
                                            <tr>
                                                <td><span class="rank-badge"><?= $idx + 1 ?></span></td>
                                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                <td style="text-align: right;"><strong><?= number_format($product['total_qty'], 0, ',', '.') ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Empty Stock Section -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-exclamation-triangle"></i>
                    Detail Stok Kosong/Habis
                </h5>
            </div>
            <div class="card-body">
                <?php
                $empty = $pos->getEmptyStockProducts();
                if (empty($empty)): ?>
                    <div class="no-data">
                        <i class="bi bi-check-circle"></i>
                        <p>Semua produk tersedia dengan stok yang cukup! Tidak ada stok yang kosong.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash"></i> ID</th>
                                    <th><i class="bi bi-tag"></i> Nama Produk</th>
                                    <th><i class="bi bi-currency-dollar"></i> Harga</th>
                                    <th style="text-align: center;"><i class="bi bi-archive"></i> Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empty as $prod): ?>
                                    <tr>
                                        <td><strong><?= $prod['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($prod['name']) ?></td>
                                        <td><strong>Rp <?= number_format($prod['price'], 0, ',', '.') ?></strong></td>
                                        <td style="text-align: center;">
                                            <span class="badge-danger-custom">
                                                <i class="bi bi-exclamation-circle"></i> <?= $prod['stock'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Market Basket Analysis Section -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-link-45deg"></i>
                    Analisis Market Basket - Pasangan Barang Terlaris
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted" style="margin-bottom: 20px;">
                    <i class="bi bi-info-circle"></i>
                    Pasangan barang paling sering dibeli bersama dalam 1 transaksi (berdasarkan frekuensi tertinggi).
                </p>
                
                <?php
                $pairs = $pos->getTopPairs(20); // Show top 20
                if (empty($pairs)): ?>
                    <div class="no-data">
                        <i class="bi bi-inbox"></i>
                        <p>Belum ada data transaksi. Lakukan penjualan terlebih dahulu untuk melihat analisis pasangan barang.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 10%;"><i class="bi bi-hash"></i> No</th>
                                    <th><i class="bi bi-link-45deg"></i> Pasangan Barang</th>
                                    <th style="text-align: center; width: 20%;"><i class="bi bi-graph-up"></i> Frekuensi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pairs as $idx => $pair): ?>
                                    <tr>
                                        <td><strong><?= $idx + 1 ?></strong></td>
                                        <td><?= htmlspecialchars($pair['pair']) ?></td>
                                        <td style="text-align: center;">
                                            <span class="badge-freq"><?= $pair['freq'] ?>x</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($_SESSION['role'] == 'admin'): ?>
        <!-- Recent Transactions Section -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-clock-history"></i>
                    Transaksi Terbaru
                </h5>
            </div>
            <div class="card-body">
                <?php
                $recentTrans = $pos->getRecentTransactions(10);
                if (empty($recentTrans)): ?>
                    <div class="no-data">
                        <i class="bi bi-receipt"></i>
                        <p>Belum ada transaksi</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash"></i> ID</th>
                                    <th><i class="bi bi-calendar"></i> Tanggal</th>
                                    <th><i class="bi bi-list"></i> Items</th>
                                    <th style="text-align: right;"><i class="bi bi-cash"></i> Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTrans as $trans): ?>
                                    <tr>
                                        <td><strong>#<?= $trans['id'] ?></strong></td>
                                        <td><?= date('d/m/Y H:i', strtotime($trans['trans_date'])) ?></td>
                                        <td><?= htmlspecialchars($trans['items'] ?? '') ?></td>
                                        <td style="text-align: right;"><strong>Rp <?= number_format($trans['total'], 0, ',', '.') ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php if (!empty($topProducts)): ?>
    <script>
        const topProductsData = <?= json_encode(array_map(function ($product) {
            return [
                'name' => $product['product_name'],
                'qty' => (int) $product['total_qty'],
                'sales' => (float) $product['total_sales'],
            ];
        }, $topProducts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        const topProductsCanvas = document.getElementById('topProductsChart');
        if (topProductsCanvas) {
            new Chart(topProductsCanvas, {
                type: 'bar',
                data: {
                    labels: topProductsData.map(item => item.name),
                    datasets: [{
                        label: 'Jumlah Dibeli',
                        data: topProductsData.map(item => item.qty),
                        backgroundColor: [
                            '#3498db',
                            '#1abc9c',
                            '#27ae60',
                            '#f39c12',
                            '#e74c3c',
                            '#9b59b6',
                            '#34495e',
                            '#16a085',
                            '#d35400',
                            '#7f8c8d'
                        ],
                        borderRadius: 6,
                        maxBarThickness: 42
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const item = topProductsData[context.dataIndex];
                                    return [
                                        `Jumlah dibeli: ${new Intl.NumberFormat('id-ID').format(item.qty)}`,
                                        `Total penjualan: Rp ${new Intl.NumberFormat('id-ID').format(item.sales)}`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    </script>
    <?php endif; ?>
</body>

</html>
