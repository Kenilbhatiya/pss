<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Populate Test Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
        }
        h1 {
            color: #333;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Populate Test Orders</h1>
        <div id="results">
<?php
// Include database connection
include_once("includes/db_connection.php");

// Check if we have any orders
$check_orders_query = "SELECT COUNT(*) as order_count FROM orders";
$check_result = mysqli_query($conn, $check_orders_query);
$order_count = 0;

if ($check_result && $row = mysqli_fetch_assoc($check_result)) {
    $order_count = $row['order_count'];
}

echo "<p>Found {$order_count} existing orders.</p>";

// Create some test data if we have less than 5 orders
if ($order_count < 5) {
    // Make sure we have users
    $check_users_query = "SELECT id, username FROM users WHERE user_type = 'buyer' LIMIT 3";
    $users_result = mysqli_query($conn, $check_users_query);
    $users = [];
    
    if ($users_result && mysqli_num_rows($users_result) > 0) {
        while ($row = mysqli_fetch_assoc($users_result)) {
            $users[] = $row;
        }
    }
    
    // Create a test user if none exist
    if (count($users) == 0) {
        echo "<p>No buyer users found. Creating a test user.</p>";
        
        $insert_user_query = "INSERT INTO users (username, email, password, user_type, status, created_at) 
                             VALUES ('testbuyer', 'testbuyer@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'buyer', 1, NOW())";
        
        if (mysqli_query($conn, $insert_user_query)) {
            $user_id = mysqli_insert_id($conn);
            $users[] = [
                'id' => $user_id,
                'username' => 'testbuyer'
            ];
            echo "<p class='success'>Created test buyer with ID {$user_id}.</p>";
        } else {
            echo "<p class='error'>Error creating test user: " . mysqli_error($conn) . "</p>";
        }
    }
    
    // Make sure we have products
    $check_products_query = "SELECT id, name, price FROM products LIMIT 5";
    $products_result = mysqli_query($conn, $check_products_query);
    $products = [];
    
    if ($products_result && mysqli_num_rows($products_result) > 0) {
        while ($row = mysqli_fetch_assoc($products_result)) {
            $products[] = $row;
        }
    }
    
    // Create some test products if none exist
    if (count($products) < 3) {
        echo "<p>Not enough products found. Creating test products.</p>";
        
        $test_products = [
            ['Monstera Deliciosa', 'Beautiful indoor plant with unique leaf patterns', 29.99],
            ['Peace Lily', 'Air-purifying flowering plant that thrives indoors', 24.99],
            ['Snake Plant', 'Low-maintenance indoor plant perfect for beginners', 19.99]
        ];
        
        foreach ($test_products as $product) {
            $insert_product_query = "INSERT INTO products (name, description, price, stock_quantity, image_path, created_at) 
                                    VALUES (?, ?, ?, 10, 'images/products/default.jpg', NOW())";
            
            $stmt = mysqli_prepare($conn, $insert_product_query);
            mysqli_stmt_bind_param($stmt, "ssd", $product[0], $product[1], $product[2]);
            
            if (mysqli_stmt_execute($stmt)) {
                $product_id = mysqli_insert_id($conn);
                $products[] = [
                    'id' => $product_id,
                    'name' => $product[0],
                    'price' => $product[2]
                ];
                echo "<p class='success'>Created test product '{$product[0]}' with ID {$product_id}.</p>";
            } else {
                echo "<p class='error'>Error creating test product: " . mysqli_error($conn) . "</p>";
            }
        }
    }
    
    // Create test orders
    $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $orders_created = 0;
    
    for ($i = 0; $i < 5 - $order_count; $i++) {
        // Select a random user
        $user = $users[array_rand($users)];
        $user_id = $user['id'];
        $username = $user['username'];
        
        // Select a random product
        $product = $products[array_rand($products)];
        $product_id = $product['id'];
        $product_name = $product['name'];
        $product_price = $product['price'];
        
        // Random quantity between 1 and 3
        $quantity = rand(1, 3);
        $total_amount = $product_price * $quantity;
        
        // Random status
        $status = $statuses[array_rand($statuses)];
        
        // Create order
        $insert_order_query = "INSERT INTO orders (user_id, username, product_name, total_amount, shipping_address, billing_address, payment_method, status, delivery_date, created_at) 
                              VALUES (?, ?, ?, ?, '123 Main St, Anytown, CA 12345', '123 Main St, Anytown, CA 12345', 'Credit Card', ?, DATE_ADD(NOW(), INTERVAL 2 DAY), NOW() - INTERVAL ? DAY)";
        
        $stmt = mysqli_prepare($conn, $insert_order_query);
        $days_ago = rand(0, 30); // Order created 0-30 days ago
        mysqli_stmt_bind_param($stmt, "issdsi", $user_id, $username, $product_name, $total_amount, $status, $days_ago);
        
        if (mysqli_stmt_execute($stmt)) {
            $order_id = mysqli_insert_id($conn);
            
            // Create order item
            $insert_order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())";
            
            $item_stmt = mysqli_prepare($conn, $insert_order_item_query);
            mysqli_stmt_bind_param($item_stmt, "iidd", $order_id, $product_id, $quantity, $product_price);
            
            if (mysqli_stmt_execute($item_stmt)) {
                $orders_created++;
                echo "<p class='success'>Created test order #{$order_id} for {$username} with product '{$product_name}'.</p>";
            } else {
                echo "<p class='error'>Error creating order item: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p class='error'>Error creating order: " . mysqli_error($conn) . "</p>";
        }
    }
    
    echo "<p><strong>Created {$orders_created} test orders.</strong></p>";
} else {
    echo "<p>There are already {$order_count} orders in the database. No need to create test data.</p>";
}
?>
        </div>
        <p><a href='admin/index.php' class="btn">Go to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
// Close connection
mysqli_close($conn);
?> 