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

// Get date filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Set date range based on period
$today = date('Y-m-d');
$start_date = '';
$end_date = $today;

switch($period) {
    case 'today':
        $start_date = $today;
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'quarter':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    case 'custom':
        if(!empty($custom_start)) {
            $start_date = $custom_start;
        } else {
            $start_date = date('Y-m-d', strtotime('-1 month'));
        }
        
        if(!empty($custom_end)) {
            $end_date = $custom_end;
        }
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-1 month'));
        break;
}

// Get sales summary
$sales_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as average_order,
                COUNT(DISTINCT user_id) as unique_customers
                FROM orders
                WHERE created_at BETWEEN ? AND ?";

$stmt = mysqli_prepare($conn, $sales_query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sales_summary = mysqli_fetch_assoc($result);

// Get orders by status
$status_query = "SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as total
                FROM orders
                WHERE created_at BETWEEN ? AND ?
                GROUP BY status";

$stmt = mysqli_prepare($conn, $status_query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders_by_status = [];

while($row = mysqli_fetch_assoc($result)) {
    $orders_by_status[$row['status']] = $row;
}

// Get top selling products
$products_query = "SELECT 
                  p.id,
                  p.name,
                  SUM(oi.quantity) as total_quantity,
                  SUM(oi.quantity * oi.price) as total_sales
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  JOIN orders o ON oi.order_id = o.id
                  WHERE o.created_at BETWEEN ? AND ?
                  GROUP BY p.id
                  ORDER BY total_sales DESC
                  LIMIT 5";

$stmt = mysqli_prepare($conn, $products_query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$top_products = [];

while($row = mysqli_fetch_assoc($result)) {
    $top_products[] = $row;
}

// Get daily sales for chart
$daily_sales_query = "SELECT 
                     DATE(created_at) as date,
                     COUNT(*) as order_count,
                     SUM(total_amount) as total_sales
                     FROM orders
                     WHERE created_at BETWEEN ? AND ?
                     GROUP BY DATE(created_at)
                     ORDER BY date";

$stmt = mysqli_prepare($conn, $daily_sales_query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$daily_sales = [];
$dates = [];
$sales_data = [];

while($row = mysqli_fetch_assoc($result)) {
    $daily_sales[] = $row;
    $dates[] = date('M d', strtotime($row['date']));
    $sales_data[] = $row['total_sales'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once('includes/sidebar.php'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Sales Reports</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye"></i> View Website
                        </a>
                    </div>
                </div>

                <!-- Date Filter -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="period" class="form-label">Time Period</label>
                                <select class="form-select" id="period" name="period" onchange="toggleCustomDates()">
                                    <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="quarter" <?php echo $period == 'quarter' ? 'selected' : ''; ?>>Last 3 Months</option>
                                    <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>Last 12 Months</option>
                                    <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>
                            <div class="col-md-3 custom-date" <?php echo $period != 'custom' ? 'style="display:none;"' : ''; ?>>
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $custom_start; ?>">
                            </div>
                            <div class="col-md-3 custom-date" <?php echo $period != 'custom' ? 'style="display:none;"' : ''; ?>>
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $custom_end; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply</button>
                                <a href="reports.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sales Summary -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Total Sales</h5>
                                <h2 class="display-6 fw-bold mb-0">₹<?php echo number_format($sales_summary['total_sales'] ?? 0, 2); ?></h2>
                                <p class="text-muted"><?php echo $sales_summary['total_orders'] ?? 0; ?> orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Average Order Value</h5>
                                <h2 class="display-6 fw-bold mb-0">₹<?php echo number_format($sales_summary['average_order'] ?? 0, 2); ?></h2>
                                <p class="text-muted">&nbsp;</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Customers</h5>
                                <h2 class="display-6 fw-bold mb-0"><?php echo $sales_summary['unique_customers'] ?? 0; ?></h2>
                                <p class="text-muted">unique customers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title text-muted">Conversion Rate</h5>
                                <h2 class="display-6 fw-bold mb-0">
                                    <?php 
                                        $conversion = 0;
                                        if(isset($sales_summary['unique_customers']) && $sales_summary['unique_customers'] > 0) {
                                            $conversion = ($sales_summary['total_orders'] / $sales_summary['unique_customers']) * 100;
                                        }
                                        echo number_format($conversion, 1) . '%';
                                    ?>
                                </h2>
                                <p class="text-muted">orders per customer</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Sales Chart -->
                    <div class="col-md-8 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Sales Trend</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Orders by Status -->
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Orders by Status</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Orders</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                                        foreach($statuses as $status):
                                            $count = isset($orders_by_status[$status]) ? $orders_by_status[$status]['count'] : 0;
                                            $total = isset($orders_by_status[$status]) ? $orders_by_status[$status]['total'] : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($status) {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'processing': echo 'info'; break;
                                                            case 'shipped': echo 'primary'; break;
                                                            case 'delivered': echo 'success'; break;
                                                            case 'cancelled': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $count; ?></td>
                                                <td>₹<?php echo number_format($total, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Top Selling Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($top_products) > 0): ?>
                                        <?php foreach($top_products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo $product['total_quantity']; ?></td>
                                                <td>₹<?php echo number_format($product['total_sales'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No sales data available for this period</td>
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
    
    <script>
        // Toggle custom date inputs based on period selection
        function toggleCustomDates() {
            const period = document.getElementById('period').value;
            const customDateFields = document.querySelectorAll('.custom-date');
            
            if(period === 'custom') {
                customDateFields.forEach(field => field.style.display = 'block');
            } else {
                customDateFields.forEach(field => field.style.display = 'none');
            }
        }
        
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Sales (₹)',
                    data: <?php echo json_encode($sales_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₹' + context.raw;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 