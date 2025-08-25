<?php
session_start();
$cart = $_SESSION['online_cart'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - Sup Tulang ZZ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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
        .online-badge {
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
        .cart-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
        }
        .cart-item-card {
            background: #FAF9F6;
            padding: 24px;
            margin-bottom: 20px;
            border-radius: 16px;
            border: 1px solid rgba(180, 205, 186, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .cart-item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(46, 46, 46, 0.1);
            border-color: #FF6B35;
            background: white;
        }
        .cart-item-content {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .cart-item-image {
            flex-shrink: 0;
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            background: white;
            border: 2px solid rgba(180, 205, 186, 0.2);
        }
        .cart-item-image img {
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
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 16px;
            width: 100%;
        }
        .cart-item-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4em;
            font-weight: 600;
            color: #2E2E2E;
            line-height: 1.3;
            flex: 1 1 auto;
        }
        .cart-item-price {
            font-size: 1.5em;
            font-weight: 700;
            color: #FF6B35;
            background: white;
            padding: 8px 32px;
            border-radius: 8px;
            border: 2px solid #FF6B35;
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
            margin-left: 16px;
            margin-right: 16px;
            align-self: flex-start;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
        }
        .cart-item-remove {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
            color: #666;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            align-self: flex-start;
        }
        .cart-item-header > *:last-child {
            margin-left: auto;
        }
        .cart-item-info {
            display: flex;
            gap: 24px;
            align-items: center;
            margin-bottom: 12px;
        }
        .cart-item-qty {
            font-size: 1.1em;
            font-weight: 500;
            color: #2E2E2E;
        }
        .cart-item-subtotal {
            font-size: 1.1em;
            font-weight: 600;
            color: #FF6B35;
        }
        .addons-section {
            background: rgba(180, 205, 186, 0.1);
            padding: 16px;
            border-radius: 12px;
            border: 1px solid rgba(180, 205, 186, 0.3);
            margin-top: 10px;
        }
        .addons-title {
            color: #2E2E2E;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .addon-tag {
            background: #B4CDBA;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 8px;
            margin-bottom: 6px;
            display: inline-block;
        }
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            font-size: 1rem;
        }
        .summary-row.total {
            border-top: 2px solid #f0f0f0;
            margin-top: 16px;
            padding-top: 20px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #FF6B35;
        }
        .delivery-notice {
            /* Change from green-yellow gradient to orange */
            background: linear-gradient(90deg, #FF6B35, #FF8555);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
        }
        .delivery-notice i {
            margin-right: 8px;
        }
        .checkout-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
        }
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2E2E2E;
            margin-bottom: 24px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .section-title i {
            color: #FF6B35;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: 500;
            color: #2E2E2E;
            margin-bottom: 8px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-label i {
            color: #FF6B35;
            width: 16px;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            background: white;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        .order-btn {
            background: #FF6B35;
            color: white;
            border: none;
            padding: 16px 32px;
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: block;
            margin: 30px auto 0;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        .order-btn:hover {
            background: #E55A2B;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 53, 0.4);
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
        }
        .btn-secondary {
            background: #B4CDBA;
            color: white;
        }
        .btn-secondary:hover {
            background: #FFD700;
            transform: translateY(-1px);
        }
        .btn-outline {
            background: white;
            color: #FF6B35;
            border: 2px solid #FF6B35;
        }
        .btn-outline:hover {
            background: #FF6B35;
            color: white;
        }
        .empty-cart {
            text-align: center;
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .empty-cart i {
            font-size: 4rem;
            color: #B4CDBA;
            margin-bottom: 24px;
        }
        .empty-cart h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.75rem;
            color: #2E2E2E;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .empty-cart p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 30px;
        }
        #address_group {
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            .header {
                padding: 40px 24px;
            }
            .header h1 {
                font-size: 2.2em;
            }
            .cart-item-content {
                flex-direction: column;
                gap: 16px;
            }
            .cart-item-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .btn {
                width: 100%;
                max-width: 200px;
                justify-content: center;
            }
        }
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8em;
            }
            .cart-item-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-shopping-cart icon"></i>Your Cart</h1>
        <p>Review your order and proceed to checkout</p>
        <div class="online-badge">
            <i class="fas fa-truck"></i>
            Online Ordering
        </div>
    </div>
    <div class="cart-section">
        <a class="cart-link" href="online_cart.php">
            <i class="fas fa-shopping-cart"></i>
            View Cart (<?= array_sum(array_column($cart, 'quantity')) ?>)
        </a>
    </div>
    <?php if (empty($cart)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added any delicious items to your cart yet.</p>
            <a href="online_menu.php" class="btn btn-secondary">
                <i class="fas fa-utensils"></i> Browse Menu
            </a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <?php
            $grand_total = 0;
            foreach ($cart as $index => $item):
                $item_total = $item['price'] * $item['quantity'];
                $grand_total += $item_total;
            ?>
            <div class="cart-item-card">
                <div class="cart-item-content">
                    <!-- Removed .cart-item-image container -->
                    <div class="cart-item-details">
                        <div class="cart-item-header">
                            <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="cart-item-price">RM<?= number_format($item['price'], 2) ?></div>
                            <form action="remove_from_online_cart.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="item_index" value="<?= $index ?>">
                                <button type="submit" class="cart-item-remove" onclick="return confirm('Remove this item from your cart?')">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </form>
                        </div>
                        <div class="cart-item-info">
                            <span class="cart-item-qty">Qty: <?= $item['quantity'] ?></span>
                            <span class="cart-item-subtotal">Subtotal: RM<?= number_format($item_total, 2) ?></span>
                        </div>
                        <?php if (!empty($item['addons'])): ?>
                            <div class="addons-section">
                                <div class="addons-title">
                                    <i class="fas fa-plus-circle"></i> Add-ons
                                </div>
                                <?php foreach ($item['addons'] as $addon): ?>
                                    <span class="addon-tag">
                                        <?= htmlspecialchars($addon['name']) ?> +RM<?= number_format($addon['price'], 2) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        $delivery_fee = ($grand_total >= 20) ? 0 : 5;
        $final_total = $grand_total + $delivery_fee;
        ?>
        <div class="summary-card">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>RM<?= number_format($grand_total, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery Fee</span>
                <span><?= $delivery_fee > 0 ? 'RM' . number_format($delivery_fee, 2) : 'FREE' ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span>RM<?= number_format($final_total, 2) ?></span>
            </div>
        </div>
        <?php if ($grand_total < 20): ?>
            <div class="delivery-notice">
                <i class="fas fa-truck"></i>
                Add RM<?= number_format(20 - $grand_total, 2) ?> more to your order for FREE delivery!
            </div>
        <?php endif; ?>
        <div class="checkout-section">
            <div class="section-title">
                <i class="fas fa-clipboard-check"></i>
                Checkout Details
            </div>
            <form action="submit_online_order.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="customer_name" class="form-label">
                            <i class="fas fa-user"></i> Full Name *
                        </label>
                        <input type="text" name="customer_name" id="customer_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone Number *
                        </label>
                        <input type="tel" name="customer_phone" id="customer_phone" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="customer_email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address *
                    </label>
                    <input type="email" name="customer_email" id="customer_email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="order_type" class="form-label">
                        <i class="fas fa-truck"></i> Order Type *
                    </label>
                    <select name="order_type" id="order_type" class="form-select" required onchange="toggleAddress()">
                        <option value="">Choose delivery or pickup</option>
                        <option value="delivery">Delivery</option>
                        <option value="pickup">Pickup</option>
                    </select>
                </div>
                <div class="form-group" id="address_group" style="display: none;">
                    <label for="delivery_address" class="form-label">
                        <i class="fas fa-map-marker-alt"></i> Delivery Address *
                    </label>
                    <textarea name="delivery_address" id="delivery_address" class="form-textarea" rows="3" placeholder="Enter your complete delivery address"></textarea>
                </div>
                <div class="form-group">
                    <label for="notes" class="form-label">
                        <i class="fas fa-comment"></i> Special Instructions
                    </label>
                    <textarea name="notes" id="notes" class="form-textarea" rows="2" placeholder="Any special requests or cooking preferences..."></textarea>
                </div>
                <button type="submit" class="order-btn">
                    <i class="fas fa-check-circle"></i>
                    Place Order • RM<?= number_format($final_total, 2) ?>
                </button>
            </form>
        </div>
        <div class="action-buttons">
            <a href="online_menu.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
            <a href="clear_online_cart.php" class="btn btn-outline" onclick="return confirm('Are you sure you want to clear your cart?')">
                <i class="fas fa-trash-alt"></i> Clear Cart
            </a>
        </div>
    <?php endif; ?>
</div>
<script>
    function toggleAddress() {
        const orderType = document.getElementById('order_type').value;
        const addressGroup = document.getElementById('address_group');
        const addressField = document.getElementById('delivery_address');
        if (orderType === 'delivery') {
            addressGroup.style.display = 'block';
            addressField.required = true;
        } else {
            addressGroup.style.display = 'none';
            addressField.required = false;
            addressField.value = '';
        }
    }
</script>
</body>
</html>