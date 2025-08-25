<?php
session_start();
include 'includes/db.php';

// Check admin authentication
$admin_logged_in = $_SESSION['admin_logged_in'] ?? false;

if (!$admin_logged_in) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $table_number = $_POST['table_number'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? '';

    try {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, table_number = ?, customer_name = ? WHERE id = ?");
        $stmt->bind_param("sisi", $status, $table_number, $customer_name, $order_id);

        if ($stmt->execute()) {
            header("Location: admin_dashboard.php?tab=manage-orders&success=Order #$order_id updated successfully");
        } else {
            header("Location: admin_dashboard.php?tab=manage-orders&error=Failed to update order");
        }
    } catch (Exception $e) {
        header("Location: admin_dashboard.php?tab=manage-orders&error=Database error");
    }
} else {
    header("Location: admin_dashboard.php");
}
exit;
?>