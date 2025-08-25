<?php
session_start();
include 'includes/db.php'; // Required to fetch addon names

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $item_price = (float) $_POST['item_price'];
    $quantity = (int) $_POST['quantity'];
    $selected_addons = $_POST['addons'] ?? []; // [addon_id => price]

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

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Create unique key: item ID + add-on IDs
    $addon_keys = array_keys($selected_addons);
    sort($addon_keys); // to ensure consistent key
    $unique_key = $item_id . '-' . implode('-', $addon_keys);

    $_SESSION['cart'][$unique_key] = [
        'name' => $item_name,
        'price' => $total_unit_price,
        'quantity' => $quantity,
        'addons' => $addons_detail
    ];

    header('Location: menu.php?success=' . urlencode("$item_name added to cart"));
    exit;
}
?>