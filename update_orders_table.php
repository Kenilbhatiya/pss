<?php
// Include database connection
include_once("includes/db_connection.php");

// Add delivery_date column to orders table if it doesn't exist
$check_column = "SHOW COLUMNS FROM orders LIKE 'delivery_date'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    // Column doesn't exist, add it
    $add_column = "ALTER TABLE orders ADD COLUMN delivery_date DATE AFTER created_at";
    
    if (mysqli_query($conn, $add_column)) {
        echo "<p>Successfully added delivery_date column to orders table.</p>";
        
        // Update existing orders to have delivery_date as created_at + 2 days
        $update_delivery_dates = "UPDATE orders SET delivery_date = DATE_ADD(created_at, INTERVAL 2 DAY)";
        
        if (mysqli_query($conn, $update_delivery_dates)) {
            echo "<p>Successfully updated delivery dates for existing orders.</p>";
        } else {
            echo "<p>Error updating delivery dates: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Error adding delivery_date column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>The delivery_date column already exists in the orders table.</p>";
}

// Close connection
mysqli_close($conn);
?> 