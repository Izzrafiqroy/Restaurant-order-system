<?php
session_start();

// Clear the cart by unsetting the cart session variable
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

// Alternatively, you could also use:
// $_SESSION['cart'] = [];

// Redirect back to the cart page
header('Location: cart.php');
exit;
?>