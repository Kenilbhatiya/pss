<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Save current page to redirect after login
    $_SESSION['redirect_after_login'] = 'myaccount.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to account page if no valid order ID
    header("Location: myaccount.php");
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$order = null;
$order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$order_stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($order_stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

if ($order_result && mysqli_num_rows($order_result) > 0) {
    $order = mysqli_fetch_assoc($order_result);
} else {
    // Order not found or does not belong to the user
    header("Location: myaccount.php");
    exit();
}

// Get order items
$items = [];
$items_query = "SELECT oi.*, p.name, p.image_path 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

if ($items_result) {
    while ($row = mysqli_fetch_assoc($items_result)) {
        $items[] = $row;
    }
}

// Calculate subtotal
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate tax (assume tax and shipping are included in total_amount)
$tax_rate = 0.07; // 7% tax
$tax = $subtotal * $tax_rate;

// Shipping cost (simple calculation)
$shipping = $order['total_amount'] - $subtotal - $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <section class="order-details py-5">
        <div class="container">
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="myaccount.php">My Account</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Order #<?php echo $order_id; ?></li>
                    </ol>
                </nav>
            </div>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Order #<?php echo $order_id; ?></h5>
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
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="order-info mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-2">Order Date</h6>
                                        <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-2">Payment Method</h6>
                                        <p class="mb-0"><?php echo htmlspecialchars($order['payment_method']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mb-3">Items in your order</h6>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($order['tracking_number'] && $order['status'] != 'pending' && $order['status'] != 'cancelled'): ?>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Tracking Information</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-2">Shipping Method</h6>
                                        <p class="mb-3"><?php echo htmlspecialchars($order['shipping_method']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-2">Tracking Number</h6>
                                        <p class="mb-3"><?php echo htmlspecialchars($order['tracking_number']); ?></p>
                                    </div>
                                </div>
                                
                                <a href="https://track-package.com/<?php echo urlencode($order['tracking_number']); ?>" target="_blank" class="btn btn-outline-success">
                                    <i class="fas fa-truck me-2"></i> Track Package
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>₹<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (7%)</span>
                                <span>₹<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span>₹<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-0">
                                <strong>Total</strong>
                                <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Shipping Address</h5>
                        </div>
                        <div class="card-body p-4">
                            <address>
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </address>
                        </div>
                    </div>
                    
                    <?php if($order['billing_address']): ?>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Billing Address</h5>
                            </div>
                            <div class="card-body p-4">
                                <address>
                                    <?php echo nl2br(htmlspecialchars($order['billing_address'])); ?>
                                </address>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <a href="myaccount.php#orders" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Orders
                        </a>
                        <?php if($order['status'] == 'pending'): ?>
                            <a href="cancel-order.php?id=<?php echo $order_id; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this order?');">
                                <i class="fas fa-times-circle me-2"></i> Cancel Order
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 