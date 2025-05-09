<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once("includes/db_connection.php");

// Check if username column exists in orders table
$check_username_column = "SHOW COLUMNS FROM orders LIKE 'username'";
$username_column_exists = mysqli_query($conn, $check_username_column);

// If the username column doesn't exist, create it
if (mysqli_num_rows($username_column_exists) == 0) {
    $add_username_column = "ALTER TABLE orders ADD COLUMN username VARCHAR(100) AFTER user_id";
    
    if (mysqli_query($conn, $add_username_column)) {
        echo "<p>Successfully added username column to orders table.</p>";
    } else {
        echo "<p>Error adding username column: " . mysqli_error($conn) . "</p>";
        exit();
    }
} else {
    echo "<p>The username column already exists in the orders table.</p>";
}

// Check if product_name column exists in the orders table
$check_product_column = "SHOW COLUMNS FROM orders LIKE 'product_name'";
$product_column_exists = mysqli_query($conn, $check_product_column);

// If the product_name column doesn't exist, create it
if (mysqli_num_rows($product_column_exists) == 0) {
    $add_product_column = "ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) AFTER username";
    
    if (mysqli_query($conn, $add_product_column)) {
        echo "<p>Successfully added product_name column to orders table.</p>";
    } else {
        echo "<p>Error adding product_name column: " . mysqli_error($conn) . "</p>";
        exit();
    }
} else {
    echo "<p>The product_name column already exists in the orders table.</p>";
}

// Update usernames in orders table
$update_usernames_query = "UPDATE orders o 
                          JOIN users u ON o.user_id = u.id 
                          SET o.username = u.username 
                          WHERE o.username IS NULL OR o.username = ''";

if (mysqli_query($conn, $update_usernames_query)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "<p>Successfully updated usernames for {$affected_rows} orders.</p>";
} else {
    echo "<p>Error updating usernames: " . mysqli_error($conn) . "</p>";
}

// Update product names in orders table
// For each order, get the first product in the order
$orders_query = "SELECT DISTINCT o.id, p.name as product_name 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE (o.product_name IS NULL OR o.product_name = '')";

$orders_result = mysqli_query($conn, $orders_query);

if ($orders_result) {
    $updated_count = 0;
    
    while ($row = mysqli_fetch_assoc($orders_result)) {
        $update_product_query = "UPDATE orders 
                                SET product_name = ? 
                                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $update_product_query);
        mysqli_stmt_bind_param($stmt, "si", $row['product_name'], $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $updated_count++;
        }
    }
    
    echo "<p>Successfully updated product names for {$updated_count} orders.</p>";
} else {
    echo "<p>Error fetching orders to update: " . mysqli_error($conn) . "</p>";
}

echo "<p>Order details update completed. <a href='admin/index.php'>Go to Admin Dashboard</a></p>";

// Close connection
mysqli_close($conn);
?> 