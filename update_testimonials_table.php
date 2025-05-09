<?php
// Include database connection
include_once("includes/db_connection.php");

// Check if user_id column already exists
$check_column_query = "SHOW COLUMNS FROM testimonials LIKE 'user_id'";
$check_column_result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($check_column_result) == 0) {
    // Column doesn't exist, add it
    $alter_query = "ALTER TABLE testimonials ADD COLUMN user_id INT NULL AFTER rating, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL";
    
    if (mysqli_query($conn, $alter_query)) {
        echo "Successfully added user_id column to testimonials table.";
    } else {
        echo "Error adding user_id column: " . mysqli_error($conn);
    }
} else {
    echo "The user_id column already exists in the testimonials table.";
}

// Optional: Redirect to admin dashboard after 5 seconds
header("refresh:5;url=admin/index.php");
?> 