<?php
// Start session
session_start();

// Check if user is logged in as admin
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sales Summary
$sales_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_sales,
    AVG(total_amount) as average_order_value
FROM orders 
WHERE created_at BETWEEN ? AND ?
AND status != 'cancelled'";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$sales_summary = $stmt->get_result()->fetch_assoc();

// Top Products
$top_products_query = "SELECT 
    p.name,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.quantity * oi.price) as total_revenue
FROM order_items oi
JOIN products p ON oi.product_id = p.id
JOIN orders o ON oi.order_id = o.id
WHERE o.created_at BETWEEN ? AND ?
AND o.status != 'cancelled'
GROUP BY p.id
ORDER BY total_revenue DESC
LIMIT 5";

$stmt = $conn->prepare($top_products_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$top_products = [];

// Check if the query was successful before calling fetch_all()
if ($result && $result->num_rows > 0) {
    $top_products = $result->fetch_all(MYSQLI_ASSOC);
}

// Low Stock Products
$low_stock_query = "SELECT 
    name,
    stock_quantity,
    reorder_level
FROM products
WHERE stock_quantity <= reorder_level
ORDER BY stock_quantity ASC";

$low_stock_result = $conn->query($low_stock_query);
$low_stock_products = [];

// Check if the query was successful before calling fetch_all()
if ($low_stock_result && $low_stock_result->num_rows > 0) {
    $low_stock_products = $low_stock_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Plant Nursery</title>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports</h1>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Date Range Filter</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Start Date:</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Date:</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sales Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Orders</h5>
                                <h3 class="mb-0"><?php echo $sales_summary['total_orders'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Sales</h5>
                                <h3 class="mb-0">₹<?php echo number_format($sales_summary['total_sales'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Average Order Value</h5>
                                <h3 class="mb-0">₹<?php echo number_format($sales_summary['average_order_value'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Selling Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($top_products) > 0): ?>
                                        <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo $product['total_quantity']; ?></td>
                                            <td>₹<?php echo number_format($product['total_revenue'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No product data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($low_stock_products) > 0): ?>
                                        <?php foreach ($low_stock_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo $product['stock_quantity']; ?></td>
                                            <td><?php echo $product['reorder_level']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No low stock products</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 