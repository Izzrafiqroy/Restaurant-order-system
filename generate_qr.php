<?php
// Include PHP QRCode library
include 'phpqrcode/phpqrcode.php';

// Base URL (change if hosted online)
$base_url = "http://localhost/restaurant-order-system/menu.php?table=";

// Number of tables you want QR codes for
$total_tables = 10;

// Output folder
$output_dir = "qr_codes/";

// Generate QR Codes
ob_start(); // Start buffering output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Table QR Codes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        p.center {
            text-align: center;
            font-size: 1.1em;
            color: #555;
            margin-bottom: 30px;
        }

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 25px;
            justify-items: center;
        }

        .qr-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .qr-card:hover {
            transform: translateY(-5px);
        }

        .qr-card img {
            width: 160px;
            height: auto;
            margin-bottom: 10px;
        }

        .qr-card strong {
            display: block;
            margin-bottom: 10px;
            color: #333;
        }

        .download-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            font-size: 0.9em;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .download-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h1>🧾 Generate Table QR Codes</h1>
    <p class="center">Scan these QR codes to view the menu for each table.</p>

    <div class="qr-grid">
        <?php for ($i = 1; $i <= $total_tables; $i++): 
            $text = $base_url . $i;
            $filename = $output_dir . "table_" . $i . ".png";

            // Generate QR code
            QRcode::png($text, $filename, 'L', 4, 2);
        ?>
            <div class="qr-card">
                <strong>Table <?= $i ?></strong>
                <img src="<?= $filename ?>" alt="QR Code for Table <?= $i ?>">
                <a href="<?= $filename ?>" class="download-btn" download>Download QR</a>
            </div>
        <?php endfor; ?>
    </div>

</body>
</html>
<?php
ob_end_flush(); // Send buffered output
?>