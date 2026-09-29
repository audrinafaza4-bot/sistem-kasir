<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/functions.php';
$pos = new POSFunctions();
$dailySummary = $pos->getDailySummary();
$todayTopProducts = $pos->getTopSoldProducts(5, date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
$lowStocks = $pos->getLowStockProducts(5);
$salesChart = $pos->getSalesChartData(7);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audri POS Dashboard</title>
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
            background: linear-gradient(135deg, var(--info-color) 0%, #16a085 100%);
            min-height: 100vh;
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

        .dashboard-header {
            color: white;
            padding: 40px 0 60px 0;
            text-align: center;
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .container-main {
            padding: 0 20px 40px 20px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
            border: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-count {
            background-color: var(--accent-color);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .table {
            margin: 0;
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

        .btn-action {
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .btn-success-custom {
            background: linear-gradient(90deg, var(--success-color) 0%, #229954 100%);
            color: white;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
            color: white;
        }

        .cta-section {
            text-align: center;
            margin-top: 40px;
        }

        .stat-box {
            background: white;
            width: 100%;
            min-height: 168px;
            padding: 22px 18px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 8px;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--accent-color);
            line-height: 1.15;
            margin: 4px 0;
            overflow-wrap: anywhere;
        }

        .stat-number.currency {
            font-size: 1.85rem;
        }

        .stat-number.product-name {
            font-size: 1.35rem;
            min-height: 3.1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .stat-label {
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .stat-note {
            min-height: 20px;
            display: block;
        }

        .chart-panel {
            position: relative;
            height: 360px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 2rem;
            }

            .navbar-custom .nav-link {
                margin: 5px 0;
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

    <!-- Header -->
    <div class="dashboard-header">
        <div class="container-lg">
            <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
            <p>Kelola toko Anda dengan mudah dan efisien</p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container-lg container-main">
        <div class="row mb-4 g-3 align-items-stretch">
            <div class="col-md-6 col-lg-3 d-flex">
                <div class="stat-box">
                    <div class="stat-label">Penjualan Hari Ini</div>
                    <div class="stat-number currency">Rp <?= number_format($dailySummary['total_sales'] ?? 0, 0, ',', '.') ?></div>
                    <small class="text-muted stat-note">Reset otomatis setiap pergantian hari</small>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 d-flex">
                <div class="stat-box">
                    <div class="stat-label">Total Transaksi Hari Ini</div>
                    <div class="stat-number"><?= number_format($dailySummary['total_transactions'] ?? 0, 0, ',', '.') ?></div>
                    <small class="text-muted stat-note"><?= date('d/m/Y') ?></small>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 d-flex">
                <div class="stat-box">
                    <div class="stat-label">Barang Terbanyak Dibeli</div>
                    <div class="stat-number product-name">
                        <?= htmlspecialchars($todayTopProducts[0]['product_name'] ?? '-') ?>
                    </div>
                    <small class="text-muted stat-note"><?= isset($todayTopProducts[0]) ? number_format($todayTopProducts[0]['total_qty'], 0, ',', '.') . ' item hari ini' : 'Belum ada penjualan' ?></small>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 d-flex">
                <div class="stat-box">
                    <div class="stat-label">Stok Menipis</div>
                    <div class="stat-number" style="color: var(--warning-color);"><?= count($lowStocks) ?></div>
                    <small class="text-muted stat-note">Stok 1 sampai 5</small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-graph-up-arrow"></i> Grafik Penjualan 7 Hari Terakhir</h5>
            </div>
            <div class="card-body">
                <div class="chart-panel">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-trophy"></i> Barang Terbanyak Dibeli Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todayTopProducts)): ?>
                            <div class="no-data">Belum ada penjualan hari ini.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th>Barang</th><th class="text-end">Qty</th><th class="text-end">Penjualan</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($todayTopProducts as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                <td class="text-end"><strong><?= number_format($product['total_qty'], 0, ',', '.') ?></strong></td>
                                                <td class="text-end">Rp <?= number_format($product['total_sales'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-exclamation-circle"></i> Stok Menipis</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lowStocks)): ?>
                            <div class="no-data">Tidak ada stok yang menipis.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th>Produk</th><th>Kategori</th><th class="text-end">Stok</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($lowStocks as $prod): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($prod['name']) ?></td>
                                                <td><?= htmlspecialchars($prod['category'] ?? 'Tanpa Kategori') ?></td>
                                                <td class="text-end"><span class="badge bg-warning text-dark"><?= $prod['stock'] ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty Stock Section -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-exclamation-triangle"></i>
                    Stok Kosong/Habis
                    <span class="badge-count"><?= count($pos->getEmptyStockProducts()) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php $emptyStocks = $pos->getEmptyStockProducts(); ?>
                <?php if (empty($emptyStocks)): ?>
                    <div class="no-data">
                        <i class="bi bi-check-circle" style="font-size: 3rem; color: var(--success-color);"></i>
                        <p style="margin-top: 15px;">Semua produk tersedia dengan stok yang cukup!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash"></i> ID</th>
                                    <th><i class="bi bi-tag"></i> Nama Produk</th>
                                    <th><i class="bi bi-currency-dollar"></i> Harga</th>
                                    <th><i class="bi bi-archive"></i> Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emptyStocks as $prod): ?>
                                    <tr>
                                        <td><strong><?= $prod['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($prod['name']) ?></td>
                                        <td>Rp <?= number_format($prod['price'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge bg-danger">
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

        <!-- CTA Section -->
        <div class="cta-section">
            <a href="transactions.php" class="btn-action btn-primary-custom">
                <i class="bi bi-plus-circle"></i> Buat Transaksi Baru
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const salesChartRows = <?= json_encode($salesChart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const salesChartCanvas = document.getElementById('salesChart');
        if (salesChartCanvas) {
            const ctx = salesChartCanvas.getContext('2d');
            const salesGradient = ctx.createLinearGradient(0, 0, 0, 360);
            salesGradient.addColorStop(0, 'rgba(52, 152, 219, 0.36)');
            salesGradient.addColorStop(0.55, 'rgba(26, 188, 156, 0.16)');
            salesGradient.addColorStop(1, 'rgba(52, 152, 219, 0.02)');
            const formatDate = value => new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short'
            }).format(new Date(value));
            const formatCurrency = value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);

            new Chart(salesChartCanvas, {
                type: 'bar',
                data: {
                    labels: salesChartRows.map(row => formatDate(row.sales_date)),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Transaksi',
                            data: salesChartRows.map(row => Number(row.transaction_count)),
                            yAxisID: 'transactions',
                            backgroundColor: 'rgba(243, 156, 18, 0.32)',
                            borderColor: 'rgba(243, 156, 18, 0.9)',
                            borderWidth: 1,
                            borderRadius: 8,
                            maxBarThickness: 28,
                            order: 2
                        },
                        {
                            type: 'line',
                            label: 'Penjualan',
                            data: salesChartRows.map(row => Number(row.total_sales)),
                            yAxisID: 'sales',
                            borderColor: '#3498db',
                            backgroundColor: salesGradient,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#1abc9c',
                            pointBorderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#52616b',
                                font: {
                                    weight: 700
                                }
                            }
                        },
                        sales: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(44, 62, 80, 0.08)'
                            },
                            ticks: {
                                color: '#52616b',
                                callback: value => formatCurrency(value)
                            }
                        },
                        transactions: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                precision: 0,
                                color: '#8a6d1d'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 18
                            }
                        },
                        tooltip: {
                            backgroundColor: '#2c3e50',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: context => {
                                    if (context.dataset.yAxisID === 'sales') {
                                        return 'Penjualan: ' + formatCurrency(context.parsed.y);
                                    }
                                    return 'Transaksi: ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>
