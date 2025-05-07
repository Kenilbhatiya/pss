<?php
// Start session
session_start();

// Check if user is logged in as seller
if(!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Get seller ID from session
$seller_id = $_SESSION['seller_id'];

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sales overview - Total sales and orders for this seller
$sales_query = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(oi.price * oi.quantity) as total_sales,
                AVG(oi.price * oi.quantity) as average_order_value
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE p.seller_id = ? 
                AND o.created_at BETWEEN ? AND ?";
$sales_stmt = $conn->prepare($sales_query);
$sales_stmt->bind_param("iss", $seller_id, $start_date, $end_date);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();
$sales_data = $sales_result->fetch_assoc();

// Top selling products for this seller
$products_query = "SELECT 
                p.name as product_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.price * oi.quantity) as total_revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE p.seller_id = ?
                AND o.created_at BETWEEN ? AND ?
                GROUP BY p.id
                ORDER BY total_quantity DESC
                LIMIT 5";
$products_stmt = $conn->prepare($products_query);
$products_stmt->bind_param("iss", $seller_id, $start_date, $end_date);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$top_products = [];
while ($row = $products_result->fetch_assoc()) {
    $top_products[] = $row;
}

// Monthly sales data for chart
$monthly_query = "SELECT 
                 DATE_FORMAT(o.created_at, '%Y-%m') as month,
                 SUM(oi.price * oi.quantity) as monthly_sales
                 FROM orders o
                 JOIN order_items oi ON o.id = oi.order_id
                 JOIN products p ON oi.product_id = p.id
                 WHERE p.seller_id = ?
                 AND o.created_at BETWEEN DATE_SUB(?, INTERVAL 12 MONTH) AND ?
                 GROUP BY month
                 ORDER BY month";
$monthly_stmt = $conn->prepare($monthly_query);
$monthly_stmt->bind_param("iss", $seller_id, $end_date, $end_date);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_data = [];
$labels = [];
$values = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
    $labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $values[] = $row['monthly_sales'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Plant Nursery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/seller-style.css">
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
                                <h3 class="mb-0"><?php echo $sales_data['total_orders'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Sales</h5>
                                <h3 class="mb-0">₹<?php echo number_format($sales_data['total_sales'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Average Order Value</h5>
                                <h3 class="mb-0">₹<?php echo number_format($sales_data['average_order_value'] ?? 0, 2); ?></h3>
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
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
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

                <!-- Monthly Sales Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Sales</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Monthly Sales Chart
    const monthlySalesChart = new Chart(document.getElementById('monthlySalesChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Monthly Sales',
                data: <?php echo json_encode($values); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderWidth: 2
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html> 