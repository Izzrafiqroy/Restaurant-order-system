
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
    $order_id = $_POST['order_id'] ?? '';
    $new_status = $_POST['new_status'] ?? '';
    
    if ($order_id && $new_status) {
        // Validate status
        $valid_statuses = ['Pending', 'Preparing', 'Ready', 'Completed'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Order #$order_id status updated to $new_status";
            } else {
                $_SESSION['error_message'] = "Failed to update order status";
            }
        } else {
            $_SESSION['error_message'] = "Invalid status";
        }
    } else {
        $_SESSION['error_message'] = "Missing order ID or status";
    }
}

header("Location: admin_dashboard.php");
exit;
?>