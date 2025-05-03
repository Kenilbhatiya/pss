<?php
// Database connection parameters
$host = "localhost";
$db_username = "root";      // Default XAMPP username
$db_password = "";          // Default XAMPP password
$db_name = "plant_shop";    // Your database name

// Create connection
$conn = mysqli_connect($host, $db_username, $db_password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to ensure proper handling of unicode characters
mysqli_set_charset($conn, "utf8mb4");
?> 