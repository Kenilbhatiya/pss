<?php
// Start session
session_start();

// Check if user is logged in as admin
if(!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Initialize messages array
$messages = [];

// Process only if form submitted
if(isset($_POST['update'])) {
    // Check if the username column exists in the orders table
    $check_username_query = "SHOW COLUMNS FROM orders LIKE 'username'";
    $username_result = mysqli_query($conn, $check_username_query);
    $username_column_exists = mysqli_num_rows($username_result) > 0;

    // Check if the product_name column exists in the orders table
    $check_product_name_query = "SHOW COLUMNS FROM orders LIKE 'product_name'";
    $product_name_result = mysqli_query($conn, $check_product_name_query);
    $product_name_column_exists = mysqli_num_rows($product_name_result) > 0;

    // If columns don't exist, add them
    if (!$username_column_exists) {
        $add_username_query = "ALTER TABLE orders ADD COLUMN username VARCHAR(100) AFTER user_id";
        if (mysqli_query($conn, $add_username_query)) {
            $messages[] = ["type" => "success", "text" => "Added username column to orders table."];
        } else {
            $messages[] = ["type" => "danger", "text" => "Error adding username column: " . mysqli_error($conn)];
        }
    }

    if (!$product_name_column_exists) {
        $add_product_name_query = "ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) AFTER username";
        if (mysqli_query($conn, $add_product_name_query)) {
            $messages[] = ["type" => "success", "text" => "Added product_name column to orders table."];
        } else {
            $messages[] = ["type" => "danger", "text" => "Error adding product_name column: " . mysqli_error($conn)];
        }
    }

    // Update orders with missing username
    $update_usernames_query = "UPDATE orders o
                              JOIN users u ON o.user_id = u.id
                              SET o.username = u.username
                              WHERE o.username IS NULL OR o.username = ''";
    if (mysqli_query($conn, $update_usernames_query)) {
        $rows_affected = mysqli_affected_rows($conn);
        $messages[] = ["type" => "success", "text" => "Updated usernames for $rows_affected orders."];
    } else {
        $messages[] = ["type" => "danger", "text" => "Error updating usernames: " . mysqli_error($conn)];
    }

    // Update orders with missing product names
    // For each order, we'll use the first product in the order items
    $update_product_names_query = "UPDATE orders o
                                  JOIN (
                                      SELECT oi.order_id, p.name
                                      FROM order_items oi
                                      JOIN products p ON oi.product_id = p.id
                                      GROUP BY oi.order_id
                                  ) AS first_product ON o.id = first_product.order_id
                                  SET o.product_name = first_product.name
                                  WHERE o.product_name IS NULL OR o.product_name = ''";
    if (mysqli_query($conn, $update_product_names_query)) {
        $rows_affected = mysqli_affected_rows($conn);
        $messages[] = ["type" => "success", "text" => "Updated product names for $rows_affected orders."];
    } else {
        $messages[] = ["type" => "danger", "text" => "Error updating product names: " . mysqli_error($conn)];
    }

    $messages[] = ["type" => "info", "text" => "Orders update completed."];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Orders Fields - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once('includes/sidebar.php'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="orders.php">Orders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Update Orders Fields</li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Update Orders Fields</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </div>
                
                <?php if(!empty($messages)): ?>
                    <div class="alert-container">
                        <?php foreach($messages as $message): ?>
                            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message['text']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="p-4 text-center">
                            <h4 class="mb-4">Update Missing Order Fields</h4>
                            <p class="mb-3">
                                This tool will ensure that all orders have the username and product name fields populated correctly.
                                It will add these columns if they don't exist and then update them based on related data.
                            </p>
                            <form method="post" action="">
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-sync-alt me-1"></i> Update Orders Fields
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mb-4">
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
                    </a>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close connection
mysqli_close($conn);
?> 