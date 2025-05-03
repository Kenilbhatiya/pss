<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Save current page to redirect after login
    $_SESSION['redirect_after_login'] = 'myaccount.php#wishlist';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if wishlist ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $wishlist_id = intval($_GET['id']);
    
    // Delete the wishlist item
    $query = "DELETE FROM wishlist WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $wishlist_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Set success message
        $_SESSION['success_message'] = "Item removed from wishlist successfully!";
    } else {
        // Set error message
        $_SESSION['error_message'] = "Failed to remove item from wishlist. Please try again.";
    }
} else {
    // Invalid wishlist ID
    $_SESSION['error_message'] = "Invalid wishlist item.";
}

// Redirect back to wishlist page
header("Location: myaccount.php#wishlist");
exit();
?> 