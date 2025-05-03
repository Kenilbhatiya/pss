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
    
    // First check if address is default
    $check_default_query = "SELECT is_default FROM user_addresses WHERE id = ? AND user_id = ?";
    $check_default_stmt = mysqli_prepare($conn, $check_default_query);
    mysqli_stmt_bind_param($check_default_stmt, "ii", $address_id, $user_id);
    mysqli_stmt_execute($check_default_stmt);
    $check_default_result = mysqli_stmt_get_result($check_default_stmt);
    
    if (mysqli_num_rows($check_default_result) === 0) {
        $_SESSION['error_message'] = "Invalid address selected.";
        header("Location: myaccount.php");
        exit();
    }
    
    $address = mysqli_fetch_assoc($check_default_result);
    $is_default = $address['is_default'];
    
    // Get count of all user's addresses
    $count_query = "SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $user_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $address_count = $count_row['count'];
    
    // If trying to delete the only address or the default address when there's only one left
    if ($address_count <= 1) {
        $_SESSION['error_message'] = "You cannot delete your only address. Please add another address first.";
        header("Location: myaccount.php");
        exit();
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete the address
        $delete_query = "DELETE FROM user_addresses WHERE id = ? AND user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $address_id, $user_id);
        
        if (!mysqli_stmt_execute($delete_stmt)) {
            throw new Exception("Error deleting address: " . mysqli_error($conn));
        }
        
        // If deleted address was default, set a new default
        if ($is_default) {
            // Get the most recently added address
            $new_default_query = "SELECT id FROM user_addresses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
            $new_default_stmt = mysqli_prepare($conn, $new_default_query);
            mysqli_stmt_bind_param($new_default_stmt, "i", $user_id);
            mysqli_stmt_execute($new_default_stmt);
            $new_default_result = mysqli_stmt_get_result($new_default_stmt);
            
            if ($new_default_row = mysqli_fetch_assoc($new_default_result)) {
                $new_default_id = $new_default_row['id'];
                
                // Set as default
                $update_query = "UPDATE user_addresses SET is_default = 1 WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "i", $new_default_id);
                mysqli_stmt_execute($update_stmt);
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Address deleted successfully!";
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