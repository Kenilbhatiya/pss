<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Save current page to redirect after login
    $_SESSION['redirect_after_login'] = 'myaccount.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid order ID.";
    header("Location: myaccount.php#orders");
    exit();
}

$order_id = intval($_GET['id']);

// Check if order exists and belongs to the user
$check_query = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) == 0) {
    $_SESSION['error_message'] = "Order not found or does not belong to you.";
    header("Location: myaccount.php#orders");
    exit();
}

// Bind the result
mysqli_stmt_bind_result($check_stmt, $order_status);
mysqli_stmt_fetch($check_stmt);

// Check if order can be cancelled (only pending orders can be cancelled)
if ($order_status != 'pending') {
    $_SESSION['error_message'] = "Only pending orders can be cancelled.";
    header("Location: myaccount.php#orders");
    exit();
}

// Cancel the order
$update_query = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, "ii", $order_id, $user_id);

if (mysqli_stmt_execute($update_stmt)) {
    $_SESSION['success_message'] = "Order #$order_id has been cancelled successfully.";
} else {
    $_SESSION['error_message'] = "Failed to cancel the order. Please try again.";
}

// Redirect back to orders page
header("Location: myaccount.php#orders");
exit();
?> 