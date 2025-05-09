<?php
include_once("includes/db_connection.php");

// Update Succulents category (ID 3)
$category_id = 3;
$image_path = "images/succulents.jpg";
$description = "A beautiful collection of low-maintenance succulents perfect for any home or office. These hardy plants store water in their leaves and are ideal for busy people or beginners.";

// Update the database
$update_query = "UPDATE categories SET 
                image_path = '$image_path',
                description = '$description'
                WHERE id = $category_id";

if (mysqli_query($conn, $update_query)) {
    echo "Success! Succulents category has been updated.";
    echo "<br><a href='category.php?id=$category_id'>View Succulents Category</a>";
} else {
    echo "Error updating category: " . mysqli_error($conn);
}
?> 