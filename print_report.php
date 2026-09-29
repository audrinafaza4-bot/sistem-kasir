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
    $title = 'Laporan Bulanan ' . $monthDate->format('m/Y');
} else {
    $startDay = (($selectedWeek - 1) * 7) + 1;
    $endDay = $selectedWeek === 4 ? (int) $monthDate->format('t') : $startDay + 6;
    $startDate = $monthDate->format('Y-m-') . str_pad((string) $startDay, 2, '0', STR_PAD_LEFT) . ' 00:00:00';
    $endDate = $monthDate->format('Y-m-') . str_pad((string) $endDay, 2, '0', STR_PAD_LEFT) . ' 23:59:59';
    $title = 'Laporan Mingguan Minggu ' . $selectedWeek . ' - ' . $monthDate->format('m/Y');
}

$rows = $pos->getSalesReport($startDate, $endDate);
$totalSales = array_sum(array_column($rows, 'total'));
$purchaseTotal = $pos->getPurchaseTotal($startDate, $endDate);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #2c3e50; margin: 24px; }
        .header { text-align: center; border-bottom: 2px solid #2c3e50; padding-bottom: 14px; margin-bottom: 20px; }
        .brand { font-size: 24px; font-weight: 800; }
        .meta { color: #666; margin-top: 6px; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .box { border: 1px solid #ddd; padding: 12px; border-radius: 6px; }
        .label { font-size: 12px; color: #666; text-transform: uppercase; }
        .value { font-size: 20px; font-weight: 800; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f4f6f8; }
        .right { text-align: right; }
        .print { text-align: center; margin-top: 24px; }
        button { border: 0; background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; font-weight: 700; cursor: pointer; }
        @media print {
            body { margin: 0; }
            .print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Mutiara Tani</div>
        <h2><?= htmlspecialchars($title) ?></h2>
        <div class="meta">Periode: <?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?></div>
    </div>

    <div class="summary">
        <div class="box">
            <div class="label">Total Transaksi</div>
            <div class="value"><?= count($rows) ?></div>
        </div>
        <div class="box">
            <div class="label">Total Penjualan</div>
            <div class="value">Rp <?= number_format($totalSales, 0, ',', '.') ?></div>
        </div>
        <div class="box">
            <div class="label">Pembelian Supplier</div>
            <div class="value">Rp <?= number_format($purchaseTotal, 0, ',', '.') ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Metode</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="4" style="text-align:center;">Tidak ada transaksi pada periode ini.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $idx => $row): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['trans_date'])) ?></td>
                        <td><?= htmlspecialchars($row['payment_method'] ?? 'Tunai') ?></td>
                        <td class="right">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="print">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
</body>
</html>
