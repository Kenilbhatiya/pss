<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
session_start([
    'cookie_lifetime' => 86400, // 1 day
    'cookie_httponly' => true,
    'cookie_path' => '/',
    'use_cookies' => 1,
    'use_only_cookies' => 1
]);

// Include database connection
include_once("includes/db_connection.php");

// Get user data from database to ensure we have accurate information
$user_query = "SELECT * FROM users WHERE id = 1";
$result = mysqli_query($conn, $user_query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    
    // Set session variables directly (simulate login)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    
    // Debug info
    echo "<h2>Setup completed</h2>";
    echo "<p>Session variables set:</p>";
    echo "<ul>";
    echo "<li>User ID: " . $_SESSION['user_id'] . "</li>";
    echo "<li>Username: " . $_SESSION['username'] . "</li>";
    echo "<li>User Type: " . $_SESSION['user_type'] . "</li>";
    echo "<li>Session ID: " . session_id() . "</li>";
    echo "</ul>";
    
    // Link to myaccount
    echo "<p>Now you can proceed to <a href='myaccount.php'>My Account</a></p>";
    echo "<script>
        // Auto-redirect after 2 seconds
        setTimeout(function() {
            window.location.href = 'myaccount.php';
        }, 2000);
    </script>";
} else {
    echo "<h2>Error</h2>";
    echo "<p>Could not find user with ID 1. Please make sure you have at least one user in the database.</p>";
}
?> 