
<?php
session_start();
include 'includes/db.php';

// Check admin authentication
$admin_logged_in = $_SESSION['admin_logged_in'] ?? false;

if (!$admin_logged_in) {
    header("Location: admin_login.php");
    exit;
}

$order_id = $_GET['id'] ?? null;
$order_details = null;
$order_items = [];

if ($order_id) {
    // Fetch order details
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
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
    <title>Order Details #<?= $order_id ?> - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Crimson+Text:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Crimson Text', serif;
            background: #f8f9fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .back-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #2980b9;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .order-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .order-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .meta-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .meta-label {
            font-weight: 600;
            color: #7f8c8d;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 1.1em;
            color: #2c3e50;
        }

        .status-update {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .status-select {
            padding: 10px 15px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 1em;
        }

        .update-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .update-btn:hover {
            background: #229954;
        }

        .order-items {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .items-header {
            background: #34495e;
            color: white;
            padding: 20px;
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .total-section {
            background: #2c3e50;
            color: white;
            padding: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .grand-total {
            font-size: 1.3em;
            font-weight: 700;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 10px;
            margin-top: 10px;
        }

        .customer-info {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .info-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .print-btn {
            background: #9b59b6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 15px;
        }

        .print-btn:hover {
            background: #8e44ad;
        }

        @media (max-width: 768px) {
            .order-meta {
                grid-template-columns: 1fr;
            }

            .status-form {
                flex-direction: column;
                align-items: stretch;
            }

            .items-table {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

<div class="admin-header">
    <div class="admin-nav">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1 style="font-family: 'Playfair Display', serif;">Order Details</h1>
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<div class="container">
    <?php if ($order_details): ?>
        <div class="order-header">
            <h1 class="order-title">Order #<?= htmlspecialchars($order_id) ?></h1>
            
            <div class="order-meta">
                <div class="meta-item">
                    <div class="meta-label">Order Time</div>
                    <div class="meta-value"><?= date('d M Y, g:i A', strtotime($order_details['order_time'])) ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Order Method</div>
                    <div class="meta-value"><?= ucfirst($order_details['order_method']) ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Current Status</div>
                    <div class="meta-value"><?= htmlspecialchars($order_details['status']) ?></div>
                </div>
                <?php if ($order_details['order_method'] == 'walk-in'): ?>
                    <div class="meta-item">
                        <div class="meta-label">Table Number</div>
                        <div class="meta-value"><?= htmlspecialchars($order_details['table_number']) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="status-update">
                <form class="status-form" method="POST" action="update_order_status.php">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <label for="new_status"><strong>Update Status:</strong></label>
                    <select name="new_status" class="status-select">
                        <option value="Pending" <?= $order_details['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Preparing" <?= $order_details['status'] == 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                        <option value="Ready" <?= $order_details['status'] == 'Ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="Completed" <?= $order_details['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                    <button type="submit" class="update-btn">Update Status</button>
                </form>
            </div>
        </div>

        <?php if ($order_details['order_method'] == 'online'): ?>
            <div class="customer-info">
                <h3 class="info-title">Customer Information</h3>
                <div class="info-grid">
                    <div class="meta-item">
                        <div class="meta-label">Customer Name</div>
                        <div class="meta-value"><?= htmlspecialchars($order_details['customer_name']) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Phone Number</div>
                        <div class="meta-value"><?= htmlspecialchars($order_details['customer_phone']) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Email</div>
                        <div class="meta-value"><?= htmlspecialchars($order_details['customer_email']) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Order Type</div>
                        <div class="meta-value"><?= ucfirst($order_details['order_type']) ?></div>
                    </div>
                    <?php if ($order_details['order_type'] == 'delivery' && $order_details['delivery_address']): ?>
                        <div class="meta-item" style="grid-column: 1 / -1;">
                            <div class="meta-label">Delivery Address</div>
                            <div class="meta-value"><?= nl2br(htmlspecialchars($order_details['delivery_address'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="order-items">
            <div class="items-header">
                <i class="fas fa-list"></i> Order Items
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <?php if ($item['item_name'] !== 'Delivery Fee'): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>RM<?= number_format($item['price'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>RM<?= number_format($subtotal, 2) ?></span>
                </div>
                <?php if ($delivery_fee > 0): ?>
                    <div class="total-row">
                        <span>Delivery Fee:</span>
                        <span>RM<?= number_format($delivery_fee, 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>RM<?= number_format($total, 2) ?></span>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="order-header">
            <h1 class="order-title">Order Not Found</h1>
            <p style="text-align: center; color: #7f8c8d;">The requested order could not be found.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>