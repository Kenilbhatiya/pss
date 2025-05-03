<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Save current page to redirect after login
    $_SESSION['redirect_after_login'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'shop.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id']) && is_numeric($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    // Redirect to self with GET parameters
    header("Location: add_to_wishlist.php?id=" . $product_id . 
        (isset($_POST['redirect']) ? "&redirect=" . $_POST['redirect'] : ""));
    exit();
}

// Process GET request
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Check if product exists
    $check_query = "SELECT id FROM products WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $product_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        // Check if product is already in wishlist
        $wishlist_query = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
        $wishlist_stmt = mysqli_prepare($conn, $wishlist_query);
        mysqli_stmt_bind_param($wishlist_stmt, "ii", $user_id, $product_id);
        mysqli_stmt_execute($wishlist_stmt);
        mysqli_stmt_store_result($wishlist_stmt);
        
        if (mysqli_stmt_num_rows($wishlist_stmt) > 0) {
            // Product already in wishlist, no need to add again
            $_SESSION['info_message'] = "This product is already in your wishlist.";
        } else {
            // Add product to wishlist
            $insert_query = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $product_id);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $_SESSION['success_message'] = "Product added to your wishlist!";
            } else {
                $_SESSION['error_message'] = "Failed to add to wishlist. Please try again.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Product not found.";
    }
}

// Determine redirect URL
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

switch ($redirect) {
    case 'product':
        header("Location: product.php?id=" . $product_id);
        break;
    case 'wishlist':
        header("Location: myaccount.php#wishlist");
        break;
    case 'cart':
        header("Location: cart.php");
        break;
    default:
        header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'shop.php'));
        break;
}
exit();
?> 