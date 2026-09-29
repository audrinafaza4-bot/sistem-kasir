<?php
require_once __DIR__ . '/includes/functions.php';
$pos = new POSFunctions();

$trans_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$trans_id) {
    die('ID Transaksi tidak valid');
}

// Get transaction details
$trans = $pos->getTransactionDetails($trans_id);
if (!$trans) {
    die('Transaksi tidak ditemukan');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi #<?= $trans_id ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }

        .invoice-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }

        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .store-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .store-subtitle {
            font-size: 12px;
            color: #7f8c8d;
        }

        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0 5px 0;
        }

        .invoice-date {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .items-section {
            margin-bottom: 20px;
            border-top: 1px dashed #ddd;
            border-bottom: 1px dashed #ddd;
            padding: 15px 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .item-name {
            flex: 1;
            font-weight: bold;
        }

        .item-qty {
            width: 50px;
            text-align: center;
            margin: 0 5px;
        }

        .item-price {
            width: 80px;
            text-align: right;
            font-weight: bold;
        }

        .item-detail {
            font-size: 11px;
            color: #7f8c8d;
            margin-left: 10px;
        }

        .summary-section {
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .summary-row.total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            color: #27ae60;
        }

        .summary-label {
            flex: 1;
        }

        .summary-value {
            width: 120px;
            text-align: right;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            border-top: 1px dashed #ddd;
            padding-top: 15px;
            margin-top: 20px;
        }

        .thank-you {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 15px 0;
            color: #2c3e50;
        }

        .print-button {
            text-align: center;
            margin-top: 20px;
            padding: 20px 0;
            border-top: 1px solid #ddd;
        }

        .print-button button {
            background: linear-gradient(90deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .print-button button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                max-width: 100%;
                margin: 0;
                box-shadow: none;
                border: none;
                padding: 20px;
            }

            .print-button {
                display: none;
            }

            button {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .invoice-container {
                padding: 20px;
                margin: 0;
            }

            .item-detail {
                margin-top: 5px;
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="store-name">Mutiara Tani</div>
            <div class="store-subtitle">Toko Penjualan</div>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-number">NO. NOTA: #<?= str_pad($trans_id, 6, '0', STR_PAD_LEFT) ?></div>
        <div class="invoice-date">
            Tanggal: <?= date('d-m-Y H:i:s', strtotime($trans['trans_date'])) ?>
        </div>

        <!-- Items -->
        <div class="items-section">
            <?php foreach ($trans['items'] as $idx => $item): 
                $unit_price = isset($item['unit_price']) ? $item['unit_price'] : (($item['qty'] > 0) ? ($item['subtotal'] / $item['qty']) : 0);
            ?>
                <div class="item-row">
                    <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                    <div class="item-qty">x<?= $item['qty'] ?></div>
                    <div class="item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                </div>
                <div class="item-detail">
                    @ Rp <?= number_format($unit_price, 0, ',', '.') ?> / unit
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-row">
                <div class="summary-label">Total Item:</div>
                <div class="summary-value"><?= $trans['item_count'] ?> Item</div>
            </div>
            <div class="summary-row total">
                <div class="summary-label">TOTAL:</div>
                <div class="summary-value">Rp <?= number_format($trans['total'], 0, ',', '.') ?></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Metode Bayar:</div>
                <div class="summary-value"><?= htmlspecialchars($trans['payment_method'] ?? 'Tunai') ?></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Bayar:</div>
                <div class="summary-value">Rp <?= number_format($trans['paid_amount'] ?? $trans['total'], 0, ',', '.') ?></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Kembalian:</div>
                <div class="summary-value">Rp <?= number_format($trans['change_amount'] ?? 0, 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- Thank You -->
        <div class="thank-you">TERIMA KASIH</div>

        <!-- Footer -->
        <div class="footer">
            <p>Mutiara Tani</p>
            <p>Dicetak: <?= date('d-m-Y H:i:s') ?></p>
        </div>

        <!-- Print Button -->
        <div class="print-button">
            <button onclick="window.print()">
                <i class="bi bi-printer"></i> Cetak / Simpan as PDF
            </button>
        </div>
    </div>

    <script>
        // Auto print jika parameter autoprint=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1') {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        }
    </script>
</body>

</html>
