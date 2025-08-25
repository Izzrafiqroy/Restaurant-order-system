<?php
// Database configuration
$host = 'localhost';       // Host name (usually localhost)
$user = 'root';            // MySQL username (default in XAMPP is 'root')
$pass = '';                // MySQL password (default in XAMPP is empty)
$db = 'restaurant_db';     // Your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>