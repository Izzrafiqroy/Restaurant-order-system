<?php
session_start();
include 'includes/db.php';

// Simple authentication check
$admin_logged_in = $_SESSION['admin_logged_in'] ?? false;
if (!$admin_logged_in) {
    if (!isset($_SESSION['simple_admin'])) {
        header("Location: admin_login.php");
        exit;
    }
}

// Default user info
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
    
    if (!$auth->isAuthenticated()) {
        header("Location: admin_login.php");
        exit;
    }
    
    $user_info = $auth->getUserInfo();
}

// Get report parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today
$report_type = $_GET['report_type'] ?? 'daily';

// Build query for sales data
$where_clause = "WHERE DATE(order_time) BETWEEN ? AND ? AND status = 'Completed'";
$params = [$start_date, $end_date];

// Get total sales summary
$summary_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(oi.price) as total_revenue,
        AVG(oi.price) as avg_order_value,
        COUNT(DISTINCT o.table_number) as tables_served
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    $where_clause
";
$summary_stmt = $conn->prepare($summary_query);
$summary_stmt->bind_param("ss", $start_date, $end_date);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Get daily sales breakdown
$daily_query = "
    SELECT 
        DATE(o.order_time) as sale_date,
        COUNT(DISTINCT o.id) as orders_count,
        SUM(oi.price) as daily_revenue,
        COUNT(DISTINCT o.table_number) as tables_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY DATE(o.order_time)
    ORDER BY sale_date ASC
";
$daily_stmt = $conn->prepare($daily_query);
$daily_stmt->bind_param("ss", $start_date, $end_date);
$daily_stmt->execute();
$daily_sales = $daily_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get top selling items
$items_query = "
    SELECT 
        oi.item_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.price) as total_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY oi.item_name
    ORDER BY total_quantity DESC
    LIMIT 10
";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("ss", $start_date, $end_date);
$items_stmt->execute();
$top_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get hourly sales pattern
$hourly_query = "
    SELECT 
        HOUR(o.order_time) as hour,
        COUNT(DISTINCT o.id) as orders_count,
        SUM(oi.price) as hourly_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY HOUR(o.order_time)
    ORDER BY hour
";
$hourly_stmt = $conn->prepare($hourly_query);
$hourly_stmt->bind_param("ss", $start_date, $end_date);
$hourly_stmt->execute();
$hourly_sales = $hourly_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get method breakdown
$method_query = "
    SELECT 
        o.order_method,
        COUNT(DISTINCT o.id) as orders_count,
        SUM(oi.price) as method_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY o.order_method
";
$method_stmt = $conn->prepare($method_query);
$method_stmt->bind_param("ss", $start_date, $end_date);
$method_stmt->execute();
$method_breakdown = $method_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get category breakdown
$category_query = "
    SELECT 
        CASE 
            WHEN oi.item_name LIKE '%Sup Tulang%' THEN 'Soup'
            WHEN oi.item_name LIKE '%Nasi%' THEN 'Rice'
            WHEN oi.item_name LIKE '%Mie%' OR oi.item_name LIKE '%Mee%' THEN 'Noodles'
            WHEN oi.item_name LIKE '%Drink%' OR oi.item_name LIKE '%Teh%' OR oi.item_name LIKE '%Kopi%' THEN 'Beverages'
            ELSE 'Others'
        END as category,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.price) as total_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY category
    ORDER BY total_revenue DESC
";
$category_stmt = $conn->prepare($category_query);
$category_stmt->bind_param("ss", $start_date, $end_date);
$category_stmt->execute();
$category_breakdown = $category_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report - Sup Tulang ZZ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Crimson+Text:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

        .report-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .company-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .report-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8em;
            color: #2c3e50;
            margin: 20px 0 10px;
        }

        .report-period {
            color: #7f8c8d;
            font-size: 1.1em;
            margin-bottom: 10px;
        }

        .report-generated {
            color: #95a5a6;
            font-size: 0.9em;
        }

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
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

        .print-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .print-btn:hover {
            background: #219a52;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
        }

        .summary-card.revenue { border-left-color: #27ae60; }
        .summary-card.orders { border-left-color: #f39c12; }
        .summary-card.average { border-left-color: #9b59b6; }
        .summary-card.tables { border-left-color: #e74c3c; }

        .summary-number {
            font-size: 2.2em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .summary-label {
            font-size: 1em;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .report-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5em;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .report-table th,
        .report-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .report-table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9em;
        }

        .report-table tr:hover {
            background: #f8f9fa;
        }

        .report-table .number {
            text-align: right;
            font-weight: 600;
        }

        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 1.2em;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
            font-family: 'Playfair Display', serif;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .chart-wrapper.bar {
            height: 400px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .filters-form {
                grid-template-columns: 1fr;
            }

            .chart-container {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .sidebar,
            .menu-toggle,
            .filters-section,
            .print-btn {
                display: none !important;
            }

            .main-content {
                margin-left: 0;
            }

            .content-body {
                padding: 0;
            }

            .report-section {
                box-shadow: none;
                border: 1px solid #ddd;
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
                <a href="admin_dashboard.php?tab=dashboard" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Dashboard
                </a>
                <a href="admin_dashboard.php?tab=menu-items" class="nav-item">
                    <i class="fas fa-utensils"></i>
                    Menu Items
                </a>
                <a href="admin_qr_generator.php" class="nav-item">
                    <i class="fas fa-qrcode"></i>
                    QR Generator
                </a>
                <a href="sales_report.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i>
                    Sales Report
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <a href="admin_dashboard.php?tab=profile" class="nav-item">
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
            <h1 class="content-title">Sales Report</h1>
            <p class="content-subtitle">Comprehensive sales analysis and revenue insights</p>
        </div>

        <div class="content-body">
            <!-- Report Header -->
            <div class="report-header">
                <div class="company-logo">
                    <i class="fas fa-utensils"></i> Sup Tulang ZZ
                </div>
                <div style="color: #7f8c8d; margin-bottom: 20px;">
                    Authentic Malaysian Flavors Since 1995
                </div>
                <h2 class="report-title">Sales Report</h2>
                <div class="report-period">
                    Period: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>
                </div>
                <div class="report-generated">
                    Generated on: <?= date('d M Y, H:i') ?> by <?= htmlspecialchars($user_info['name']) ?>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <form class="filters-form" method="GET">
                    <div class="filter-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                    </div>
                    <div class="filter-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                    </div>
                    <div class="filter-group">
                        <label>Report Type</label>
                        <select name="report_type">
                            <option value="daily" <?= $report_type == 'daily' ? 'selected' : '' ?>>Daily Breakdown</option>
                            <option value="summary" <?= $report_type == 'summary' ? 'selected' : '' ?>>Summary Only</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-chart-line"></i> Generate Report
                        </button>
                    </div>
                    <div class="filter-group">
                        <button type="button" class="print-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="summary-grid">
                <div class="summary-card revenue">
                    <div class="summary-number">RM <?= number_format($summary['total_revenue'] ?? 0, 2) ?></div>
                    <div class="summary-label">Total Revenue</div>
                </div>
                <div class="summary-card orders">
                    <div class="summary-number"><?= $summary['total_orders'] ?? 0 ?></div>
                    <div class="summary-label">Total Orders</div>
                </div>
                <div class="summary-card average">
                    <div class="summary-number">RM <?= number_format($summary['avg_order_value'] ?? 0, 2) ?></div>
                    <div class="summary-label">Average Order</div>
                </div>
                <div class="summary-card tables">
                    <div class="summary-number"><?= $summary['tables_served'] ?? 0 ?></div>
                    <div class="summary-label">Tables Served</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="chart-container">
                <!-- Order Methods Pie Chart -->
                <?php if (!empty($method_breakdown)): ?>
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i> Order Methods Distribution
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="methodPieChart"></canvas>
                    </div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th class="number">Orders</th>
                                <th class="number">Revenue</th>
                                <th class="number">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_orders = array_sum(array_column($method_breakdown, 'orders_count'));
                            foreach ($method_breakdown as $method): 
                                $percentage = $total_orders > 0 ? ($method['orders_count'] / $total_orders) * 100 : 0;
                            ?>
                            <tr>
                                <td style="text-transform: capitalize;"><?= htmlspecialchars($method['order_method']) ?></td>
                                <td class="number"><?= $method['orders_count'] ?></td>
                                <td class="number">RM <?= number_format($method['method_revenue'], 2) ?></td>
                                <td class="number"><?= number_format($percentage, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Category Breakdown Donut Chart -->
                <?php if (!empty($category_breakdown)): ?>
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-donut"></i> Sales by Category
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="categoryDonutChart"></canvas>
                    </div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="number">Quantity</th>
                                <th class="number">Revenue</th>
                                <th class="number">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_revenue = array_sum(array_column($category_breakdown, 'total_revenue'));
                            foreach ($category_breakdown as $category): 
                                $percentage = $total_revenue > 0 ? ($category['total_revenue'] / $total_revenue) * 100 : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($category['category']) ?></td>
                                <td class="number"><?= $category['total_quantity'] ?></td>
                                <td class="number">RM <?= number_format($category['total_revenue'], 2) ?></td>
                                <td class="number"><?= number_format($percentage, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Daily Sales Bar Chart -->
            <?php if (!empty($daily_sales)): ?>
            <div class="report-section">
                <h3 class="section-title">
                    <i class="fas fa-chart-bar"></i> Daily Revenue Trend
                </h3>
                <div class="chart-wrapper bar">
                    <canvas id="dailyRevenueChart"></canvas>
                </div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Tables Served</th>
                            <th class="number">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_sales as $day): ?>
                        <tr>
                            <td><?= date('d M Y (l)', strtotime($day['sale_date'])) ?></td>
                            <td><?= $day['orders_count'] ?></td>
                            <td><?= $day['tables_count'] ?></td>
                            <td class="number">RM <?= number_format($day['daily_revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Top Selling Items Horizontal Bar Chart -->
            <?php if (!empty($top_items)): ?>
            <div class="report-section">
                <h3 class="section-title">
                    <i class="fas fa-trophy"></i> Top Selling Items
                </h3>
                <div class="chart-wrapper bar">
                    <canvas id="topItemsChart"></canvas>
                </div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Item</th>
                            <th class="number">Quantity</th>
                            <th class="number">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($top_items, 0, 10) as $index => $item): ?>
                        <tr>
                            <td>
                                <span style="color: #f39c12; font-weight: bold;">#<?= $index + 1 ?></span>
                            </td>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td class="number"><?= $item['total_quantity'] ?></td>
                            <td class="number">RM <?= number_format($item['total_revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Hourly Sales Pattern Line Chart -->
            <?php if (!empty($hourly_sales)): ?>
            <div class="report-section">
                <h3 class="section-title">
                    <i class="fas fa-clock"></i> Hourly Sales Pattern
                </h3>
                <div class="chart-wrapper">
                    <canvas id="hourlySalesChart"></canvas>
                </div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Hour</th>
                            <th class="number">Orders</th>
                            <th class="number">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hourly_sales as $hour): ?>
                        <tr>
                            <td><?= sprintf('%02d:00 - %02d:59', $hour['hour'], $hour['hour']) ?></td>
                            <td class="number"><?= $hour['orders_count'] ?></td>
                            <td class="number">RM <?= number_format($hour['hourly_revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Report Footer -->
            <div class="report-section" style="text-align: center; padding: 20px;">
                <div style="color: #7f8c8d; font-style: italic;">
                    This report contains confidential business information of Sup Tulang ZZ.<br>
                    Generated automatically by the Restaurant Management System.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    }

    // Chart.js Color Palette
    const chartColors = {
        primary: '#3498db',
        success: '#27ae60',
        warning: '#f39c12',
        danger: '#e74c3c',
        info: '#17a2b8',
        purple: '#9b59b6',
        orange: '#fd7e14',
        teal: '#20c997',
        pink: '#e83e8c',
        indigo: '#6610f2'
    };

    const colorPalette = [
        chartColors.primary,
        chartColors.success,
        chartColors.warning,
        chartColors.danger,
        chartColors.purple,
        chartColors.orange,
        chartColors.teal,
        chartColors.pink,
        chartColors.indigo,
        chartColors.info
    ];

    // Order Methods Pie Chart
    <?php if (!empty($method_breakdown)): ?>
    const methodCtx = document.getElementById('methodPieChart').getContext('2d');
    new Chart(methodCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php foreach ($method_breakdown as $method): ?>
                '<?= ucfirst($method['order_method']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($method_breakdown as $method): ?>
                    <?= $method['orders_count'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: colorPalette.slice(0, <?= count($method_breakdown) ?>),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return context.label + ': ' + context.raw + ' orders (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Category Breakdown Donut Chart
    <?php if (!empty($category_breakdown)): ?>
    const categoryCtx = document.getElementById('categoryDonutChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach ($category_breakdown as $category): ?>
                '<?= addslashes($category['category']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($category_breakdown as $category): ?>
                    <?= $category['total_revenue'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: colorPalette.slice(0, <?= count($category_breakdown) ?>),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return context.label + ': RM ' + context.raw.toFixed(2) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Daily Revenue Bar Chart
    <?php if (!empty($daily_sales)): ?>
    const dailyCtx = document.getElementById('dailyRevenueChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($daily_sales as $day): ?>
                '<?= date('M j', strtotime($day['sale_date'])) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Daily Revenue (RM)',
                data: [
                    <?php foreach ($daily_sales as $day): ?>
                    <?= $day['daily_revenue'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: chartColors.primary,
                borderColor: chartColors.primary,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toFixed(2);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: RM ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Top Items Horizontal Bar Chart
    <?php if (!empty($top_items)): ?>
    const topItemsCtx = document.getElementById('topItemsChart').getContext('2d');
    new Chart(topItemsCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach (array_slice($top_items, 0, 8) as $item): ?>
                '<?= addslashes(substr($item['item_name'], 0, 30)) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Quantity Sold',
                data: [
                    <?php foreach (array_slice($top_items, 0, 8) as $item): ?>
                    <?= $item['total_quantity'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: chartColors.success,
                borderColor: chartColors.success,
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Quantity: ' + context.parsed.x;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Hourly Sales Line Chart
    <?php if (!empty($hourly_sales)): ?>
    const hourlyCtx = document.getElementById('hourlySalesChart').getContext('2d');
    
    // Create full 24-hour array
    const hourlyData = new Array(24).fill(0);
    const hourlyRevenue = new Array(24).fill(0);
    
    <?php foreach ($hourly_sales as $hour): ?>
    hourlyData[<?= $hour['hour'] ?>] = <?= $hour['orders_count'] ?>;
    hourlyRevenue[<?= $hour['hour'] ?>] = <?= $hour['hourly_revenue'] ?>;
    <?php endforeach; ?>
    
    new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: [
                '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
                '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
                '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
                '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
            ],
            datasets: [{
                label: 'Orders',
                data: hourlyData,
                borderColor: chartColors.primary,
                backgroundColor: chartColors.primary + '20',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Revenue (RM)',
                data: hourlyRevenue,
                borderColor: chartColors.success,
                backgroundColor: chartColors.success + '20',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Time of Day'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Number of Orders'
                    },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue (RM)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toFixed(0);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Orders: ' + context.raw;
                            } else {
                                return 'Revenue: RM ' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
</script>

</body>
</html>