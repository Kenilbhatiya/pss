<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if there's an order ID in session
if (!isset($_SESSION['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_SESSION['order_id'];
unset($_SESSION['order_id']); // Clear the order ID from session

// Get order details
$order_query = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = ? AND o.user_id = ?";
$order_stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($order_stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: index.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, p.name, p.image_path 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$order_items = [];
$subtotal = 0;

while ($item = mysqli_fetch_assoc($items_result)) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $order_items[] = $item;
    $subtotal += $item['subtotal'];
}

// Calculate tax and shipping
$tax_rate = 0.07; // 7% tax
$tax_amount = $subtotal * $tax_rate;
$shipping = 5.99; // Standard shipping

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Order Confirmation Banner -->
    <section class="confirmation-banner py-5 bg-light">
        <div class="container text-center">
            <div class="mb-4">
                <i class="fas fa-check-circle text-success fa-4x"></i>
            </div>
            <h1 class="display-4 fw-bold text-success">Thank You for Your Order!</h1>
            <p class="lead">Your order has been placed successfully.</p>
            <p class="mb-0">Order #<?php echo sprintf('%06d', $order_id); ?></p>
            <p>A confirmation email has been sent to <?php echo htmlspecialchars($order['email']); ?></p>
        </div>
    </section>

    <!-- Order Details -->
    <section class="order-details py-5">
        <div class="container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success mb-4">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Order Details</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Order Information</h6>
                                    <p class="mb-1">Order Number: #<?php echo sprintf('%06d', $order_id); ?></p>
                                    <p class="mb-1">Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                    <p class="mb-1">Status: <span class="badge bg-success"><?php echo ucfirst($order['status']); ?></span></p>
                                    <p class="mb-0">Payment Method: <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Customer Information</h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                    <p class="mb-1"><?php echo htmlspecialchars($order['email']); ?></p>
                                    <p class="mb-0"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Shipping Address</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Billing Address</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                <td class="text-end">₹<?php echo number_format($item['price'], 2); ?></td>
                                                <td class="text-end">₹<?php echo number_format($item['subtotal'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end">Subtotal:</td>
                                            <td class="text-end">₹<?php echo number_format($subtotal, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end">Tax (7%):</td>
                                            <td class="text-end">₹<?php echo number_format($tax_amount, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end">Shipping:</td>
                                            <td class="text-end">₹<?php echo number_format($shipping, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Total:</td>
                                            <td class="text-end fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-5">
                        <a href="index.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-home me-2"></i>Return to Home
                        </a>
                        <a href="shop.php" class="btn btn-success">
                            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <section class="py-5 bg-light">
        <div class="container text-center">
            <h3 class="mb-4">Care for Your New Plants</h3>
            <p class="mb-4">Check out our plant care guides to help your new plants thrive.</p>
            <a href="#" class="btn btn-outline-success">Plant Care Guides</a>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 