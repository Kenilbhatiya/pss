<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Save current page to redirect after login
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Get user information
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_result);

// Get cart items
$cart_items = [];
$total_price = 0;

$query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image_path, p.quantity as stock_quantity 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $cart_items[] = $row;
        $total_price += $row['subtotal'];
    }
}

// Check if cart is empty
if (count($cart_items) == 0) {
    header("Location: cart.php");
    exit();
}

// Calculate taxes and shipping
$tax_rate = 0.07; // 7% tax
$tax_amount = $total_price * $tax_rate;
$shipping = 5.99; // Standard shipping
$total_with_tax_shipping = $total_price + $tax_amount + $shipping;

// Process checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // Validate form data
    $shipping_address = trim($_POST['address']) . ', ' . 
                     trim($_POST['city']) . ', ' . 
                     trim($_POST['state']) . ' ' . 
                     trim($_POST['zip_code']);
    
    $billing_address = isset($_POST['same_as_shipping']) ? $shipping_address : 
                      trim($_POST['billing_address']) . ', ' . 
                      trim($_POST['billing_city']) . ', ' . 
                      trim($_POST['billing_state']) . ' ' . 
                      trim($_POST['billing_zip_code']);
    
    $shipping_method = $_POST['shipping_method'];
    $payment_method = $_POST['payment_method'];
    
    // Check if all required fields are filled
    if (empty($_POST['address']) || empty($_POST['city']) || 
        empty($_POST['state']) || empty($_POST['pin_code'])) {
        $error_message = "Please fill in all required shipping details";
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Check stock availability and update product quantities
            $out_of_stock_items = [];
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock_quantity']) {
                    $out_of_stock_items[] = $item['name'];
                }
            }
            
            if (!empty($out_of_stock_items)) {
                throw new Exception("Some items are out of stock: " . implode(", ", $out_of_stock_items));
            }
            
            // Insert order
            $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, 
                          shipping_method, payment_method, status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $order_stmt = mysqli_prepare($conn, $order_query);
            mysqli_stmt_bind_param($order_stmt, "idssss", $user_id, $total_with_tax_shipping, 
                                 $shipping_address, $billing_address, $shipping_method, $payment_method);
            
            if (!mysqli_stmt_execute($order_stmt)) {
                throw new Exception("Failed to create order: " . mysqli_error($conn));
            }
            
            $order_id = mysqli_insert_id($conn);
            
            // Insert order items and update product quantities
            foreach ($cart_items as $item) {
                // Insert order item
                $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                   VALUES (?, ?, ?, ?)";
                $order_item_stmt = mysqli_prepare($conn, $order_item_query);
                mysqli_stmt_bind_param($order_item_stmt, "iiid", $order_id, $item['product_id'], 
                                     $item['quantity'], $item['price']);
                
                if (!mysqli_stmt_execute($order_item_stmt)) {
                    throw new Exception("Failed to add order item: " . mysqli_error($conn));
                }
                
                // Update product quantity
                $update_product_query = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                $update_product_stmt = mysqli_prepare($conn, $update_product_query);
                mysqli_stmt_bind_param($update_product_stmt, "ii", $item['quantity'], $item['product_id']);
                
                if (!mysqli_stmt_execute($update_product_stmt)) {
                    throw new Exception("Failed to update product quantity: " . mysqli_error($conn));
                }
            }
            
            // Clear the cart
            $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_cart_stmt = mysqli_prepare($conn, $clear_cart_query);
            mysqli_stmt_bind_param($clear_cart_stmt, "i", $user_id);
            
            if (!mysqli_stmt_execute($clear_cart_stmt)) {
                throw new Exception("Failed to clear cart: " . mysqli_error($conn));
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Set success message and redirect to order confirmation
            $_SESSION['success_message'] = "Your order has been placed successfully!";
            $_SESSION['order_id'] = $order_id;
            header("Location: order-confirmation.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    }
}

// Apply coupon code (placeholder for future implementation)
$coupon_code = "";
$discount = 0;
if (isset($_POST['apply_coupon']) && !empty($_POST['coupon'])) {
    $coupon_code = trim($_POST['coupon']);
    // Here you would check if the coupon is valid and calculate the discount
    // For now, we'll just set a fake discount for demonstration
    if ($coupon_code === "PLANT10") {
        $discount = $total_price * 0.10; // 10% discount
        $total_with_tax_shipping -= $discount;
        $success_message = "Coupon applied successfully!";
    } else {
        $error_message = "Invalid coupon code";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Checkout Banner -->
    <section class="checkout-banner py-5 bg-light">
        <div class="container text-center">
            <h1 class="display-4 fw-bold text-success">Checkout</h1>
            <p class="lead">Complete your purchase</p>
        </div>
    </section>

    <!-- Checkout Content -->
    <section class="checkout-content py-5">
        <div class="container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Shipping Information</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Street Address *</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="state" class="form-label">State *</label>
                                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user_data['state'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="pin_code" class="form-label">PIN Code *</label>
                                        <input type="text" class="form-control" id="pin_code" name="pin_code" value="<?php echo htmlspecialchars($user_data['pin_code'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Billing Information</h5>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="same_as_shipping" name="same_as_shipping" checked>
                                    <label class="form-check-label" for="same_as_shipping">
                                        Same as shipping address
                                    </label>
                                </div>
                            </div>
                            <div class="card-body p-4 billing-address-form d-none">
                                <div class="mb-3">
                                    <label for="billing_address" class="form-label">Street Address *</label>
                                    <input type="text" class="form-control" id="billing_address" name="billing_address">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="billing_city" name="billing_city">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="billing_state" class="form-label">State *</label>
                                        <input type="text" class="form-control" id="billing_state" name="billing_state">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="billing_pin_code" class="form-label">PIN Code *</label>
                                        <input type="text" class="form-control" id="billing_pin_code" name="billing_pin_code">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Shipping Method</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="standard_shipping" value="standard" checked>
                                    <label class="form-check-label" for="standard_shipping">
                                        <span class="d-flex justify-content-between">
                                            <span><strong>Standard Shipping</strong> (2-5 business days)</span>
                                            <span>$5.99</span>
                                        </span>
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="shipping_method" id="express_shipping" value="express">
                                    <label class="form-check-label" for="express_shipping">
                                        <span class="d-flex justify-content-between">
                                            <span><strong>Express Shipping</strong> (1-2 business days)</span>
                                            <span>$12.99</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Payment Method</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                    <label class="form-check-label" for="credit_card">
                                        <span class="d-flex align-items-center">
                                            <span class="me-2">Credit Card</span>
                                            <span>
                                                <i class="fab fa-cc-visa me-1"></i>
                                                <i class="fab fa-cc-mastercard me-1"></i>
                                                <i class="fab fa-cc-amex me-1"></i>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                
                                <div id="credit_card_details">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="card_name" class="form-label">Name on Card *</label>
                                            <input type="text" class="form-control" id="card_name" name="card_name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="card_number" class="form-label">Card Number *</label>
                                            <input type="text" class="form-control" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="expiry_month" class="form-label">Expiry Month *</label>
                                            <select class="form-select" id="expiry_month" name="expiry_month" required>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="expiry_year" class="form-label">Expiry Year *</label>
                                            <select class="form-select" id="expiry_year" name="expiry_year" required>
                                                <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="cvv" class="form-label">CVV *</label>
                                            <input type="text" class="form-control" id="cvv" name="cvv" placeholder="XXX" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                    <label class="form-check-label" for="paypal">
                                        <span class="d-flex align-items-center">
                                            <span class="me-2">PayPal</span>
                                            <i class="fab fa-paypal"></i>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h5 class="mb-3">Order Notes</h5>
                                <div class="mb-3">
                                    <textarea class="form-control" id="order_notes" name="order_notes" rows="3" placeholder="Notes about your order, e.g. special instructions for delivery"></textarea>
                                </div>
                            </div>
                        </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 2rem;">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div>
                                                <span class="d-block"><?php echo htmlspecialchars($item['name']); ?></span>
                                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                            </div>
                                        </div>
                                        <span>₹<?php echo number_format($item['subtotal'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>₹<?php echo number_format($total_price, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (7%)</span>
                                <span>₹<?php echo number_format($tax_amount, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span>₹<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            
                            <?php if ($discount > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Discount</span>
                                    <span>-₹<?php echo number_format($discount, 2); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total</strong>
                                <strong>₹<?php echo number_format($total_with_tax_shipping, 2); ?></strong>
                            </div>
                            
                            <div class="coupon mb-3">
                                <label for="coupon" class="form-label">Have a coupon?</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="coupon" name="coupon" placeholder="Enter coupon code" value="<?php echo htmlspecialchars($coupon_code); ?>">
                                    <button class="btn btn-outline-success" type="submit" name="apply_coupon">Apply</button>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="place_order" class="btn btn-success btn-lg">Place Order</button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">By placing your order, you agree to our <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>.</small>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle billing address form
        document.getElementById('same_as_shipping').addEventListener('change', function() {
            const billingForm = document.querySelector('.billing-address-form');
            if (this.checked) {
                billingForm.classList.add('d-none');
            } else {
                billingForm.classList.remove('d-none');
            }
        });
        
        // Toggle payment method details
        document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const creditCardDetails = document.getElementById('credit_card_details');
                if (this.value === 'credit_card') {
                    creditCardDetails.classList.remove('d-none');
                } else {
                    creditCardDetails.classList.add('d-none');
                }
            });
        });
        
        // Update shipping cost based on method
        document.querySelectorAll('input[name="shipping_method"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                // This would be replaced with an AJAX call to update the order total
                // For now it's just a placeholder
                console.log('Shipping method changed to: ' + this.value);
            });
        });
    </script>
</body>
</html> 