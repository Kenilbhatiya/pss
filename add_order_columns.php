<?php
// Include database connection
include_once("includes/db_connection.php");

// Check if username column exists in orders table
$check_username = "SHOW COLUMNS FROM orders LIKE 'username'";
$username_exists = mysqli_query($conn, $check_username);

// Check if product_name column exists in orders table
$check_product = "SHOW COLUMNS FROM orders LIKE 'product_name'";
$product_exists = mysqli_query($conn, $check_product);

// Add username column if it doesn't exist
if ($username_exists && mysqli_num_rows($username_exists) == 0) {
    $add_username = "ALTER TABLE orders ADD COLUMN username VARCHAR(100) AFTER user_id";
    
    if (mysqli_query($conn, $add_username)) {
        echo "<p>Successfully added username column to orders table.</p>";
        
        // Update existing orders with usernames
        $update_usernames = "UPDATE orders o 
                           JOIN users u ON o.user_id = u.id 
                           SET o.username = u.username";
        
        if (mysqli_query($conn, $update_usernames)) {
            echo "<p>Successfully updated usernames for existing orders.</p>";
        } else {
            echo "<p>Error updating usernames: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Error adding username column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>The username column already exists in the orders table or there was an error checking columns.</p>";
}

// Add product_name column if it doesn't exist
if ($product_exists && mysqli_num_rows($product_exists) == 0) {
    $add_product = "ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) AFTER username";
    
    if (mysqli_query($conn, $add_product)) {
        echo "<p>Successfully added product_name column to orders table.</p>";
        
        // Update existing orders with product names (first product in order)
        $update_products = "UPDATE orders o 
                          SET o.product_name = (
                              SELECT p.name 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = o.id 
                              LIMIT 1
                          )";
        
        if (mysqli_query($conn, $update_products)) {
            echo "<p>Successfully updated product names for existing orders.</p>";
        } else {
            echo "<p>Error updating product names: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Error adding product_name column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>The product_name column already exists in the orders table or there was an error checking columns.</p>";
}

// Close connection
mysqli_close($conn);

// Redirect back to admin page after 3 seconds
echo "<p>Redirecting back to admin page in 3 seconds...</p>";
echo "<script>setTimeout(function(){ window.location.href = 'admin/index.php'; }, 3000);</script>";
?> 