<?php
session_start();
include 'includes/db.php';

// Capture table number from URL
$table_number = isset($_GET['table']) ? intval($_GET['table']) : null;
if ($table_number) {
    $_SESSION['table_number'] = $table_number;
}

$cart = $_SESSION['cart'] ?? [];
$cartCount = array_sum(array_column($cart, 'quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sup Tulang ZZ Menu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #FAF9F6;
            color: #2E2E2E;
            line-height: 1.7;
            font-weight: 400;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            background: white;
            padding: 60px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(46, 46, 46, 0.06);
            border: 1px solid rgba(180, 205, 186, 0.2);
        }
        .header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3.2em;
            color: #2E2E2E;
            margin-bottom: 12px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .header .icon {
            color: #FF6B35;
            margin-right: 16px;
            font-size: 0.9em;
        }
        .header p {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 24px;
            font-weight: 400;
        }
        .menu-badge {
            background: linear-gradient(135deg, #FF6B35, #FF8555);
            color: white;
            padding: 12px 28px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 50px;
            font-size: 0.9em;
            box-shadow: 0 4px 16px rgba(255, 107, 53, 0.3);
        }
        .cart-section {
            text-align: center;
            margin-bottom: 40px;
            position: sticky;
            top: 20px;
            z-index: 100;
        }
        .cart-link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #2E2E2E;
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(46, 46, 46, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.05em;
        }
        .cart-link:hover {
            background: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(46, 46, 46, 0.3);
        }
        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #2E2E2E;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: 700;
            border: 3px solid #FAF9F6;
        }
        .success-message {
            background: linear-gradient(135deg, #B4CDBA, #C5D6CB);
            color: #2E2E2E;
            padding: 16px 24px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 32px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 4px 16px rgba(180, 205, 186, 0.3);
        }
        .menu-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 40px;
        }
        .menu-btn {
            background: white;
            border: 2px solid rgba(180, 205, 186, 0.3);
            padding: 16px 20px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            color: #2E2E2E;
            font-size: 0.95em;
            box-shadow: 0 2px 8px rgba(46, 46, 46, 0.04);
        }
        .menu-btn:hover {
            border-color: #FF6B35;
            background: rgba(255, 107, 53, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(255, 107, 53, 0.1);
        }
        .menu-btn.active {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
            box-shadow: 0 4px 16px rgba(255, 107, 53, 0.3);
        }
        .group {
            display: none;
            background: white;
            padding: 40px;
            margin-bottom: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(46, 46, 46, 0.06);
            border: 1px solid rgba(180, 205, 186, 0.2);
        }
        .group.active { display: block; }
        .group-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5em;
            color: #2E2E2E;
            margin-bottom: 32px;
            text-align: center;
            font-weight: 600;
            letter-spacing: -0.01em;
        }
        .category {
            margin-bottom: 40px;
        }
        .category h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6em;
            color: #FF6B35;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(255, 107, 53, 0.1);
        }
        .category h3::before {
            content: '🍽';
            font-size: 1.1em;
        }
        .menu-item-card {
            background: #FAF9F6;
            padding: 24px;
            margin-bottom: 20px;
            border-radius: 16px;
            border: 1px solid rgba(180, 205, 186, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .menu-item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(46, 46, 46, 0.1);
            border-color: #FF6B35;
            background: white;
        }
        .menu-item-content {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .menu-item-image {
            flex-shrink: 0;
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            background: white;
            border: 2px solid rgba(180, 205, 186, 0.2);
        }
        .menu-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(180, 205, 186, 0.1);
            color: #B4CDBA;
            font-size: 2em;
        }
        .menu-item-details { flex-grow: 1; }
        .menu-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 16px;
        }
        .menu-item-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4em;
            font-weight: 600;
            color: #2E2E2E;
            line-height: 1.3;
        }
        .menu-item-price {
            font-size: 1.5em;
            font-weight: 700;
            color: #FF6B35;
            background: white;
            padding: 8px 16px;
            border-radius: 8px;
            border: 2px solid #FF6B35;
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
        }
        .addons {
            background: rgba(180, 205, 186, 0.1);
            padding: 20px;
            margin: 20px 0;
            border-radius: 12px;
            border: 1px solid rgba(180, 205, 186, 0.3);
        }
        .addons strong {
            color: #2E2E2E;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .addons > div {
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .addons input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #FF6B35;
        }
        .addons label {
            font-weight: 500;
            color: #2E2E2E;
            cursor: pointer;
        }
        .addons label span {
            color: #FF6B35;
            font-weight: 600;
        }
        .order-form {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(180, 205, 186, 0.3);
        }
        .order-form > div {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .order-form label {
            font-weight: 500;
            color: #2E2E2E;
        }
        .order-form input[type="number"] {
            width: 80px;
            padding: 12px;
            border: 2px solid rgba(180, 205, 186, 0.3);
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            background: white;
            transition: border-color 0.3s ease;
        }
        .order-form input[type="number"]:focus {
            outline: none;
            border-color: #FF6B35;
        }
        .add-to-cart-btn {
            background: linear-gradient(135deg, #FF6B35, #FF8555);
            color: white;
            border: none;
            padding: 14px 24px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95em;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(255, 107, 53, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
            background: linear-gradient(135deg, #E55A2B, #FF6B35);
        }
        .floating-cart {
            position: fixed;
            bottom: 32px;
            right: 32px;
            z-index: 1000;
        }
        .floating-cart .cart-link {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6em;
            padding: 0;
            background: #FF6B35;
            box-shadow: 0 8px 32px rgba(255, 107, 53, 0.4);
        }
        .floating-cart .cart-link:hover {
            background: #E55A2B;
            transform: translateY(-4px) scale(1.05);
        }
        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #2E2E2E;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: 700;
            border: 3px solid #FAF9F6;
        }
        /* Add table indicator styles */
        .table-indicator {
            background: linear-gradient(135deg, #2E2E2E, #404040);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(46, 46, 46, 0.2);
        }
        
        .table-indicator i {
            color: #FF6B35;
        }
        @media (max-width: 768px) {
            .container { padding: 16px; }
            .header { padding: 40px 24px; }
            .header h1 { font-size: 2.2em; }
            .menu-buttons { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px; }
            .menu-item-content { flex-direction: column; gap: 16px; }
            .menu-item-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .floating-cart { bottom: 20px; right: 20px; }
            .floating-cart .cart-link { width: 64px; height: 64px; font-size: 1.4em; }
        }
        @media (max-width: 480px) {
            .header h1 { font-size: 1.8em; }
            .group { padding: 24px; }
            .menu-item-card { padding: 20px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-utensils icon"></i> Sup Tulang ZZ</h1>
        <p>Authentic Malaysian Flavors Since 1995</p>
        <div class="menu-badge">
            <i class="fas fa-store"></i>
            Dine-In Menu
        </div>
        
        <!-- Add table number indicator -->
        <?php if (isset($_SESSION['table_number'])): ?>
            <div class="table-indicator">
                <i class="fas fa-table"></i>
                Table <?= $_SESSION['table_number'] ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <div class="cart-section">
        <a class="cart-link" href="cart.php">
            <i class="fas fa-shopping-cart"></i> View Cart (<?= $cartCount ?>)
        </a>
    </div>
    <?php
    $menuGroups = [
        "Signature Items" => ["Sup ZZ", "Mee Rebus ZZ"],
        "Set Menu" => ["Set Nasi & Lauk", "SET Tengah Hari"],
        "Sarapan" => ["Sarapan"],
        "Roti" => ["Roti Bakar", "Roti Canai"],
        "Seafood" => ["Ikan Siakap & Bakar-Bakar", "Menu Ikan"],
        "Sayur" => ["Sayur"],
        "Aneka Lauk Thai" => ["Aneka Lauk Thai"],
        "Goreng Tepung" => ["Goreng Tepung"],
        "Sup Ala Thai" => ["Sup Ala Thai"],
        "Mee Kuah" => ["Mee Kuah"],
        "Tomyam" => ["Tomyam"],
        "Western Food" => ["Western Food"],
        "Burger & Sides" => ["Burger", "Sides"],
        "Goreng-Goreng" => ["Goreng-Goreng"],
        "Drinks" => ["Drinks"]
    ];
    $categoryToAddonMap = [
        'Sup ZZ' => 'Signature Soups',
        'Mee Rebus ZZ' => 'Signature Soups'
    ];
    ?>
    <div class="menu-buttons">
        <?php foreach ($menuGroups as $groupName => $categories): ?>
            <button class="menu-btn" onclick="showGroup('<?= htmlspecialchars($groupName) ?>')">
                <?= htmlspecialchars($groupName) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php foreach ($menuGroups as $groupName => $categories): ?>
        <div class="group" data-group="<?= htmlspecialchars($groupName) ?>">
            <div class="group-title"><?= htmlspecialchars($groupName) ?></div>
            <?php foreach ($categories as $category): ?>
                <?php
                $stmt = $conn->prepare("SELECT id, name, price, image_url FROM menu_items WHERE category = ?");
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0):
                ?>
                    <div class="category">
                        <h3><?= htmlspecialchars($category) ?></h3>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="menu-item-card">
                                <div class="menu-item-content">
                                    <div class="menu-item-image">
                                        <?php 
                                        $imagePath = $row['image_url'] ?? '';
                                        if (!empty($imagePath)) {
                                            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                                                $webImagePath = $imagePath;
                                            } else {
                                                $webImagePath = '/restaurant-order-system/' . ltrim($imagePath, '/\\');
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($webImagePath) ?>" 
                                                 alt="<?= htmlspecialchars($row['name']) ?>"
                                                 onerror="this.parentElement.innerHTML='<div class=\'no-image\'><i class=\'fas fa-image\'></i></div>'">
                                        <?php 
                                        } else { ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="menu-item-details">
                                        <div class="menu-item-header">
                                            <div class="menu-item-name"><?= htmlspecialchars($row['name']) ?></div>
                                            <div class="menu-item-price">RM<?= number_format($row['price'], 2) ?></div>
                                        </div>
                                        <?php
                                        $addon_category = $categoryToAddonMap[$category] ?? null;
                                        $addon_result = null;
                                        if ($addon_category) {
                                            $stmt_addon = $conn->prepare("SELECT id, name, price FROM addons WHERE applies_to_category = ?");
                                            $stmt_addon->bind_param("s", $addon_category);
                                            $stmt_addon->execute();
                                            $addon_result = $stmt_addon->get_result();
                                        }
                                        ?>
                                        <form action="add_to_cart.php" method="POST">
                                            <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="item_name" value="<?= htmlspecialchars($row['name']) ?>">
                                            <input type="hidden" name="item_price" value="<?= $row['price'] ?>">
                                            <?php if (!empty($addon_result) && $addon_result->num_rows > 0): ?>
                                                <div class="addons">
                                                    <strong><i class="fas fa-plus-circle"></i> Add-Ons:</strong>
                                                    <?php while ($addon = $addon_result->fetch_assoc()): ?>
                                                        <div>
                                                            <input type="checkbox" name="addons[<?= $addon['id'] ?>]" value="<?= $addon['price'] ?>" id="addon_<?= $addon['id'] ?>_<?= $row['id'] ?>">
                                                            <label for="addon_<?= $addon['id'] ?>_<?= $row['id'] ?>">
                                                                <?= htmlspecialchars($addon['name']) ?> 
                                                                <span>(+RM<?= number_format($addon['price'], 2) ?>)</span>
                                                            </label>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="order-form">
                                                <div>
                                                    <label for="qty_<?= $row['id'] ?>">Quantity:</label>
                                                    <input type="number" name="quantity" value="1" min="1" id="qty_<?= $row['id'] ?>">
                                                </div>
                                                <button type="submit" class="add-to-cart-btn">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
<div class="floating-cart">
    <a class="cart-link" href="cart.php">
        <i class="fas fa-shopping-cart"></i>
        <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount ?></span>
        <?php endif; ?>
    </a>
</div>
<script>
    function showGroup(groupName) {
        const buttons = document.querySelectorAll('.menu-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        const groups = document.querySelectorAll('.group');
        groups.forEach(group => {
            group.classList.remove('active');
        });
        const targetGroup = document.querySelector(`[data-group="${groupName}"]`);
        if (targetGroup) {
            targetGroup.classList.add('active');
        }
    }
    window.addEventListener('DOMContentLoaded', () => {
        const firstButton = document.querySelector('.menu-btn');
        const firstGroup = document.querySelector('.group');
        if (firstButton && firstGroup) {
            firstButton.classList.add('active');
            firstGroup.classList.add('active');
        }
    });
</script>
</body>
</html>