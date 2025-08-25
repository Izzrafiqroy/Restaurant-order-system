<?php
session_start();
include 'includes/db.php';

// Simple authentication check - can be enhanced later
$admin_logged_in = $_SESSION['admin_logged_in'] ?? false;

// Simple login check (fallback if no proper auth system)
if (!$admin_logged_in) {
    // Check for simple admin session
    if (!isset($_SESSION['simple_admin'])) {
        header("Location: admin_login.php");
        exit;
    }
}

// Default user info if no proper auth system
$user_info = [
    'id' => $_SESSION['admin_id'] ?? 1,
    'username' => $_SESSION['admin_username'] ?? 'admin',
    'email' => $_SESSION['admin_email'] ?? 'admin@localhost',
    'name' => $_SESSION['admin_name'] ?? 'Administrator',
    'role' => $_SESSION['admin_role'] ?? 'admin',
    'login_time' => $_SESSION['login_time'] ?? time()
];

// Try to include auth system if it exists
if (file_exists('includes/admin_auth.php')) {
    include 'includes/admin_auth.php';
    $auth = new AdminAuth($conn);
    
    // Check authentication with proper system
    if (!$auth->isAuthenticated()) {
        header("Location: admin_login.php");
        exit;
    }
    
    // Get user info from auth system
    $user_info = $auth->getUserInfo();
}

// Get current tab
$current_tab = $_GET['tab'] ?? 'dashboard';

// Handle QR Code Generation
if ($current_tab === 'qr-generator') {
    $qr_generated = false;
    $qr_error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_qr'])) {
        $table_count = intval($_POST['table_count'] ?? 10);
        $base_url = $_POST['base_url'] ?? "http://localhost/restaurant-order-system/menu.php?table=";
        
        try {
            // Create QR codes directory if it doesn't exist
            $qr_dir = "qr_codes/";
            if (!is_dir($qr_dir)) {
                mkdir($qr_dir, 0755, true);
            }
            
            // Check if phpqrcode library exists
            if (!file_exists('phpqrcode/phpqrcode.php')) {
                $qr_error = "PHPQRCode library not found. Please install it in the 'phpqrcode' directory.";
            } else {
                include_once 'phpqrcode/phpqrcode.php';
                
                // Generate QR codes
                for ($i = 1; $i <= $table_count; $i++) {
                    $text = $base_url . $i;
                    $filename = $qr_dir . "table_" . $i . ".png";
                    QRcode::png($text, $filename, 'L', 4, 2);
                }
                
                $qr_generated = true;
            }
        } catch (Exception $e) {
            $qr_error = "Error generating QR codes: " . $e->getMessage();
        }
    }
}

// Fetch orders with pagination for dashboard
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filter options for orders
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$method_filter = $_GET['method'] ?? '';

// Build query with filters for orders
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($date_filter) {
    $where_conditions[] = "DATE(order_time) = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}

if ($method_filter) {
    $where_conditions[] = "order_method = ?";
    $params[] = $method_filter;
    $param_types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Count total orders for pagination
$count_query = "SELECT COUNT(*) as total FROM orders $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);

// Fetch orders for dashboard
if ($current_tab === 'dashboard') {
    $query = "SELECT * FROM orders $where_clause ORDER BY order_time DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $param_types .= 'ii';

    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch menu items for menu management with filters
if ($current_tab === 'menu-items') {
    $category_filter = $_GET['category'] ?? '';
    $search_filter = $_GET['search'] ?? '';
    
    $menu_where_conditions = [];
    $menu_params = [];
    $menu_param_types = '';
    
    if ($category_filter) {
        $menu_where_conditions[] = "category = ?";
        $menu_params[] = $category_filter;
        $menu_param_types .= 's';
    }
    
    if ($search_filter) {
        $menu_where_conditions[] = "name LIKE ?";
        $menu_params[] = '%' . $search_filter . '%';
        $menu_param_types .= 's';
    }
    
    $menu_where_clause = '';
    if (!empty($menu_where_conditions)) {
        $menu_where_clause = 'WHERE ' . implode(' AND ', $menu_where_conditions);
    }
    
    $menu_query = "SELECT * FROM menu_items $menu_where_clause ORDER BY category, name";
    
    if (!empty($menu_params)) {
        $menu_stmt = $conn->prepare($menu_query);
        $menu_stmt->bind_param($menu_param_types, ...$menu_params);
        $menu_stmt->execute();
        $menu_items = $menu_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $menu_result = $conn->query($menu_query);
        $menu_items = $menu_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get order statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'Preparing' THEN 1 ELSE 0 END) as preparing_orders,
    SUM(CASE WHEN status = 'Ready' THEN 1 ELSE 0 END) as ready_orders,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN DATE(order_time) = CURDATE() THEN 1 ELSE 0 END) as today_orders
    FROM orders";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Available categories from your database
$available_categories = [
    'Sup ZZ',
    'Mee Rebus ZZ',
    'Roti Bakar',
    'Sarapan (Breakfast)',
    'Roti Canai',
    'Set Nasi & Lauk',
    'Ikan Siakap & Bakar-Bakar',
    'Menu Ikan',
    'Sayur',
    'Aneka Lauk Thai',
    'Goreng Tepung',
    'Sup Ala Thai',
    'Mee Kuah',
    'Tomyam',
    'Western Food',
    'Spaghetti',
    'Burger',
    'Sides',
    'Goreng-Goreng',
    'Drinks'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Sup Tulang ZZ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Crimson+Text:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Crimson Text', serif;
            background: #f8f9fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar-subtitle {
            font-size: 0.9em;
            opacity: 0.8;
            font-style: italic;
        }

        .user-profile {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            margin: 0 auto 15px;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-role {
            font-size: 0.85em;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-section-title {
            padding: 0 20px 10px;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
            font-weight: 600;
        }

        .nav-item {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }

        .nav-item.active {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            font-weight: 600;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            width: 100%;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        .content-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #ecf0f1;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .content-title {
            font-family: 'Playfair Display', serif;
            font-size: 2em;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .content-subtitle {
            color: #7f8c8d;
            font-style: italic;
        }

        .content-body {
            padding: 30px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
        }

        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.preparing { border-left-color: #e74c3c; }
        .stat-card.ready { border-left-color: #2ecc71; }
        .stat-card.completed { border-left-color: #9b59b6; }
        .stat-card.today { border-left-color: #1abc9c; }

        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1em;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filters-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .filter-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .filter-btn:hover {
            background: #2980b9;
        }

        .orders-table, .menu-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .orders-table th,
        .orders-table td,
        .menu-table th,
        .menu-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .orders-table th,
        .menu-table th {
            background: #34495e;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9em;
        }

        .orders-table tr:hover,
        .menu-table tr:hover {
            background: #f8f9fa;
        }

        .method-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .method-walk-in { background: #e3f2fd; color: #1976d2; }
        .method-online { background: #f3e5f5; color: #7b1fa2; }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #f8d7da; color: #721c24; }
        .status-ready { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-new {
            background: #2ecc71;
            color: white;
            padding: 12px 25px;
            margin-bottom: 20px;
            font-size: 1em;
            border-radius: 5px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8em;
            color: #2c3e50;
        }

        .close {
            font-size: 2em;
            font-weight: bold;
            cursor: pointer;
            color: #7f8c8d;
        }

        .close:hover {
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-save {
            background: #2ecc71;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            text-decoration: none;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            color: #2c3e50;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
        }

        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* QR Code Styles */
        .qr-config-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .qr-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .qr-card:hover {
            transform: translateY(-5px);
        }

        .qr-card img {
            width: 150px;
            height: 150px;
            margin-bottom: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
        }

        .qr-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }

        .download-btn {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background 0.3s;
        }

        .download-btn:hover {
            background: #2980b9;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .content-header {
                padding-left: 70px;
            }

            .qr-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>

<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<div class="admin-layout">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-utensils"></i>
                Sup Tulang ZZ
            </div>
            <div class="sidebar-subtitle">Admin Panel</div>
        </div>

        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-name"><?= htmlspecialchars($user_info['name']) ?></div>
            <div class="user-role"><?= htmlspecialchars($user_info['role']) ?></div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="?tab=dashboard" class="nav-item <?= $current_tab === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    Dashboard
                </a>
                <a href="?tab=menu-items" class="nav-item <?= $current_tab === 'menu-items' ? 'active' : '' ?>">
                    <i class="fas fa-utensils"></i>
                    Menu Items
                </a>
                <a href="admin_qr_generator.php" class="nav-item">
                    <i class="fas fa-qrcode"></i>
                    QR Generator
                </a>
                <a href="sales_report.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    Sales Report
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <a href="?tab=profile" class="nav-item <?= $current_tab === 'profile' ? 'active' : '' ?>">
                    <i class="fas fa-user-cog"></i>
                    Profile
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="admin_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1 class="content-title">
                <?php
                switch($current_tab) {
                    case 'dashboard':
                        echo 'Dashboard';
                        break;
                    case 'menu-items':
                        echo 'Menu Items';
                        break;
                    case 'qr-generator':
                        echo 'QR Code Generator';
                        break;
                    case 'profile':
                        echo 'Profile Settings';
                        break;
                    default:
                        echo 'Dashboard';
                }
                ?>
            </h1>
            <p class="content-subtitle">
                <?php
                switch($current_tab) {
                    case 'dashboard':
                        echo 'Overview of restaurant operations';
                        break;
                    case 'menu-items':
                        echo 'Manage restaurant menu items';
                        break;
                    case 'qr-generator':
                        echo 'Generate QR codes for table ordering';
                        break;
                    case 'profile':
                        echo 'Manage your account settings';
                        break;
                    default:
                        echo 'Welcome to the admin panel';
                }
                ?>
            </p>
        </div>

        <div class="content-body">
            <!-- Dashboard Tab -->
            <div class="tab-content <?= $current_tab === 'dashboard' ? 'active' : '' ?>">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['total_orders'] ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-number"><?= $stats['pending_orders'] ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card preparing">
                        <div class="stat-number"><?= $stats['preparing_orders'] ?></div>
                        <div class="stat-label">Preparing</div>
                    </div>
                    <div class="stat-card ready">
                        <div class="stat-number"><?= $stats['ready_orders'] ?></div>
                        <div class="stat-label">Ready</div>
                    </div>
                    <div class="stat-card completed">
                        <div class="stat-number"><?= $stats['completed_orders'] ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card today">
                        <div class="stat-number"><?= $stats['today_orders'] ?></div>
                        <div class="stat-label">Today's Orders</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <h3 class="filters-title">Filter Orders</h3>
                    <form class="filters-form" method="GET">
                        <input type="hidden" name="tab" value="dashboard">
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Preparing" <?= $status_filter == 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                                <option value="Ready" <?= $status_filter == 'Ready' ? 'selected' : '' ?>>Ready</option>
                                <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date</label>
                            <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Order Method</label>
                            <select name="method">
                                <option value="">All Methods</option>
                                <option value="walk-in" <?= $method_filter == 'walk-in' ? 'selected' : '' ?>>Walk-in</option>
                                <option value="online" <?= $method_filter == 'online' ? 'selected' : '' ?>>Online</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="filter-btn">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <?php if (isset($orders) && !empty($orders)): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Time</th>
                                <th>Table/Customer</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['id'] ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['order_time'])) ?></td>
                                    <td>
                                        <?php if ($order['order_method'] == 'walk-in'): ?>
                                            Table <?= $order['table_number'] ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="method-badge method-<?= $order['order_method'] ?>">
                                            <?= ucfirst($order['order_method']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="update_order_status.php" style="display: inline-flex; align-items: center;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="new_status" style="padding: 5px; border-radius: 3px; border: 1px solid #bdc3c7;">
                                                <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Preparing" <?= $order['status'] == 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                                                <option value="Ready" <?= $order['status'] == 'Ready' ? 'selected' : '' ?>>Ready</option>
                                                <option value="Completed" <?= $order['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                            <button type="submit" style="background: #27ae60; color: white; padding: 5px 10px; border: none; border-radius: 3px; margin-left: 5px; cursor: pointer;">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="view_order.php?id=<?= $order['id'] ?>" class="action-btn btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?tab=dashboard&page=<?= $page - 1 ?>&status=<?= $status_filter ?>&date=<?= $date_filter ?>&method=<?= $method_filter ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?tab=dashboard&page=<?= $i ?>&status=<?= $status_filter ?>&date=<?= $date_filter ?>&method=<?= $method_filter ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?tab=dashboard&page=<?= $page + 1 ?>&status=<?= $status_filter ?>&date=<?= $date_filter ?>&method=<?= $method_filter ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; background: white; border-radius: 10px;">
                        <i class="fas fa-inbox" style="font-size: 3em; color: #bdc3c7; margin-bottom: 20px;"></i>
                        <p style="color: #7f8c8d; font-size: 1.2em;">No orders found</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Menu Items Tab -->
            <div class="tab-content <?= $current_tab === 'menu-items' ? 'active' : '' ?>">
                <button onclick="openAddItemModal()" class="btn-new">
                    <i class="fas fa-plus"></i> Add New Item
                </button>

                <!-- Menu Item Filters -->
                <div class="filters-section">
                    <h3 class="filters-title">Filter Menu Items</h3>
                    <form class="filters-form" method="GET">
                        <input type="hidden" name="tab" value="menu-items">
                        <div class="filter-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($available_categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= $category_filter == $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Search Item</label>
                            <input type="text" name="search" placeholder="Search by item name..." value="<?= htmlspecialchars($search_filter) ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="filter-btn">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                        <?php if ($category_filter || $search_filter): ?>
                            <div class="filter-group">
                                <a href="?tab=menu-items" class="filter-btn" style="background: #95a5a6; text-decoration: none; display: inline-block;">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (isset($menu_items) && !empty($menu_items)): ?>
                    <table class="menu-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menu_items as $item): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($item['category']) ?></td>
                                    <td>RM <?= number_format($item['price'], 2) ?></td>
                                    <td><?= htmlspecialchars(substr($item['description'] ?? '', 0, 50)) ?><?= strlen($item['description'] ?? '') > 50 ? '...' : '' ?></td>
                                    <td>
                                        <button onclick="editMenuItem(<?= $item['id'] ?>)" class="action-btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteMenuItem(<?= $item['id'] ?>)" class="action-btn btn-delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; background: white; border-radius: 10px;">
                        <i class="fas fa-utensils" style="font-size: 3em; color: #bdc3c7; margin-bottom: 20px;"></i>
                        <p style="color: #7f8c8d; font-size: 1.2em;">No menu items found</p>
                        <?php if ($category_filter || $search_filter): ?>
                            <p style="color: #7f8c8d; margin-top: 10px;">Try adjusting your filters</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- QR Generator Tab -->
            <div class="tab-content <?= $current_tab === 'qr-generator' ? 'active' : '' ?>">
                <?php if (isset($qr_error) && $qr_error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($qr_error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($qr_generated) && $qr_generated): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> QR codes generated successfully!
                    </div>
                <?php endif; ?>

                <!-- QR Configuration -->
                <div class="qr-config-section">
                    <h3 class="filters-title">QR Code Configuration</h3>
                    <form method="POST">
                        <input type="hidden" name="generate_qr" value="1">
                        <div class="filters-form">
                            <div class="filter-group">
                                <label>Number of Tables</label>
                                <input type="number" name="table_count" min="1" max="100" value="<?= $_POST['table_count'] ?? 10 ?>" required>
                            </div>
                            <div class="filter-group">
                                <label>Base URL</label>
                                <input type="url" name="base_url" value="<?= $_POST['base_url'] ?? 'http://localhost/restaurant-order-system/menu.php?table=' ?>" required>
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="filter-btn">
                                    <i class="fas fa-qrcode"></i> Generate QR Codes
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="alert alert-info" style="margin-top: 20px;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Instructions:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Set the number of tables you want QR codes for</li>
                            <li>Update the base URL if your system is hosted online</li>
                            <li>QR codes will link to: [Base URL][Table Number]</li>
                            <li>Generated QR codes will be saved in the 'qr_codes' folder</li>
                        </ul>
                    </div>
                </div>

                <!-- Display Generated QR Codes -->
                <?php if (is_dir("qr_codes/")): ?>
                    <?php 
                    $qr_files = glob("qr_codes/table_*.png");
                    if (!empty($qr_files)): 
                        // Sort files naturally by table number
                        natsort($qr_files);
                    ?>
                        <div class="filters-section">
                            <h3 class="filters-title">Generated QR Codes</h3>
                            <div class="qr-grid">
                                <?php foreach ($qr_files as $qr_file): 
                                    $table_num = preg_replace('/.*table_(\d+)\.png/', '$1', $qr_file);
                                ?>
                                    <div class="qr-card">
                                        <h4>Table <?= $table_num ?></h4>
                                        <img src="<?= $qr_file ?>" alt="QR Code for Table <?= $table_num ?>">
                                        <div>
                                            <a href="<?= $qr_file ?>" class="download-btn" download="table_<?= $table_num ?>_qr.png">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; background: white; border-radius: 10px;">
                            <i class="fas fa-qrcode" style="font-size: 3em; color: #bdc3c7; margin-bottom: 20px;"></i>
                            <p style="color: #7f8c8d; font-size: 1.2em;">No QR codes generated yet</p>
                            <p style="color: #7f8c8d; margin-top: 10px;">Use the form above to generate QR codes for your tables</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Profile Tab -->
            <div class="tab-content <?= $current_tab === 'profile' ? 'active' : '' ?>">
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 20px;">Profile Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?= htmlspecialchars($user_info['username']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" value="<?= htmlspecialchars($user_info['name']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($user_info['email']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="<?= htmlspecialchars($user_info['role']) ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Menu Item Modal -->
<div id="menuItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Add Menu Item</h2>
            <span class="close" onclick="closeModal('menuItemModal')">&times;</span>
        </div>
        <form id="menuItemForm" action="admin_manage_menu.php" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="item_id" id="itemId">
            
            <div class="form-group">
                <label for="item_name">Item Name *</label>
                <input type="text" name="name" id="item_name" required>
            </div>

            <div class="form-group">
                <label for="item_category">Category *</label>
                <select name="category" id="item_category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($available_categories as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="item_price">Price (RM) *</label>
                <input type="number" name="price" id="item_price" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="item_description">Description</label>
                <textarea name="description" id="item_description" rows="3"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Item
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal('menuItemModal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    }

    function openAddItemModal() {
        document.getElementById('modalTitle').textContent = 'Add Menu Item';
        document.getElementById('formAction').value = 'add';
        document.getElementById('menuItemForm').reset();
        document.getElementById('menuItemModal').style.display = 'block';
    }

    function editMenuItem(itemId) {
        document.getElementById('modalTitle').textContent = 'Edit Menu Item';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('itemId').value = itemId;
        
        // Fetch item details and populate form
        fetch(`get_menu_item.php?id=${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('item_name').value = data.item.name;
                    document.getElementById('item_category').value = data.item.category;
                    document.getElementById('item_price').value = data.item.price;
                    document.getElementById('item_description').value = data.item.description || '';
                    
                    document.getElementById('menuItemModal').style.display = 'block';
                } else {
                    alert('Error loading item details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading item details');
            });
    }

    function deleteMenuItem(itemId) {
        if (confirm('Are you sure you want to delete this menu item? This action cannot be undone.')) {
            fetch('admin_manage_menu.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&item_id=${itemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Menu item deleted successfully');
                    location.reload();
                } else {
                    alert('Error deleting item: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting item');
            });
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
</script>

</body>
</html>