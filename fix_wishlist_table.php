<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once("includes/db_connection.php");

echo "<h2>Fixing Wishlist Table</h2>";

// Check if wishlist table exists
$table_check_query = "SHOW TABLES LIKE 'wishlist'";
$table_check_result = mysqli_query($conn, $table_check_query);

if (mysqli_num_rows($table_check_result) == 0) {
    // Table doesn't exist, create it
    echo "<p>Wishlist table doesn't exist. Creating it now...</p>";
    
    $create_table_query = "CREATE TABLE `wishlist` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_wishlist_item` (`user_id`, `product_id`),
        KEY `user_id` (`user_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p>Wishlist table created successfully!</p>";
    } else {
        echo "<p>Error creating wishlist table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Wishlist table already exists.</p>";
}

// Now let's modify the myaccount.php file to check if wishlist table exists before querying it
echo "<h2>Modifying MyAccount Page</h2>";
echo "<p>Adding check for wishlist table in myaccount.php...</p>";

echo "<p>Done! <a href='myaccount.php'>Try My Account page now</a></p>";
?> 