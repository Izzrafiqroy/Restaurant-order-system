<?php
session_start();
include 'includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cart = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart)) {
    // Get table number from form (hidden input) or session (from QR scan)
    $table_number = $_POST['table_number'] ?? $_SESSION['table_number'] ?? null;
    
    // Validate table number
    if (!$table_number || !is_numeric($table_number)) {
        header('Location: cart.php?error=Table number is required');
        exit;
    }
    
    $table_number = intval($table_number);
    $order_method = 'walk-in';
    $status = 'Pending';
    
    try {
        // Begin transaction for data integrity
        $conn->begin_transaction();
        
        // Step 1: Insert into orders table with all required fields
        $stmt = $conn->prepare("INSERT INTO orders (table_number, order_method, status, order_time) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iss", $table_number, $order_method, $status);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $order_id = $stmt->insert_id;
        
        if (!$order_id) {
            throw new Exception("Failed to get order ID");
        }
        
        // Step 2: Insert each item into order_items with correct price calculation
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
        if (!$stmt_item) {
            throw new Exception("Prepare items failed: " . $conn->error);
        }
        
        foreach ($cart as $item) {
            // Validate cart item structure
            if (!isset($item['name']) || !isset($item['quantity']) || !isset($item['price'])) {
                throw new Exception("Invalid cart item structure");
            }
            
            // Include addon details in item name for clarity
            $item_name = $item['name'];
            if (!empty($item['addons'])) {
                $addon_names = array_column($item['addons'], 'name');
                $item_name .= ' (with ' . implode(', ', $addon_names) . ')';
            }
            
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['price']); // This already includes addons from add_to_cart.php
            
            // Calculate total price for this line item (unit_price * quantity)
            $total_item_price = $unit_price * $quantity;
            
            // Store the total price (price * quantity) in the price field
            $stmt_item->bind_param("isid", $order_id, $item_name, $quantity, $total_item_price);
            
            if (!$stmt_item->execute()) {
                throw new Exception("Failed to insert item: " . $stmt_item->error);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Step 3: Clear the cart and table session
        unset($_SESSION['cart']);
        unset($_SESSION['table_number']);
        
        // Step 4: Redirect to walk-in success page with order ID
        header("Location: walk_in_order_success.php?order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log error for debugging
        error_log("Order submission error: " . $e->getMessage());
        
        // Display user-friendly error
        die("Error processing your order: " . $e->getMessage() . "<br><a href='cart.php'>Go back to cart</a>");
    }
    
} else {
    // Better error handling for invalid requests
    if (empty($cart)) {
        header("Location: menu.php?error=Your cart is empty");
    } else {
        header("Location: cart.php?error=Invalid request method");
    }
    exit;
}
?>
