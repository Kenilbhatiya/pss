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

// Get admin ID from session
$admin_id = $_SESSION['admin_id'];

// Get total counts for dashboard
// Count sellers
$sellers_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'seller'";
$sellers_result = mysqli_query($conn, $sellers_query);
$sellers_count = 0;
if($sellers_result && $row = mysqli_fetch_assoc($sellers_result)) {
    $sellers_count = $row['count'];
}

// Count users
$users_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'buyer'";
$users_result = mysqli_query($conn, $users_query);
$users_count = 0;
if($users_result && $row = mysqli_fetch_assoc($users_result)) {
    $users_count = $row['count'];
}

// Count orders
$orders_query = "SELECT COUNT(*) as count FROM orders";
$orders_result = mysqli_query($conn, $orders_query);
$orders_count = 0;
if($orders_result && $row = mysqli_fetch_assoc($orders_result)) {
    $orders_count = $row['count'];
}

// Count products
$products_query = "SELECT COUNT(*) as count FROM products";
$products_result = mysqli_query($conn, $products_query);
$products_count = 0;
if($products_result && $row = mysqli_fetch_assoc($products_result)) {
    $products_count = $row['count'];
}

// Get recent orders
$recent_orders_query = "SELECT o.*, u.username as customer_name, p.name as product_name 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id
                       LEFT JOIN order_items oi ON o.id = oi.order_id
                       LEFT JOIN products p ON oi.product_id = p.id
                       GROUP BY o.id
                       ORDER BY o.created_at DESC LIMIT 5";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);
$recent_orders = [];
if($recent_orders_result) {
    while($row = mysqli_fetch_assoc($recent_orders_result)) {
        $recent_orders[] = $row;
    }
}

// Get recent sellers
$recent_sellers_query = "SELECT u.*, COUNT(p.id) as product_count 
                        FROM users u 
                        LEFT JOIN products p ON u.id = p.seller_id 
                        WHERE u.user_type = 'seller' 
                        GROUP BY u.id 
                        ORDER BY u.created_at DESC LIMIT 5";
$recent_sellers_result = mysqli_query($conn, $recent_sellers_query);
$recent_sellers = [];
if($recent_sellers_result) {
    while($row = mysqli_fetch_assoc($recent_sellers_result)) {
        $recent_sellers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Plant Nursery</title>
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
                    <h1 class="h2">Admin Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye"></i> View Website
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary stat-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-store fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Sellers</h5>
                                    <h2 class="mb-0"><?php echo $sellers_count; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="sellers.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success stat-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-users fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Users</h5>
                                    <h2 class="mb-0"><?php echo $users_count; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="users.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning stat-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-shopping-cart fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Orders</h5>
                                    <h2 class="mb-0"><?php echo $orders_count; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="orders.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info stat-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-leaf fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Products</h5>
                                    <h2 class="mb-0"><?php echo $products_count; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="products.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Orders</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Product</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($recent_orders) > 0): ?>
                                                <?php foreach($recent_orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                switch($order['status']) {
                                                                    case 'pending': echo 'warning'; break;
                                                                    case 'processing': echo 'info'; break;
                                                                    case 'shipped': echo 'primary'; break;
                                                                    case 'delivered': echo 'success'; break;
                                                                    case 'cancelled': echo 'danger'; break;
                                                                    default: echo 'secondary';
                                                                }
                                                            ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No recent orders found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sellers -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Sellers</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Seller</th>
                                                <th>Email</th>
                                                <th>Products</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($recent_sellers) > 0): ?>
                                                <?php foreach($recent_sellers as $seller): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($seller['username']); ?></td>
                                                        <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                                        <td><?php echo $seller['product_count']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $seller['status'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $seller['status'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="seller-details.php?id=<?php echo $seller['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No sellers found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="sellers.php" class="btn btn-sm btn-outline-primary">View All Sellers</a>
                                </div>
                            </div>
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