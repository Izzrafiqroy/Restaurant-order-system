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

// Handle QR Code Generation
$qr_generated = false;
$qr_error = '';
$qr_deleted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_qr'])) {
    $table_count = intval($_POST['table_count'] ?? 10);
    $base_url = $_POST['base_url'] ?? "http://192.168.0.146/restaurant-order-system/menu.php?table=";
    
    try {
        // Create QR codes directory if it doesn't exist
        $qr_dir = "qr_codes/";
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        
        // FORCE DELETE ALL EXISTING QR CODES FIRST
        $existing_files = glob("qr_codes/table_*.png");
        foreach ($existing_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Check if phpqrcode library exists
        if (!file_exists('phpqrcode/phpqrcode.php')) {
            $qr_error = "PHPQRCode library not found. Please install it in the 'phpqrcode' directory.";
        } else {
            include_once 'phpqrcode/phpqrcode.php';
            
            // Generate QR codes with the proper URL format
            for ($i = 1; $i <= $table_count; $i++) {
                $text = $base_url . $i;
                $filename = $qr_dir . "table_" . $i . ".png";
                
                // Delete the specific file if it exists (double check)
                if (file_exists($filename)) {
                    unlink($filename);
                }
                
                // Generate new QR code
                QRcode::png($text, $filename, 'L', 4, 2);
                
                // Debug: verify the file was created
                if (file_exists($filename)) {
                    error_log("Generated QR for Table $i: " . $text);
                } else {
                    error_log("Failed to generate QR for Table $i");
                }
            }
            
            $qr_generated = true;
        }
    } catch (Exception $e) {
        $qr_error = "Error generating QR codes: " . $e->getMessage();
    }
}

// Handle QR Code Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_qr'])) {
    $table_number = intval($_POST['table_number'] ?? 0);
    
    if ($table_number > 0) {
        $qr_file = "qr_codes/table_" . $table_number . ".png";
        if (file_exists($qr_file)) {
            if (unlink($qr_file)) {
                $qr_deleted = true;
            } else {
                $qr_error = "Failed to delete QR code for Table " . $table_number;
            }
        } else {
            $qr_error = "QR code file not found for Table " . $table_number;
        }
    }
}

// Handle Delete All QR Codes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_qr'])) {
    $qr_files = glob("qr_codes/table_*.png");
    $deleted_count = 0;
    
    foreach ($qr_files as $file) {
        if (unlink($file)) {
            $deleted_count++;
        }
    }
    
    if ($deleted_count > 0) {
        $qr_deleted = true;
        $qr_error = "Deleted " . $deleted_count . " QR code(s) successfully.";
    } else {
        $qr_error = "No QR codes found to delete.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Code Generator - Sup Tulang ZZ</title>
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

        .qr-config-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8em;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .config-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .generate-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .generate-btn:hover {
            background: #219a52;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .qr-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .qr-card:hover {
            transform: translateY(-5px);
        }

        .qr-card h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
            font-size: 1.3em;
        }

        .qr-card img {
            width: 150px;
            height: 150px;
            margin-bottom: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
        }

        .download-btn {
            background: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .download-btn:hover {
            background: #2980b9;
        }

        .instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin-top: 20px;
        }

        .instructions ul {
            margin: 10px 0 0 20px;
        }

        .instructions li {
            margin-bottom: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4em;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #7f8c8d;
            font-size: 1.3em;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #95a5a6;
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

        /* Add this to your existing CSS styles */
        .delete-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.8em;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 8px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        .delete-all-section {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .delete-all-btn {
            background: #e53e3e;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .delete-all-btn:hover {
            background: #c53030;
        }

        .qr-card-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
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

            .config-form {
                grid-template-columns: 1fr;
            }

            .qr-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
                <a href="admin_qr_generator.php" class="nav-item active">
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
            <h1 class="content-title">
                <i class="fas fa-qrcode"></i>
                QR Code Generator
            </h1>
            <p class="content-subtitle">Generate QR codes for table ordering</p>
        </div>

        <div class="content-body">
            <?php if ($qr_error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($qr_error) ?>
                </div>
            <?php endif; ?>

            <?php if ($qr_generated): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    QR codes generated successfully!
                </div>
            <?php endif; ?>

            <!-- QR Configuration -->
            <div class="qr-config-section">
                <h2 class="section-title">Generate QR Codes</h2>
                <form method="POST" class="config-form">
                    <input type="hidden" name="generate_qr" value="1">
                    
                    <div class="form-group">
                        <label for="table_count">Number of Tables</label>
                        <input type="number" 
                               name="table_count" 
                               id="table_count"
                               min="1" 
                               max="100" 
                               value="<?= $_POST['table_count'] ?? 10 ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="base_url">Base URL</label>
                        <input type="url" 
                               name="base_url" 
                               id="base_url"
                               value="<?= $_POST['base_url'] ?? 'http://192.168.0.146/restaurant-order-system/menu.php?table=' ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="generate-btn">
                            <i class="fas fa-qrcode"></i>
                            Generate QR Codes
                        </button>
                    </div>
                </form>
                
            </div>

            <!-- Display Generated QR Codes -->
            <?php if (is_dir("qr_codes/")): ?>
                <?php 
                $qr_files = glob("qr_codes/table_*.png");
                if (!empty($qr_files)): 
                    natsort($qr_files);
                ?>
                    <div class="qr-config-section">
                        <h2 class="section-title">
                            <i class="fas fa-images"></i>
                            Generated QR Codes (<?= count($qr_files) ?> tables)
                        </h2>
                        <div class="qr-grid">
                            <?php foreach ($qr_files as $qr_file): 
                                $table_num = preg_replace('/.*table_(\d+)\.png/', '$1', $qr_file);
                            ?>
                                <div class="qr-card">
                                    <h4>Table <?= $table_num ?></h4>
                                    <img src="<?= $qr_file ?>" alt="QR Code for Table <?= $table_num ?>">
                                    <div class="qr-card-actions">
                                        <a href="<?= $qr_file ?>" class="download-btn" download="table_<?= $table_num ?>_qr.png">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete QR code for Table <?= $table_num ?>?')">
                                            <input type="hidden" name="delete_qr" value="1">
                                            <input type="hidden" name="table_number" value="<?= $table_num ?>">
                                            <button type="submit" class="delete-btn">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Simple Delete All Button at bottom -->
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete ALL QR codes? This action cannot be undone!')">
                                <input type="hidden" name="delete_all_qr" value="1">
                                <button type="submit" class="delete-all-btn">
                                    <i class="fas fa-trash-alt"></i>
                                    Delete All QR Codes
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-qrcode"></i>
                        <h3>No QR codes generated yet</h3>
                        <p>Use the form above to generate QR codes for your tables</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    }
</script>

</body>
</html>