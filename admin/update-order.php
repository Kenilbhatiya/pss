<?php
// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);
$success_message = "";
$error_message = "";

// Get order details
$order_query = "SELECT * FROM orders WHERE id = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $order = mysqli_fetch_assoc($result);
} else {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.image_path 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$items = [];
if ($items_result) {
    while ($row = mysqli_fetch_assoc($items_result)) {
        $items[] = $row;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order'])) {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $tracking_number = mysqli_real_escape_string($conn, $_POST['tracking_number']);
    $shipping_method = mysqli_real_escape_string($conn, $_POST['shipping_method']);
    $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
    
    // Update order
    $update_query = "UPDATE orders SET 
                    status = ?, 
                    tracking_number = ?, 
                    shipping_method = ?,
                    delivery_date = ? 
                    WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ssssi", $status, $tracking_number, $shipping_method, $delivery_date, $order_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $success_message = "Order updated successfully!";
        
        // Refresh order data
        $result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id");
        $order = mysqli_fetch_assoc($result);
        
        // Notify customer by email (placeholder for email functionality)
        // This would need to be implemented with a proper email service
        if ($status == 'shipped' && !empty($tracking_number)) {
            // Placeholder for email notification code
            // sendOrderUpdateEmail($order['user_id'], $order_id, $status, $tracking_number);
        }
    } else {
        $error_message = "Failed to update order: " . mysqli_error($conn);
    }
}

// Define shipping carriers for dropdown
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
    <title>Update Order #<?php echo $order_id; ?> - Admin Dashboard</title>
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
                        <li class="breadcrumb-item active" aria-current="page">Update Order #<?php echo $order_id; ?></li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Update Order #<?php echo $order_id; ?></h1>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
                    </a>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Order Update Form -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">Update Order Status</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="update-order-form">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Order Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="delivery_date" class="form-label">Delivery Date</label>
                                        <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                            value="<?php echo date('Y-m-d', strtotime($order['delivery_date'])); ?>">
                                        <small class="form-text text-muted">The expected delivery date to show to the customer</small>
                                    </div>
                                    
                                    <div class="mb-3 shipping-fields" <?php echo ($order['status'] != 'shipped' && $order['status'] != 'delivered') ? 'style="display:none;"' : ''; ?>>
                                        <label for="shipping_method" class="form-label">Shipping Method</label>
                                        <select class="form-select" id="shipping_method" name="shipping_method">
                                            <option value="">Select Shipping Carrier</option>
                                            <?php foreach ($shipping_carriers as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo $order['shipping_method'] == $value ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3 shipping-fields" <?php echo ($order['status'] != 'shipped' && $order['status'] != 'delivered') ? 'style="display:none;"' : ''; ?>>
                                        <label for="tracking_number" class="form-label">Tracking Number</label>
                                        <input type="text" class="form-control" id="tracking_number" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_order" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Update Order
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">Customer Information</h6>
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></p>
                                    <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                    <p><strong>Expected Delivery:</strong> 
                                        <?php 
                                        if (!empty($order['delivery_date'])) {
                                            echo date('F j, Y', strtotime($order['delivery_date']));
                                        } else {
                                            // If delivery date is not set, show estimated date
                                            echo date('F j, Y', strtotime($order['created_at'] . ' + 3 days'));
                                            echo ' <small class="text-muted">(Estimated)</small>';
                                        }
                                        ?>
                                    </p>
                                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                </div>
                                
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">Shipping Address</h6>
                                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                                
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">Order Items</h6>
                                    <?php foreach ($items as $item): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="me-2">
                                                <img src="<?php echo '../' . $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" width="50" height="50" class="img-thumbnail">
                                            </div>
                                            <div>
                                                <p class="mb-0"><strong><?php echo htmlspecialchars($item['name']); ?></strong></p>
                                                <small>
                                                    <?php echo $item['quantity']; ?> x ₹<?php echo number_format($item['price'], 2); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div>
                                    <h6 class="text-muted mb-2">Total Amount</h6>
                                    <h4>₹<?php echo number_format($order['total_amount'], 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($order['tracking_number']) && ($order['status'] == 'shipped' || $order['status'] == 'delivered')): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Tracking Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Shipping Method:</strong> <?php echo $shipping_carriers[$order['shipping_method']] ?? $order['shipping_method']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                                </div>
                            </div>
                            
                            <div class="mt-3">
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
                                
                                <a href="<?php echo $tracking_url; ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-truck me-1"></i> Track Package
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show/hide shipping fields based on status
            const statusSelect = document.getElementById('status');
            const shippingFields = document.querySelectorAll('.shipping-fields');
            
            statusSelect.addEventListener('change', function() {
                const showFields = this.value === 'shipped' || this.value === 'delivered';
                
                shippingFields.forEach(field => {
                    field.style.display = showFields ? 'block' : 'none';
                });
                
                // Make fields required only when shown
                if (showFields) {
                    document.getElementById('shipping_method').setAttribute('required', 'required');
                    document.getElementById('tracking_number').setAttribute('required', 'required');
                } else {
                    document.getElementById('shipping_method').removeAttribute('required');
                    document.getElementById('tracking_number').removeAttribute('required');
                }
            });
        });
    </script>
</body>
</html> 