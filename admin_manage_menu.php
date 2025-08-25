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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $name = $_POST['name'];
                $category = $_POST['category'];
                $price = $_POST['price'];
                $description = $_POST['description'] ?? '';
                
                $stmt = $conn->prepare("INSERT INTO menu_items (name, category, price, description) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $name, $category, $price, $description);
                
                if ($stmt->execute()) {
                    header("Location: admin_dashboard.php?tab=menu-items&success=Item added successfully");
                } else {
                    header("Location: admin_dashboard.php?tab=menu-items&error=Failed to add item");
                }
                break;
                
            case 'edit':
                $item_id = $_POST['item_id'];
                $name = $_POST['name'];
                $category = $_POST['category'];
                $price = $_POST['price'];
                $description = $_POST['description'] ?? '';
                
                $stmt = $conn->prepare("UPDATE menu_items SET name = ?, category = ?, price = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssdsi", $name, $category, $price, $description, $item_id);
                
                if ($stmt->execute()) {
                    header("Location: admin_dashboard.php?tab=menu-items&success=Item updated successfully");
                } else {
                    header("Location: admin_dashboard.php?tab=menu-items&error=Failed to update item");
                }
                break;
                
            case 'delete':
                $item_id = $_POST['item_id'];
                
                $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        if ($action === 'delete') {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        } else {
            header("Location: admin_dashboard.php?tab=menu-items&error=Database error");
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
exit;
?>