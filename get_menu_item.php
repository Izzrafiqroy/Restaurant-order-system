<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

// Check admin authentication
$admin_logged_in = $_SESSION['admin_logged_in'] ?? false;

if (!$admin_logged_in) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$item_id = $_GET['id'] ?? 0;

try {
    $stmt = $conn->prepare("SELECT id, name, category, price, description FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($item = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'item' => $item]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
exit;
?>