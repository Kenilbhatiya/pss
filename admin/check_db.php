<?php
// Include database connection
include_once("../includes/db_connection.php");

// Print all tables
$tables_query = "SHOW TABLES";
$tables_result = mysqli_query($conn, $tables_query);

echo "<h2>Tables in plant_nursery database:</h2>";
echo "<ul>";
while($table = mysqli_fetch_row($tables_result)) {
    echo "<li>" . $table[0] . "</li>";
}
echo "</ul>";

// Check users table
$users_query = "DESCRIBE users";
$users_result = mysqli_query($conn, $users_query);

echo "<h2>Users table structure:</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($field = mysqli_fetch_assoc($users_result)) {
    echo "<tr>";
    echo "<td>" . $field['Field'] . "</td>";
    echo "<td>" . $field['Type'] . "</td>";
    echo "<td>" . $field['Null'] . "</td>";
    echo "<td>" . $field['Key'] . "</td>";
    echo "<td>" . $field['Default'] . "</td>";
    echo "<td>" . $field['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check orders table
$orders_query = "DESCRIBE orders";
$orders_result = mysqli_query($conn, $orders_query);

echo "<h2>Orders table structure:</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($field = mysqli_fetch_assoc($orders_result)) {
    echo "<tr>";
    echo "<td>" . $field['Field'] . "</td>";
    echo "<td>" . $field['Type'] . "</td>";
    echo "<td>" . $field['Null'] . "</td>";
    echo "<td>" . $field['Key'] . "</td>";
    echo "<td>" . $field['Default'] . "</td>";
    echo "<td>" . $field['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Get a sample order to check
$sample_order_query = "SELECT * FROM orders LIMIT 1";
$sample_order_result = mysqli_query($conn, $sample_order_query);
if (mysqli_num_rows($sample_order_result) > 0) {
    $sample_order = mysqli_fetch_assoc($sample_order_result);
    echo "<h2>Sample order:</h2>";
    echo "<pre>";
    print_r($sample_order);
    echo "</pre>";
}

// Check the SQL query that's not working
$orders_query = "SELECT o.*, u.username as customer_name, p.name as product_name
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                LEFT JOIN products p ON oi.product_id = p.id
                LIMIT 1";
$result = mysqli_query($conn, $orders_query);

if ($result) {
    echo "<h2>Test query result:</h2>";
    $test_order = mysqli_fetch_assoc($result);
    echo "<pre>";
    print_r($test_order);
    echo "</pre>";
} else {
    echo "<h2>Query error:</h2>";
    echo mysqli_error($conn);
}

// Close the connection
mysqli_close($conn);
?> 