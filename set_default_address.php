<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if address ID is provided
if (isset($_GET['id'])) {
    $address_id = intval($_GET['id']);
    
    // Verify address belongs to user
    $check_query = "SELECT id FROM user_addresses WHERE id = ? AND user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ii", $address_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) === 0) {
        $_SESSION['error_message'] = "Invalid address selected.";
        header("Location: myaccount.php");
        exit();
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, set all addresses to non-default
        $reset_query = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
        $reset_stmt = mysqli_prepare($conn, $reset_query);
        mysqli_stmt_bind_param($reset_stmt, "i", $user_id);
        mysqli_stmt_execute($reset_stmt);
        
        // Then set the selected address as default
        $update_query = "UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ii", $address_id, $user_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Error setting default address: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Default address updated successfully!";
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Redirect back to account page
header("Location: myaccount.php");
exit();
?> 