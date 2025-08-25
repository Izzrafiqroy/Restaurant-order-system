<?php
include 'includes/db.php';

$order_id = $_GET['oid'] ?? null;
$order_details = null;
$order_items = [];

if ($order_id) {
    // Fetch order details
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND order_method = 'online'");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_details = $result->fetch_assoc();
    
    // Fetch order items
    if ($order_details) {
        $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
    }
}

// Calculate totals
$subtotal = 0;
$delivery_fee = 0;
foreach ($order_items as $item) {
    if ($item['item_name'] === 'Delivery Fee') {
        $delivery_fee = $item['price'];
    } else {
        $subtotal += $item['price'];
    }
}
$total = $subtotal + $delivery_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmed - Sup Tulang ZZ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #FAF9F6;
            color: #2E2E2E;
            line-height: 1.7;
            font-weight: 400;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            background: white;
            padding: 50px 30px 30px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(46, 46, 46, 0.06);
            border: 1px solid rgba(180, 205, 186, 0.2);
        }
        .header .icon {
            color: #27ae60;
            font-size: 3em;
            margin-bottom: 10px;
        }
        .header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2em;
            color: #2E2E2E;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .header p {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 12px;
            font-weight: 400;
        }
        .order-ref {
            background: #f8f6f0;
            color: #8b4513;
            border-radius: 8px;
            padding: 12px 0;
            font-weight: 600;
            font-size: 1.1em;
            margin-bottom: 10px;
            display: inline-block;
            border-left: 5px solid #FF6B35;
        }
        .online-badge {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .order-details {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            margin-bottom: 30px;
        }
        .order-info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 20px;
        }
        .order-info-col {
            flex: 1 1 220px;
            min-width: 180px;
        }
        .order-info-col strong {
            color: #2E2E2E;
            font-weight: 600;
        }
        .order-status {
            color: #f39c12;
            font-weight: bold;
        }
        .cart-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            margin-bottom: 30px;
        }
        .cart-item-card {
            background: #FAF9F6;
            padding: 18px;
            margin-bottom: 16px;
            border-radius: 12px;
            border: 1px solid rgba(180, 205, 186, 0.2);
        }
        .cart-item-content {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .cart-item-details { flex-grow: 1; }
        .cart-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 16px;
            width: 100%;
        }
        .cart-item-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1em;
            font-weight: 600;
            color: #2E2E2E;
            flex: 1 1 auto;
        }
        .cart-item-price {
            font-size: 1.1em;
            font-weight: 700;
            color: #FF6B35;
            background: white;
            padding: 6px 18px;
            border-radius: 8px;
            border: 2px solid #FF6B35;
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
            align-self: flex-start;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
        }
        .cart-item-info {
            display: flex;
            gap: 18px;
            align-items: center;
            margin-bottom: 6px;
        }
        .cart-item-qty {
            font-size: 1em;
            font-weight: 500;
            color: #2E2E2E;
        }
        .cart-item-subtotal {
            font-size: 1em;
            font-weight: 600;
            color: #FF6B35;
        }
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-top: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            font-size: 1rem;
        }
        .summary-row.total {
            border-top: 2px solid #f0f0f0;
            margin-top: 12px;
            padding-top: 16px;
            font-size: 1.3rem;
            font-weight: 700;
            color: #FF6B35;
        }
        .next-steps {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 18px;
            margin: 30px 0 0 0;
            border-radius: 10px;
        }
        .next-steps h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .steps-list {
            list-style: none;
            margin-left: 0;
            padding-left: 0;
        }
        .steps-list li {
            margin-bottom: 8px;
            padding-left: 22px;
            position: relative;
        }
        .steps-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: 700;
        }
        .contact-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .contact-info h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        .phone-number {
            font-size: 1.2em;
            font-weight: 700;
            color: #1976d2;
        }
        .action-buttons {
            text-align: center;
            margin-top: 32px;
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.95em;
        }
        .btn-primary {
            background: #FF6B35;
            color: white;
            border: 2px solid #FF6B35;
        }
        .btn-secondary {
            background: transparent;
            color: #34495e;
            border: 2px solid #34495e;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .btn-primary:hover {
            background: #E55A2B;
            border-color: #E55A2B;
        }
        .btn-secondary:hover {
            background: #34495e;
            color: white;
        }
        @media (max-width: 768px) {
            .container { padding: 8px; }
            .header { padding: 30px 10px 20px 10px; }
            .cart-item-content { flex-direction: column; gap: 10px; }
            .cart-item-header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .order-details { padding: 18px; }
            .cart-container { padding: 14px; }
            .summary-card { padding: 14px; }
            .action-buttons { flex-direction: column; align-items: center; }
            .btn { width: 100%; max-width: 220px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Order Confirmed!</h1>
        <p>Thank you for ordering with Sup Tulang ZZ</p>
        <?php if ($order_details): ?>
            <div class="order-ref">
                <i class="fas fa-receipt"></i> Order Reference: #<?= htmlspecialchars($order_id) ?>
            </div>
            <div class="online-badge">
                <i class="fas fa-<?= $order_details['order_type'] === 'pickup' ? 'store' : 'truck' ?>"></i>
                <?= ucfirst($order_details['order_type']) ?> Order
            </div>
        <?php endif; ?>
    </div>

    <?php if ($order_details): ?>
        <div class="order-details">
            <div class="order-info-row">
                <div class="order-info-col">
                    <strong>Name:</strong> <?= htmlspecialchars($order_details['customer_name']) ?><br>
                    <strong>Phone:</strong> <?= htmlspecialchars($order_details['customer_phone']) ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($order_details['customer_email']) ?><br>
                    <strong>Order Time:</strong> <?= date('d M Y, g:i A', strtotime($order_details['order_time'])) ?><br>
                    <strong>Status:</strong> <span class="order-status"><?= htmlspecialchars($order_details['status']) ?></span>
                </div>
                <div class="order-info-col">
                    <strong>Order Type:</strong> <?= ucfirst($order_details['order_type']) ?><br>
                    <?php if ($order_details['order_type'] === 'delivery'): ?>
                        <strong>Delivery Address:</strong><br>
                        <?= nl2br(htmlspecialchars($order_details['delivery_address'])) ?><br>
                        <strong>Delivery Fee:</strong> <?= $delivery_fee > 0 ? 'RM' . number_format($delivery_fee, 2) : 'FREE' ?><br>
                    <?php else: ?>
                        <strong>Pickup Location:</strong><br>
                        Sup Tulang ZZ Restaurant<br>
                        123 Jalan Makan, Taman Sedap<br>
                        12345 Kuala Lumpur<br>
                        <strong>Operating Hours:</strong> Daily: 8:00 AM - 11:00 PM
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="cart-container">
            <?php foreach ($order_items as $item): ?>
                <?php if ($item['item_name'] !== 'Delivery Fee'): ?>
                <div class="cart-item-card">
                    <div class="cart-item-content">
                        <div class="cart-item-details">
                            <div class="cart-item-header">
                                <div class="cart-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <div class="cart-item-price">RM<?= number_format($item['price'], 2) ?></div>
                            </div>
                            <div class="cart-item-info">
                                <span class="cart-item-qty">Qty: <?= $item['quantity'] ?></span>
                                <span class="cart-item-subtotal">Subtotal: RM<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="summary-card">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>RM<?= number_format($subtotal, 2) ?></span>
            </div>
            <?php if ($delivery_fee > 0): ?>
            <div class="summary-row">
                <span>Delivery Fee</span>
                <span>RM<?= number_format($delivery_fee, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row total">
                <span>Total</span>
                <span>RM<?= number_format($total, 2) ?></span>
            </div>
        </div>

        <div class="next-steps">
            <h3><i class="fas fa-clock"></i> What's Next?</h3>
            <ul class="steps-list">
                <li>You will receive a confirmation email shortly</li>
                <li>Estimated preparation time: 30-45 minutes</li>
                <?php if ($order_details['order_type'] === 'delivery'): ?>
                    <li>Our delivery team will contact you when your order is ready</li>
                    <li>Expected delivery time: 45-60 minutes from now</li>
                <?php else: ?>
                    <li>We'll call you when your order is ready for pickup</li>
                    <li>Please bring your order reference number: #<?= htmlspecialchars($order_id) ?></li>
                <?php endif; ?>
                <li>You can track your order status by calling us</li>
            </ul>
        </div>

        <div class="contact-info">
            <h4><i class="fas fa-phone"></i> Questions or Changes?</h4>
            <p>Contact us immediately at: <span class="phone-number">+60 11-6956-6961</span></p>
            <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                Please have your order reference number ready: #<?= htmlspecialchars($order_id) ?>
            </p>
        </div>
    <?php else: ?>
        <div class="order-details" style="background: #f8d7da; border-left: 5px solid #dc3545;">
            <h3 style="color: #721c24;"><i class="fas fa-exclamation-triangle"></i> Order Not Found</h3>
            <p style="color: #721c24; margin-top: 10px;">
                We couldn't find the order details. Please contact us if you have any concerns.
            </p>
        </div>
    <?php endif; ?>

    <div class="action-buttons">
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="online_menu.php" class="btn btn-primary">
            <i class="fas fa-shopping-cart"></i> Order Again
        </a>
        <a href="index.html" class="btn btn-secondary">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</div>
</body>
</html>