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

// Check if order ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$order_query = "SELECT o.*,
                COALESCE(u.username, o.username) as customer_name,
                u.email as customer_email, u.phone as customer_phone
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if($result && mysqli_num_rows($result) > 0) {
    $order = mysqli_fetch_assoc($result);
} else {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.image_path, p.id as product_id
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$items = [];
if($items_result) {
    while($row = mysqli_fetch_assoc($items_result)) {
        $items[] = $row;
    }
}

// Define shipping carriers
$shipping_carriers = [
    "fedex" => "FedEx",
    "ups" => "UPS",
    "usps" => "USPS",
    "dhl" => "DHL",
    "bluedart" => "BlueDart",
    "dtdc" => "DTDC",
    "delhivery" => "Delhivery",
    "other" => "Other"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details - Admin Dashboard</title>
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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="orders.php">Orders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Order #<?php echo $order_id; ?></li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Order #<?php echo $order_id; ?> Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="update-order.php?id=<?php echo $order_id; ?>" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-edit"></i> Edit Order
                        </a>
                        <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Order Info -->
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Order Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Order Date:</span>
                                    <strong><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Order Status:</span>
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
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Payment Method:</span>
                                    <strong><?php echo ucfirst($order['payment_method']); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Payment Status:</span>
                                    <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Expected Delivery:</span>
                                    <strong>
                                        <?php 
                                        if (!empty($order['delivery_date'])) {
                                            echo date('F j, Y', strtotime($order['delivery_date']));
                                        } else {
                                            // If delivery date is not set, show estimated date
                                            echo date('F j, Y', strtotime($order['created_at'] . ' + 3 days'));
                                            echo ' <small class="text-muted">(Est.)</small>';
                                        }
                                        ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Info -->
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Name:</span>
                                    <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Email:</span>
                                    <strong><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Phone:</span>
                                    <strong><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></strong>
                                </div>
                                <hr>
                                <h6 class="mb-3">Shipping Address</h6>
                                <address>
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </address>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment & Shipping Info -->
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Payment & Shipping</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Subtotal:</span>
                                    <strong>₹<?php echo number_format($order['total_amount'] - $order['shipping_fee'], 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Shipping Fee:</span>
                                    <strong>₹<?php echo number_format($order['shipping_fee'], 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Total Amount:</span>
                                    <strong class="text-primary">₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </div>
                                
                                <?php if (!empty($order['tracking_number']) && ($order['status'] == 'shipped' || $order['status'] == 'delivered')): ?>
                                    <hr>
                                    <h6 class="mb-3">Shipping Information</h6>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted">Carrier:</span>
                                        <strong><?php echo $shipping_carriers[$order['shipping_method']] ?? $order['shipping_method']; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted">Tracking Number:</span>
                                        <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong>
                                    </div>
                                    <?php 
                                    $tracking_url = '';
                                    switch($order['shipping_method']) {
                                        case 'fedex':
                                            $tracking_url = "https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=" . urlencode($order['tracking_number']);
                                            break;
                                        case 'ups':
                                            $tracking_url = "https://www.ups.com/track?tracknum=" . urlencode($order['tracking_number']);
                                            break;
                                        case 'usps':
                                            $tracking_url = "https://tools.usps.com/go/TrackConfirmAction?tLabels=" . urlencode($order['tracking_number']);
                                            break;
                                        case 'dhl':
                                            $tracking_url = "https://www.dhl.com/en/express/tracking.html?AWB=" . urlencode($order['tracking_number']);
                                            break;
                                        case 'bluedart':
                                            $tracking_url = "https://www.bluedart.com/tracking/" . urlencode($order['tracking_number']);
                                            break;
                                        case 'dtdc':
                                            $tracking_url = "https://tracking.dtdc.com/tracking/shipment-tracking/" . urlencode($order['tracking_number']);
                                            break;
                                        case 'delhivery':
                                            $tracking_url = "https://www.delhivery.com/track/#/package/" . urlencode($order['tracking_number']);
                                            break;
                                        default:
                                            $tracking_url = "#";
                                    }
                                    ?>
                                    <div class="mt-3">
                                        <a href="<?php echo $tracking_url; ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                            <i class="fas fa-truck me-1"></i> Track Package
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80">Image</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($items) > 0): ?>
                                        <?php foreach($items as $item): ?>
                                            <tr>
                                                <td>
                                                    <img src="../<?php echo $item['image_path'] ?: 'images/products/default.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail" width="60">
                                                </td>
                                                <td>
                                                    <a href="product-details.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </td>
                                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No items found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end">Subtotal:</td>
                                        <td>₹<?php echo number_format($order['total_amount'] - $order['shipping_fee'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">Shipping Fee:</td>
                                        <td>₹<?php echo number_format($order['shipping_fee'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total:</td>
                                        <td class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Order Actions -->
                <div class="d-flex justify-content-end mb-4">
                    <a href="orders.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
                    </a>
                    <a href="update-order.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Update Order
                    </a>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 