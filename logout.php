<?php
// Start session
session_start();

// Set logout success message
$_SESSION['success_message'] = "You have been successfully logged out.";

// Unset all session variables
$_SESSION = array();

// If session is using cookies, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit();
?> 