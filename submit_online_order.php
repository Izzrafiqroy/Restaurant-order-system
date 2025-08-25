<?php
session_start();
include 'includes/db.php';
require_once 'includes/toyyibpay_service.php'; // <-- Add this line

$cart = $_SESSION['online_cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart)) {
    $customer_name = $_POST['customer_name'];
    $customer_email = $_POST['customer_email'];
    $customer_phone = $_POST['customer_phone'];
    $order_type = $_POST['order_type'];
    $delivery_address = $_POST['delivery_address'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $delivery_fee = ($subtotal >= 20) ? 0 : 5;
    $total = $subtotal + $delivery_fee;
    
    // Insert main order
    $stmt = $conn->prepare("INSERT INTO orders (table_number, order_method, customer_name, customer_email, customer_phone, delivery_address, order_type, status, order_time) VALUES (0, 'online', ?, ?, ?, ?, ?, 'Pending', NOW())");
    $stmt->bind_param("sssss", $customer_name, $customer_email, $customer_phone, $delivery_address, $order_type);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert order items
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $item_name = $item['name'];
        if (!empty($item['addons'])) {
            $addon_names = array_column($item['addons'], 'name');
            $item_name .= ' (with ' . implode(', ', $addon_names) . ')';
        }
        $item_quantity = $item['quantity'];
        $total_item_price = $item['price'] * $item['quantity'];
        $stmt_item->bind_param("isid", $order_id, $item_name, $item_quantity, $total_item_price);
        $stmt_item->execute();
    }
    // Add delivery fee if applicable
    if ($delivery_fee > 0) {
        $delivery_item_name = "Delivery Fee";
        $delivery_quantity = 1;
        $stmt_item->bind_param("isid", $order_id, $delivery_item_name, $delivery_quantity, $delivery_fee);
        $stmt_item->execute();
    }

    // Clear cart
    unset($_SESSION['online_cart']);

    // --- ToyyibPay Integration ---
    $item_count = count($cart);
    $payment = ToyyibPayService::createBill($total, $item_count, $customer_name, $customer_email, $customer_phone, $order_id);

    if ($payment && isset($payment['paymentUrl'])) {
        header('Location: ' . $payment['paymentUrl']);
        exit;
    } else {
        echo "<h2>Payment gateway error. Please try again later.</h2>";
        exit;
    }
    // --- End ToyyibPay Integration ---

    // (Remove or comment out the old redirect below)
    // header("Location: online_order_success.php?order_id=" . $order_id);
    // exit;
} else {
    echo "Cart is empty or invalid request.";
}
?>