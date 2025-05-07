<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include_once("includes/db_connection.php");

echo "<h1>Plant Nursery Database Initialization</h1>";

// Array of required tables and their creation SQL
$tables = [
    'wishlist' => "CREATE TABLE `wishlist` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_wishlist_item` (`user_id`, `product_id`),
        KEY `user_id` (`user_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'user_addresses' => "CREATE TABLE `user_addresses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `address_type` enum('home','work','other') NOT NULL DEFAULT 'home',
        `address_line1` varchar(255) NOT NULL,
        `address_line2` varchar(255) DEFAULT NULL,
        `city` varchar(100) NOT NULL,
        `state` varchar(100) NOT NULL,
        `zip_code` varchar(20) NOT NULL,
        `country` varchar(100) NOT NULL DEFAULT 'India',
        `is_default` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'orders' => "CREATE TABLE `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `total_amount` decimal(10,2) NOT NULL,
        `shipping_address` text NOT NULL,
        `billing_address` text NOT NULL,
        `payment_method` varchar(50) NOT NULL,
        `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
        `delivery_date` date DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'order_items' => "CREATE TABLE `order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
        CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'products' => "CREATE TABLE `products` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'cart' => "CREATE TABLE `cart` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

// Check and create tables
echo "<h2>Checking and Creating Tables</h2>";
echo "<ul>";

foreach ($tables as $table_name => $create_sql) {
    $table_check_query = "SHOW TABLES LIKE '$table_name'";
    $table_check_result = mysqli_query($conn, $table_check_query);
    
    if (mysqli_num_rows($table_check_result) == 0) {
        echo "<li>Table '$table_name' doesn't exist. Creating it...</li>";
        
        if (mysqli_query($conn, $create_sql)) {
            echo "<li class='success'>Table '$table_name' created successfully!</li>";
        } else {
            echo "<li class='error'>Error creating table '$table_name': " . mysqli_error($conn) . "</li>";
        }
    } else {
        echo "<li>Table '$table_name' already exists.</li>";
    }
}

echo "</ul>";

// Check if we have any sample data in the products table
echo "<h2>Checking Sample Data</h2>";

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
        $insert_query = "INSERT INTO products (name, description, price, sale_price, category_id, stock, image_path, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = mysqli_prepare($conn, $insert_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssddiss", 
                $product['name'], 
                $product['description'], 
                $product['price'], 
                $product['sale_price'], 
                $category_id, 
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

// Check if we have any orders for our test user
echo "<h2>Creating Test Order</h2>";

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
    $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, payment_method, status) 
                   VALUES (1, 74.97, '123 Main St, Anytown, CA 12345', '123 Main St, Anytown, CA 12345', 'Credit Card', 'delivered')";
    
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

// Add CSS for styling
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #388e3c; }
    h2 { color: #2e7d32; margin-top: 30px; }
    ul { margin-bottom: 20px; }
    li { margin-bottom: 10px; }
    li.success { color: #2e7d32; }
    li.error { color: #c62828; }
    p { margin-bottom: 10px; }
    a { color: #388e3c; text-decoration: none; font-weight: bold; }
    a:hover { text-decoration: underline; }
    .btn { 
        display: inline-block; 
        padding: 10px 20px; 
        background-color: #388e3c; 
        color: white; 
        text-decoration: none; 
        border-radius: 4px; 
        margin-top: 20px;
    }
    .btn:hover { background-color: #2e7d32; }
</style>';

echo "<h2>Next Steps</h2>";
echo "<p>Database initialization is complete. You can now:</p>";
echo "<ul>";
echo "<li><a href='fix_session.php' class='btn'>Go to My Account</a> - Use this to log in directly</li>";
echo "<li><a href='login.php'>Go to Login Page</a> - Use normal login flow</li>";
echo "<li><a href='index.php'>Go to Homepage</a> - Browse the site</li>";
echo "</ul>";

// Clean up any temporary debug files
echo "<h2>Cleaning Up</h2>";
echo "<p>Cleaning up temporary files...</p>";

$temp_files = [
    'check_categories.php',
    'fix_duplicate_categories.php',
    'check_orders_table.php',
    'myaccount_debug.php',
    'debug_myaccount.php',
    'myaccount_error.php',
    'test_cookie.php',
    'test_cookie_check.php',
    'test_login.php',
    'check_database.php',
    'check_orders_columns.php'
];

foreach ($temp_files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p>Deleted temporary file: $file</p>";
        } else {
            echo "<p>Failed to delete temporary file: $file</p>";
        }
    }
}

echo "<p>All done! Your database is now properly set up.</p>";
?> 