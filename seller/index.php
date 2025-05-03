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

// Get counts for dashboard
$productCount = 0;
$categoryCount = 0;
$orderCount = 0;
$userCount = 0;

// Count products
$query = "SELECT COUNT(*) as count FROM products";
$result = mysqli_query($conn, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $productCount = $row['count'];
}

// Count categories
$query = "SELECT COUNT(*) as count FROM categories";
$result = mysqli_query($conn, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $categoryCount = $row['count'];
}

// Count orders
$query = "SELECT COUNT(*) as count FROM orders";
$result = mysqli_query($conn, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $orderCount = $row['count'];
}

// Count users
$query = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    $userCount = $row['count'];
}

// Get recent orders
$recentOrders = [];
$query = "SELECT o.id, o.total_amount, o.created_at, o.status, u.username 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $recentOrders[] = $row;
    }
}

// Get low stock products
$lowStockProducts = [];
$query = "SELECT id, name, stock_quantity as quantity, image_path FROM products WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 5";
$result = mysqli_query($conn, $query);
if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $lowStockProducts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Plant Nursery</title>
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="reports.php" class="btn btn-sm btn-outline-secondary">Reports</a>
                            <a href="settings.php" class="btn btn-sm btn-outline-secondary">Settings</a>
                        </div>
                        <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye"></i> View Site
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-shopping-cart fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Products</h5>
                                    <h2 class="mb-0"><?php echo $productCount; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="products.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-folder fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Categories</h5>
                                    <h2 class="mb-0"><?php echo $categoryCount; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="categories.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-clipboard-list fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Orders</h5>
                                    <h2 class="mb-0"><?php echo $orderCount; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="orders.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-danger h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-users fa-3x me-3"></i>
                                <div>
                                    <h5 class="card-title">Users</h5>
                                    <h2 class="mb-0"><?php echo $userCount; ?></h2>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="users.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-arrow-right text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-md-8 mb-4">
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
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($recentOrders) > 0): ?>
                                                <?php foreach($recentOrders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
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
                                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                        <td>
                                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No recent orders found</td>
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

                    <!-- Low Stock Products -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Low Stock Products</h5>
                            </div>
                            <div class="card-body">
                                <?php if(count($lowStockProducts) > 0): ?>
                                    <ul class="list-group">
                                        <?php foreach($lowStockProducts as $product): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <img src="../<?php echo $product['image_path']; ?>" alt="<?php echo $product['name']; ?>" class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <small class="text-muted">Remaining: <?php echo $product['quantity']; ?> units</small>
                                                </div>
                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-center">No low stock products found</p>
                                <?php endif; ?>
                                <div class="text-end mt-3">
                                    <a href="products.php?filter=low_stock" class="btn btn-sm btn-outline-primary">View All Low Stock</a>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html> 