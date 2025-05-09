<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once("includes/db_connection.php");

$user_id = $_SESSION['user_id'];
$success = false;
$error = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $comment = isset($_POST['comment']) ? mysqli_real_escape_string($conn, $_POST['comment']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $display_name = isset($_POST['display_name']) ? 1 : 0;
    
    // If user unchecks display name, use "Anonymous Customer" instead
    if (!$display_name) {
        $name = "Anonymous Customer";
    }
    
    // Validate required fields
    if (empty($comment) || $rating < 1 || $rating > 5) {
        $error = "Please provide a comment and valid rating.";
    } else {
        // Process image upload if exists
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploads_dir = 'images/testimonials/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
            }
            
            $tmp_name = $_FILES['image']['tmp_name'];
            $image_name = basename($_FILES['image']['name']);
            $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
            $unique_image_name = 'testimonial_' . time() . '_' . uniqid() . '.' . $image_ext;
            
            if (move_uploaded_file($tmp_name, $uploads_dir . $unique_image_name)) {
                $image_path = $uploads_dir . $unique_image_name;
            } else {
                $error = "Error uploading image.";
            }
        }
        
        // Only proceed if no errors
        if (empty($error)) {
            // Insert testimonial into database
            $query = "INSERT INTO testimonials (name, image_path, comment, rating, user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $query);
            
            if ($stmt === false) {
                $error = "Failed to prepare statement: " . mysqli_error($conn);
            } else {
                // Ensure proper data types for parameters
                // s = string, i = integer, d = double, b = blob
                if (!mysqli_stmt_bind_param($stmt, "sssii", $name, $image_path, $comment, $rating, $user_id)) {
                    $error = "Failed to bind parameters: " . mysqli_stmt_error($stmt);
                } elseif (!mysqli_stmt_execute($stmt)) {
                    $error = "Failed to execute statement: " . mysqli_stmt_error($stmt);
                } else {
                    $success = true;
                    $_SESSION['success_message'] = "Thank you for your feedback! Your testimonial has been submitted successfully.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Redirect back to account page
if ($success) {
    header("Location: myaccount.php#feedback");
} else {
    if (!empty($error)) {
        $_SESSION['error_message'] = $error;
    }
    header("Location: myaccount.php#feedback");
}
exit(); 