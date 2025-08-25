<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $item_price = (float) $_POST['item_price'];
    $quantity = (int) $_POST['quantity'];
    $selected_addons = $_POST['addons'] ?? [];

    // Prepare add-on details
    $addons_detail = [];
    $addon_total_price = 0.00;

    if (!empty($selected_addons)) {
        foreach ($selected_addons as $addon_id => $addon_price) {
            $stmt = $conn->prepare("SELECT name FROM addons WHERE id = ?");
            $stmt->bind_param("i", $addon_id);
            $stmt->execute();
            $stmt->bind_result($addon_name);
            $stmt->fetch();
            $stmt->close();

            $addons_detail[] = [
                'id' => $addon_id,
                'name' => $addon_name,
                'price' => (float) $addon_price
            ];
            $addon_total_price += (float) $addon_price;
        }
    }

    $total_unit_price = $item_price + $addon_total_price;

    if (!isset($_SESSION['online_cart'])) {
        $_SESSION['online_cart'] = [];
    }

    // Create unique key for online cart
    $addon_keys = array_keys($selected_addons);
    sort($addon_keys);
    $unique_key = $item_id . '-' . implode('-', $addon_keys);

    $_SESSION['online_cart'][$unique_key] = [
        'name' => $item_name,
        'price' => $total_unit_price,
        'quantity' => $quantity,
        'addons' => $addons_detail
    ];

    header('Location: online_menu.php?success=' . urlencode("$item_name added to cart"));
    exit;
}
?>