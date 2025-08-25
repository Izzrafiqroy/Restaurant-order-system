<?php
include 'includes/db.php';

$item_id = $_POST['item_id'];
$quantity = $_POST['quantity'];

// For now, just show what was ordered
echo "You added item ID: $item_id, Quantity: $quantity";
?>