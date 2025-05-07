<?php
// Include database connection
include_once("includes/db_connection.php");

// Check if delivery_date column exists but has NULL values
$update_delivery_dates = "UPDATE orders SET delivery_date = DATE_ADD(created_at, INTERVAL 2 DAY) WHERE delivery_date IS NULL";

if (mysqli_query($conn, $update_delivery_dates)) {
    echo "<p>Successfully updated delivery dates for orders that had NULL values.</p>";
    
    // Check how many rows were affected
    $affected_rows = mysqli_affected_rows($conn);
    if ($affected_rows > 0) {
        echo "<p>Updated $affected_rows orders with delivery dates.</p>";
    } else {
        echo "<p>No orders needed updating.</p>";
    }
} else {
    echo "<p>Error updating delivery dates: " . mysqli_error($conn) . "</p>";
}

// Update specific order if order ID is provided
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    // Update specific order
    $update_specific = "UPDATE orders SET delivery_date = DATE_ADD(created_at, INTERVAL 2 DAY) WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_specific);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p>Successfully updated delivery date for Order #$order_id.</p>";
    } else {
        echo "<p>Error updating delivery date for Order #$order_id: " . mysqli_error($conn) . "</p>";
    }
}

// Close connection
mysqli_close($conn);

// Redirect after a few seconds
echo "<p>Redirecting back in 3 seconds...</p>";
$redirect_to = isset($_GET['return']) ? $_GET['return'] : 'myaccount.php';
echo "<script>setTimeout(function(){ window.location.href = '$redirect_to'; }, 3000);</script>";
?> 