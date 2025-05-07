<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with fixed settings
session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_path' => '/',
    'use_cookies' => 1,
    'use_only_cookies' => 1
]);

// Include database connection
include_once("includes/db_connection.php");

// Clear any existing session data to start fresh
session_unset();

// Set session variables directly
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists
$_SESSION['username'] = 'admin'; // Use a known username
$_SESSION['user_type'] = 'admin'; // Use a known user type

// Force session write
session_write_close();

// Reopen session to confirm it's working
session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_path' => '/',
    'use_cookies' => 1,
    'use_only_cookies' => 1
]);

// Output debug info
echo "<h2>Session Fixed</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session variables:</p>";
echo "<ul>";
echo "<li>user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "</li>";
echo "<li>username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'Not set') . "</li>";
echo "<li>user_type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Not set') . "</li>";
echo "</ul>";

// Check if we need to add the PHPSESSID cookie manually
if (!isset($_COOKIE['PHPSESSID'])) {
    echo "<p>PHPSESSID cookie not set. Setting it manually...</p>";
    setcookie('PHPSESSID', session_id(), time() + 86400, '/', '', false, true);
    echo "<p>Cookie set: PHPSESSID=" . session_id() . "</p>";
}

// Create a simple form that will post directly to myaccount.php with the session ID
echo "<form id='directForm' action='myaccount.php' method='post'>";
echo "<input type='hidden' name='PHPSESSID' value='" . session_id() . "'>";
echo "<input type='hidden' name='direct_login' value='1'>";
echo "<input type='submit' value='Click here to go to My Account page'>";
echo "</form>";

// Auto-submit the form
echo "<script>
    // Auto-submit after 2 seconds
    setTimeout(function() {
        document.getElementById('directForm').submit();
    }, 2000);
</script>";

// Add a simple button to follow the link manually
echo "<p>Or <a href='myaccount.php?PHPSESSID=" . session_id() . "'>click here</a> to go to My Account page.</p>";
?> 