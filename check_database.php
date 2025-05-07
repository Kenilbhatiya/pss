<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once("includes/db_connection.php");

echo "<h2>Database Check and Repair Script</h2>";

// Check for products table
$table_check_query = "SHOW TABLES LIKE 'products'";
$table_check_result = mysqli_query($conn, $table_check_query);

if (mysqli_num_rows($table_check_result) == 0) {
    echo "<p>Products table doesn't exist. Creating it now...</p>";
    
    $create_table_query = "CREATE TABLE `products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `sale_price` decimal(10,2) DEFAULT NULL,
        `category_id` int(11) NOT NULL,
        `seller_id` int(11) DEFAULT NULL,
        `stock` int(11) NOT NULL DEFAULT '0',
        `image_path` varchar(255) DEFAULT NULL,
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `category_id` (`category_id`),
        KEY `seller_id` (`seller_id`),
        CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
        CONSTRAINT `products_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p>Products table created successfully!</p>";
    } else {
        echo "<p>Error creating products table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Products table exists.</p>";
}

// Add some sample products if none exist
$products_check_query = "SELECT COUNT(*) as count FROM products";
$products_check_result = mysqli_query($conn, $products_check_query);
$products_count = 0;

if ($products_check_result) {
    $row = mysqli_fetch_assoc($products_check_result);
    $products_count = $row['count'];
}

if ($products_count == 0) {
    echo "<p>No products found. Adding sample products...</p>";
    
    // First check if we have categories
    $categories_check_query = "SELECT id FROM categories LIMIT 1";
    $categories_check_result = mysqli_query($conn, $categories_check_query);
    
    if (mysqli_num_rows($categories_check_result) == 0) {
        // Create a default category
        $create_category_query = "INSERT INTO categories (name, description, image_path) VALUES 
            ('Indoor Plants', 'Beautiful plants for your home', 'images/categories/indoor.jpg')";
        mysqli_query($conn, $create_category_query);
        $category_id = mysqli_insert_id($conn);
        echo "<p>Created default category.</p>";
    } else {
        $row = mysqli_fetch_assoc($categories_check_result);
        $category_id = $row['id'];
    }
    
    // Check if sellers table exists and has entries
    $sellers_check_query = "SHOW TABLES LIKE 'sellers'";
    $sellers_check_result = mysqli_query($conn, $sellers_check_query);
    $seller_id = null;
    
    if (mysqli_num_rows($sellers_check_result) > 0) {
        $sellers_data_query = "SELECT id FROM sellers LIMIT 1";
        $sellers_data_result = mysqli_query($conn, $sellers_data_query);
        
        if (mysqli_num_rows($sellers_data_result) > 0) {
            $row = mysqli_fetch_assoc($sellers_data_result);
            $seller_id = $row['id'];
        }
    }
    
    // Add sample products
    $sample_products = [
        [
            'name' => 'Monstera Deliciosa',
            'description' => 'The Swiss Cheese Plant is famous for its natural leaf holes.',
            'price' => 29.99,
            'sale_price' => 24.99,
            'stock' => 10,
            'image_path' => 'images/products/monstera.jpg'
        ],
        [
            'name' => 'Snake Plant',
            'description' => 'One of the most tolerant indoor plants available.',
            'price' => 19.99,
            'sale_price' => NULL,
            'stock' => 15,
            'image_path' => 'images/products/snake_plant.jpg'
        ],
        [
            'name' => 'Peace Lily',
            'description' => 'Elegant white flowers and glossy leaves.',
            'price' => 24.99,
            'sale_price' => 22.99,
            'stock' => 8,
            'image_path' => 'images/products/peace_lily.jpg'
        ]
    ];
    
    $success_count = 0;
    foreach ($sample_products as $product) {
        $insert_query = "INSERT INTO products (name, description, price, sale_price, category_id, seller_id, stock, image_path, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = mysqli_prepare($conn, $insert_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssddiiis", 
                $product['name'], 
                $product['description'], 
                $product['price'], 
                $product['sale_price'], 
                $category_id, 
                $seller_id, 
                $product['stock'], 
                $product['image_path']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            }
        }
    }
    
    echo "<p>Added $success_count sample products.</p>";
} else {
    echo "<p>Found $products_count products in the database.</p>";
}

// Check cart table
$cart_check_query = "SHOW TABLES LIKE 'cart'";
$cart_check_result = mysqli_query($conn, $cart_check_query);

if (mysqli_num_rows($cart_check_result) == 0) {
    echo "<p>Cart table doesn't exist. Creating it now...</p>";
    
    $create_table_query = "CREATE TABLE `cart` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL DEFAULT '1',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_product` (`user_id`,`product_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p>Cart table created successfully!</p>";
    } else {
        echo "<p>Error creating cart table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Cart table exists.</p>";
}

// Check if we have any orders for our test user
$orders_check_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = 1";
$orders_check_result = mysqli_query($conn, $orders_check_query);
$orders_count = 0;

if ($orders_check_result) {
    $row = mysqli_fetch_assoc($orders_check_result);
    $orders_count = $row['count'];
}

if ($orders_count == 0) {
    echo "<p>No orders found for test user. Creating a sample order...</p>";
    
    // Create a sample order
    $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, payment_method, status, delivery_date, created_at) 
                   VALUES (1, 74.97, '123 Main St, Anytown, CA 12345', '123 Main St, Anytown, CA 12345', 'Credit Card', 'delivered', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), NOW())";
    
    if (mysqli_query($conn, $order_query)) {
        $order_id = mysqli_insert_id($conn);
        
        // Get product IDs
        $products_query = "SELECT id, price FROM products LIMIT 3";
        $products_result = mysqli_query($conn, $products_query);
        
        if ($products_result && mysqli_num_rows($products_result) > 0) {
            $items_added = 0;
            
            while ($product = mysqli_fetch_assoc($products_result)) {
                $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)";
                $stmt = mysqli_prepare($conn, $order_item_query);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "iid", $order_id, $product['id'], $product['price']);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $items_added++;
                    }
                }
            }
            
            echo "<p>Added $items_added items to the sample order.</p>";
        } else {
            echo "<p>No products found to add to the order.</p>";
        }
        
        echo "<p>Created sample order #$order_id.</p>";
    } else {
        echo "<p>Error creating sample order: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Found $orders_count orders for test user.</p>";
}

echo "<p>Database check and repair completed. <a href='myaccount.php'>Try My Account page now</a></p>";
?> 