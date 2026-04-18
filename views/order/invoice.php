<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= $order['order_ref'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;700&family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #000;
            --mid-gray: #666;
            --light-gray: #eee;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: var(--ink);
            margin: 0;
            padding: 40px;
            background: #fff;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 60px;
        }
        .logo {
            font-weight: 900;
            font-size: 24px;
            letter-spacing: -0.04em;
            text-transform: uppercase;
        }
        .invoice-title {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--mid-gray);
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }
        .section-label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--mid-gray);
            margin-bottom: 8px;
            border-bottom: 1px solid var(--light-gray);
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
            padding: 12px 0;
            border-bottom: 2px solid var(--ink);
        }
        td {
            padding: 16px 0;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: top;
        }
        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .total-box {
            width: 280px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .grand-total {
            font-family: 'Inter', sans-serif;
            font-weight: 900;
            font-size: 24px;
            padding-top: 16px;
            border-top: 2px solid var(--ink);
            margin-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 9px;
            font-weight: 700;
            font-family: 'IBM Plex Mono', monospace;
            border-radius: 4px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .badge-paid { background: #000; color: #fff; }
        .badge-preorder { background: #eee; color: #000; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="no-print" style="margin-bottom: 40px; display: flex; gap: 10px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #000; color: #fff; border: none; cursor: pointer; font-weight: 700; border-radius: 6px;">Print Invoice</button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #eee; border: none; cursor: pointer; font-weight: 700; border-radius: 6px;">Close Tab</button>
        </div>

        <div class="header">
            <div>
                <img src="<?= APP_URL ?>/public/assets/img/logo2.jpg" alt="<?= APP_NAME ?>" style="height: 60px; border-radius: 50%; object-fit: cover; aspect-ratio: 1/1; margin-bottom: 12px; display: block;">
                <div style="font-size: 11px; color: var(--mid-gray); margin-top: 4px;">Premium Electronics & Lifestyle Hub</div>
                <div style="font-size: 11px; color: var(--mid-gray);"><?= SITE_EMAIL ?> | Accra, Ghana</div>
            </div>
            <div style="text-align: right;">
                <div class="invoice-title">Official Invoice</div>
                <div style="font-size: 20px; font-weight: 900;"><?= $order['order_ref'] ?></div>
                <div style="font-size: 11px; color: var(--mid-gray);"><?= date('F d, Y', strtotime($order['created_at'])) ?></div>
                <div class="badge badge-paid"><?= strtoupper($order['status']) ?></div>
            </div>
        </div>

        <div class="details-grid">
            <div>
                <div class="section-label">Bill To</div>
                <div style="font-weight: 700; font-size: 15px;"><?= $order['customer_name'] ?></div>
                <div style="color: var(--mid-gray);"><?= $order['customer_email'] ?></div>
                <div><?= $order['customer_phone'] ?></div>
            </div>
            <div>
                <div class="section-label">Ship To</div>
                <div style="line-height: 1.6;">
                    <?= $order['shipping_address'] ?><br>
                    <strong><?= $order['shipping_city'] ?></strong>, <?= $order['shipping_region'] ?><br>
                    GHANA
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div style="font-weight: 700; text-transform: uppercase;"><?= $item['product_name'] ?></div>
                        <?php if($item['is_preorder']): ?>
                            <div class="badge badge-preorder">Pre-order Item</div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center; font-family: 'IBM Plex Mono', monospace;"><?= $item['qty'] ?></td>
                    <td style="text-align: right; font-family: 'IBM Plex Mono', monospace;">₵<?= number_format($item['unit_price_ghs'], 2) ?></td>
                    <td style="text-align: right; font-weight: 700; font-family: 'IBM Plex Mono', monospace;">₵<?= number_format($item['unit_price_ghs'] * $item['qty'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span style="color: var(--mid-gray);">Subtotal</span>
                    <span style="font-family: 'IBM Plex Mono', monospace;">₵<?= number_format($order['subtotal_ghs'], 2) ?></span>
                </div>
                <div class="total-row">
                    <span style="color: var(--mid-gray);">Shipping</span>
                    <span style="font-family: 'IBM Plex Mono', monospace;">₵<?= number_format($order['shipping_ghs'], 2) ?></span>
                </div>
                
                <?php if($order['is_preorder']): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ccc;">
                    <div class="total-row">
                        <span style="color: var(--mid-gray);">Total Order Value</span>
                        <span style="font-family: 'IBM Plex Mono', monospace;">₵<?= number_format($order['total_ghs'], 2) ?></span>
                    </div>
                    <div class="total-row" style="color: #000; font-weight: 700;">
                        <span>Final Amount Paid</span>
                        <span style="font-family: 'IBM Plex Mono', monospace;">₵<?= number_format($order['total_ghs'] - $order['balance_amount_ghs'], 2) ?></span>
                    </div>
                    <?php if($order['balance_amount_ghs'] > 0): ?>
                    <div class="total-row" style="color: #666; font-size: 11px;">
                        <span>Pending Balance Due</span>
                        <span style="font-family: 'IBM Plex Mono', monospace;">₵<?= number_format($order['balance_amount_ghs'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="grand-total total-row">
                    <span>Invoice Total</span>
                    <span>₵<?= number_format($order['total_ghs'], 2) ?></span>
                </div>
            </div>
        </div>

        <div style="margin-top: 80px; text-align: center; color: var(--mid-gray); font-size: 11px; border-top: 1px solid var(--light-gray); padding-top: 20px;">
            Thank you for shopping at <strong><?= APP_NAME ?></strong>.<br>
            This is a computer-generated invoice and does not require a signature.
        </div>
    </div>
</body>
</html>
