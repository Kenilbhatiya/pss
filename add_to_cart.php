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
$success_message = "";
$error_message = "";

// Process add to cart
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    // Get the product ID
    $product_id = intval($_GET['id']);
    
    // Default quantity is 1 if not specified
    $quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;
    
    // Check if product exists and has stock
    $check_query = "SELECT id, name, price, stock_quantity as quantity FROM products WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $product_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $product = mysqli_fetch_assoc($check_result);
        
        // Check if product is in stock
        if ($product['quantity'] <= 0) {
            $_SESSION['error_message'] = "Sorry, this product is currently out of stock.";
            header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'shop.php'));
            exit();
        }
        
        // Limit quantity to available stock
        if ($quantity > $product['quantity']) {
            $quantity = $product['quantity'];
            $_SESSION['warning_message'] = "Quantity adjusted to available stock.";
        }
        
        // Check if product is already in cart
        $cart_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $cart_stmt = mysqli_prepare($conn, $cart_query);
        mysqli_stmt_bind_param($cart_stmt, "ii", $user_id, $product_id);
        mysqli_stmt_execute($cart_stmt);
        $cart_result = mysqli_stmt_get_result($cart_stmt);
        
        if ($cart_result && mysqli_num_rows($cart_result) > 0) {
            // Product already in cart, update quantity
            $cart_item = mysqli_fetch_assoc($cart_result);
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            // Ensure we don't exceed available stock
            if ($new_quantity > $product['quantity']) {
                $new_quantity = $product['quantity'];
                $_SESSION['warning_message'] = "Quantity adjusted to available stock.";
            }
            
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $cart_item['id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['success_message'] = "Cart updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update cart. Please try again.";
            }
        } else {
            // Add new item to cart
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "iii", $user_id, $product_id, $quantity);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $_SESSION['success_message'] = $product['name'] . " added to your cart!";
            } else {
                $_SESSION['error_message'] = "Failed to add item to cart. Please try again.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Product not found.";
    }
} else {
    $_SESSION['error_message'] = "Invalid product.";
}

// Handle POST requests (from product detail page with quantity)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id']) && is_numeric($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
    
    // Redirect to self with GET parameters
    header("Location: add_to_cart.php?id=" . $product_id . "&quantity=" . $quantity);
    exit();
}

// Redirect back to previous page or cart
if (isset($_GET['redirect']) && $_GET['redirect'] == 'cart') {
    header("Location: cart.php");
} else {
    header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'shop.php'));
}
exit();
?> 