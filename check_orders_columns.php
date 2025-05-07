<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once("includes/db_connection.php");

echo "<h2>Checking Orders Table Columns</h2>";

// Check if delivery_date column exists in orders table
$column_check_query = "SHOW COLUMNS FROM orders LIKE 'delivery_date'";
$column_check_result = mysqli_query($conn, $column_check_query);

if (mysqli_num_rows($column_check_result) == 0) {
    echo "<p>delivery_date column doesn't exist in orders table. Adding it now...</p>";
    
    $add_column_query = "ALTER TABLE orders ADD COLUMN delivery_date date DEFAULT NULL AFTER status";
    
    if (mysqli_query($conn, $add_column_query)) {
        echo "<p>delivery_date column added successfully!</p>";
    } else {
        echo "<p>Error adding delivery_date column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>delivery_date column already exists in orders table.</p>";
}

// Now create a sample order without the delivery_date column
echo "<h2>Creating Sample Order</h2>";

// Check if we have any orders for our test user
$orders_check_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = 1";
$orders_check_result = mysqli_query($conn, $orders_check_query);
$orders_count = 0;

if ($orders_check_result) {
    $row = mysqli_fetch_assoc($orders_check_result);
    $orders_count = $row['count'];
}

if ($orders_count == 0) {
    echo "<p>No orders found for test user. Creating a sample order...</p>";
    
    // Create a sample order
    $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, payment_method, status) 
                   VALUES (1, 74.97, '123 Main St, Anytown, CA 12345', '123 Main St, Anytown, CA 12345', 'Credit Card', 'delivered')";
    
    if (mysqli_query($conn, $order_query)) {
        $order_id = mysqli_insert_id($conn);
        
        // Get product IDs
        $products_query = "SELECT id, price FROM products LIMIT 3";
        $products_result = mysqli_query($conn, $products_query);
        
        if ($products_result && mysqli_num_rows($products_result) > 0) {
            $items_added = 0;
            
            while ($product = mysqli_fetch_assoc($products_result)) {
                $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)";
                $stmt = mysqli_prepare($conn, $order_item_query);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "iid", $order_id, $product['id'], $product['price']);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $items_added++;
                    }
                }
            }
            
            echo "<p>Added $items_added items to the sample order.</p>";
        } else {
            echo "<p>No products found to add to the order.</p>";
        }
        
        echo "<p>Created sample order #$order_id.</p>";
    } else {
        echo "<p>Error creating sample order: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Found $orders_count orders for test user.</p>";
}

echo "<p>Done! <a href='myaccount.php'>Try My Account page now</a></p>";
?> 