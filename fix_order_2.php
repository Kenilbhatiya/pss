<?php
// Include database connection
include_once("includes/db_connection.php");

// Fix order #2 specifically
$order_id = 2;
$update_query = "UPDATE orders SET delivery_date = DATE_ADD(created_at, INTERVAL 2 DAY) WHERE id = $order_id";

if (mysqli_query($conn, $update_query)) {
    echo "Successfully updated delivery date for Order #$order_id.";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?> 