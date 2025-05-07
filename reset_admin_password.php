<?php
// Include database connection
include_once("includes/db_connection.php");

// New password (you can change this to any password you prefer)
$new_password = "admin123";

// Update admin password in the database
$query = "UPDATE users SET password = '$new_password' WHERE username = 'admin' AND user_type = 'admin'";

if (mysqli_query($conn, $query)) {
    echo "<p>Admin password has been reset successfully to: <strong>$new_password</strong></p>";
    echo "<p>You can now <a href='admin/login.php'>login to the admin panel</a> using:</p>";
    echo "<p>Username: <strong>admin</strong><br>Password: <strong>$new_password</strong></p>";
} else {
    echo "Error resetting password: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?> 