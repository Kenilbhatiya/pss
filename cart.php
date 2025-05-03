<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Save current page to redirect after login
    $_SESSION['redirect_after_login'] = 'cart.php';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Process cart updates (quantity change or item removal)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_cart'])) {
        // Update quantities
        foreach ($_POST['quantity'] as $cart_id => $quantity) {
            $quantity = intval($quantity);
            if ($quantity > 0) {
                $query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $user_id);
                mysqli_stmt_execute($stmt);
            } else {
                // If quantity is 0 or negative, remove the item
                $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
                mysqli_stmt_execute($stmt);
            }
        }
        $success_message = "Cart updated successfully!";
    } elseif (isset($_POST['remove_item']) && isset($_POST['cart_id'])) {
        // Remove specific item
        $cart_id = intval($_POST['cart_id']);
        $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Item removed from cart!";
        } else {
            $error_message = "Failed to remove item. Please try again.";
        }
    } elseif (isset($_POST['clear_cart'])) {
        // Clear entire cart
        $query = "DELETE FROM cart WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Cart cleared successfully!";
        } else {
            $error_message = "Failed to clear cart. Please try again.";
        }
    }
}

// Get cart items
$cart_items = [];
$total_price = 0;

$query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image_path 
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

// Calculate taxes and shipping
$tax_rate = 0.07; // 7% tax
$tax_amount = $total_price * $tax_rate;
$shipping = ($total_price > 0) ? 5.99 : 0; // $5.99 shipping fee if cart is not empty
$total_with_tax_shipping = $total_price + $tax_amount + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Cart Banner -->
    <section class="cart-banner py-5 bg-light">
        <div class="container text-center">
            <h1 class="display-4 fw-bold text-success">Your Shopping Cart</h1>
            <p class="lead">Review your items and proceed to checkout</p>
        </div>
    </section>

    <!-- Cart Content -->
    <section class="cart-content py-5">
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
            
            <?php if (count($cart_items) > 0): ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Subtotal</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cart_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                            </div>
                                                        </td>
                                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                        <td>
                                                            <div class="input-group" style="width: 120px;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm quantity-btn" data-action="decrease"><i class="fas fa-minus"></i></button>
                                                                <input type="number" name="quantity[<?php echo $item['id']; ?>]" class="form-control form-control-sm text-center quantity-input" value="<?php echo $item['quantity']; ?>" min="0">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm quantity-btn" data-action="increase"><i class="fas fa-plus"></i></button>
                                                            </div>
                                                        </td>
                                                        <td>₹<?php echo number_format($item['subtotal'], 2); ?></td>
                                                        <td>
                                                            <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger" onclick="document.querySelector('input[name=\'cart_id\']').value = <?php echo $item['id']; ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <input type="hidden" name="cart_id" value="">
                                    <div class="d-flex justify-content-between mt-3">
                                        <a href="shop.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                                        </a>
                                        <div>
                                            <button type="submit" name="clear_cart" class="btn btn-outline-danger me-2">Clear Cart</button>
                                            <button type="submit" name="update_cart" class="btn btn-success">Update Cart</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0">Order Summary</h5>
                                </div>
                                <div class="card-body p-4">
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
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>Total</strong>
                                        <strong>₹<?php echo number_format($total_with_tax_shipping, 2); ?></strong>
                                    </div>
                                    <div class="coupon mb-3">
                                        <label for="coupon" class="form-label">Have a coupon?</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="coupon" placeholder="Enter coupon code">
                                            <button class="btn btn-outline-success" type="button">Apply</button>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="checkout.php" class="btn btn-success btn-lg">Proceed to Checkout</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="mb-3">We Accept</h5>
                                    <div class="payment-methods">
                                        <i class="fab fa-cc-visa me-2 fs-3"></i>
                                        <i class="fab fa-cc-mastercard me-2 fs-3"></i>
                                        <i class="fab fa-cc-amex me-2 fs-3"></i>
                                        <i class="fab fa-cc-paypal fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                        <h2 class="mb-3">Your Cart is Empty</h2>
                        <p class="mb-4">Looks like you haven't added any plants to your cart yet.</p>
                        <a href="shop.php" class="btn btn-success btn-lg">Start Shopping</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Related Products -->
    <section class="related-products py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">You Might Also Like</h2>
            <div class="row">
                <?php
                // Get some featured products as recommendations
                $query = "SELECT id, name, price, image_path FROM products WHERE featured = 1 LIMIT 4";
                $result = mysqli_query($conn, $query);
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($product = mysqli_fetch_assoc($result)) {
                ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 product-card">
                            <img src="<?php echo $product['image_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-success fw-bold">₹<?php echo number_format($product['price'], 2); ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-secondary">Details</a>
                                    <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-success">Add to Cart</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript for Quantity Buttons -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityBtns = document.querySelectorAll('.quantity-btn');
            
            quantityBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.parentNode.querySelector('.quantity-input');
                    const currentValue = parseInt(input.value);
                    
                    if (this.dataset.action === 'increase') {
                        input.value = currentValue + 1;
                    } else if (this.dataset.action === 'decrease' && currentValue > 0) {
                        input.value = currentValue - 1;
                    }
                });
            });
        });
    </script>
</body>
</html> 