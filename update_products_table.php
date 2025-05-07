<?php
// Include database connection
include_once("includes/db_connection.php");

// Check if the seller_id column exists in the products table
$check_column = "SHOW COLUMNS FROM products LIKE 'seller_id'";
$column_exists = mysqli_query($conn, $check_column);

if (mysqli_num_rows($column_exists) == 0) {
    // Add seller_id column if it doesn't exist
    $add_column = "ALTER TABLE products ADD COLUMN seller_id INT DEFAULT NULL";
    if (mysqli_query($conn, $add_column)) {
        echo "<p>Successfully added 'seller_id' column to the products table.</p>";
    } else {
        echo "<p>Error adding column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>'seller_id' column already exists in the products table.</p>";
}

// Update the product with ID 6 to have seller_id = 3
$update_product = "UPDATE products SET seller_id = 3 WHERE id = 6";
if (mysqli_query($conn, $update_product)) {
    echo "<p>Successfully updated product ID 6 with seller_id = 3.</p>";
} else {
    echo "<p>Error updating product: " . mysqli_error($conn) . "</p>";
}

// Check if the update was successful
$check_update = "SELECT id, name, seller_id FROM products WHERE id = 6";
$result = mysqli_query($conn, $check_update);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "<p>Product ID " . $row['id'] . " (" . $row['name'] . ") now has seller_id = " . $row['seller_id'] . "</p>";
}

echo "<p>Done. <a href='seller/index.php'>Return to seller dashboard</a></p>";
?> 