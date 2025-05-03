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

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['address_id'])) {
    // Get form data
    $address_id = intval($_POST['address_id']);
    $address_line1 = trim($_POST['address_line1']);
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $country = trim($_POST['country'] ?? 'India');
    $address_type = $_POST['address_type'] ?? 'home';
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Basic validation
    if (empty($address_line1) || empty($city) || empty($state) || empty($zip_code)) {
        $_SESSION['error_message'] = "Required fields are missing.";
        header("Location: myaccount.php");
        exit();
    }
    
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
        // If setting as default, first update all addresses to non-default
        if ($is_default) {
            $update_default_query = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
            $update_default_stmt = mysqli_prepare($conn, $update_default_query);
            mysqli_stmt_bind_param($update_default_stmt, "i", $user_id);
            mysqli_stmt_execute($update_default_stmt);
        }
        
        // Update address
        $update_query = "UPDATE user_addresses SET 
                        address_line1 = ?, 
                        address_line2 = ?, 
                        city = ?, 
                        state = ?, 
                        zip_code = ?, 
                        country = ?, 
                        address_type = ?, 
                        is_default = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssssssiii", 
                             $address_line1, $address_line2, $city, $state, $zip_code, 
                             $country, $address_type, $is_default, $address_id, $user_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Error updating address: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Address updated successfully!";
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Redirect back to account page
    header("Location: myaccount.php");
    exit();
} else {
    // If not POST request or address_id not set, redirect to account page
    header("Location: myaccount.php");
    exit();
}
?> 