<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once("includes/db_connection.php");

echo "<h2>Creating Missing Tables</h2>";

// Create user_addresses table if it doesn't exist
$check_query = "SHOW TABLES LIKE 'user_addresses'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    echo "<p>Creating user_addresses table...</p>";
    
    $create_table_query = "CREATE TABLE `user_addresses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `address_type` enum('home','work','other') NOT NULL DEFAULT 'home',
        `address_line1` varchar(255) NOT NULL,
        `address_line2` varchar(255) DEFAULT NULL,
        `city` varchar(100) NOT NULL,
        `state` varchar(100) NOT NULL,
        `zip_code` varchar(20) NOT NULL,
        `country` varchar(100) NOT NULL DEFAULT 'India',
        `is_default` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p>user_addresses table created successfully!</p>";
    } else {
        echo "<p>Error creating user_addresses table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>user_addresses table already exists.</p>";
}

// Create orders table if it doesn't exist
$check_query = "SHOW TABLES LIKE 'orders'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    echo "<p>Creating orders table...</p>";
    
    $create_table_query = "CREATE TABLE `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `total_amount` decimal(10,2) NOT NULL,
        `shipping_address` text NOT NULL,
        `billing_address` text NOT NULL,
        `payment_method` varchar(50) NOT NULL,
        `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
        `delivery_date` date DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p>orders table created successfully!</p>";
    } else {
        echo "<p>Error creating orders table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>orders table already exists.</p>";
}

// Create order_items table if it doesn't exist
$check_query = "SHOW TABLES LIKE 'order_items'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    echo "<p>Creating order_items table...</p>";
    
    $create_table_query = "CREATE TABLE `order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
        CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p>order_items table created successfully!</p>";
    } else {
        echo "<p>Error creating order_items table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>order_items table already exists.</p>";
}

echo "<p>All missing tables have been created. <a href='myaccount.php'>Try My Account page now</a></p>";
?> 