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
$success_message = "";
$error_message = "";

// Get user information
$user = [];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    } else {
        // Invalid user ID, logout and redirect
        session_destroy();
        header("Location: login.php");
        exit();
    }
} else {
    // Database error
    $error_message = "Database error: " . mysqli_error($conn);
}

// Process profile update form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validation
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } elseif ($email != $user['email']) {
        // Check if new email exists for another user
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "Email already exists. Please use another one.";
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssssi", $first_name, $last_name, $email, $phone, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Profile updated successfully!";
            // Refresh user data
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Process password change form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error_message = "Current password is incorrect";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } else {
        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $password_hash, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Password changed successfully!";
        } else {
            $error_message = "Failed to change password. Please try again.";
        }
    }
}

// Get user orders
$orders = [];
$orders_query = "SELECT o.*, COUNT(oi.id) as item_count 
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.user_id = ? 
                GROUP BY o.id 
                ORDER BY o.created_at DESC";
$orders_stmt = mysqli_prepare($conn, $orders_query);
if ($orders_stmt) {
    mysqli_stmt_bind_param($orders_stmt, "i", $user_id);
    mysqli_stmt_execute($orders_stmt);
    $orders_result = mysqli_stmt_get_result($orders_stmt);
    
    if ($orders_result) {
        while ($row = mysqli_fetch_assoc($orders_result)) {
            $orders[] = $row;
        }
    }
}

// Get user wishlist items
$wishlist_items = [];
$wishlist_query = "SELECT w.id as wishlist_id, p.* 
                  FROM wishlist w 
                  JOIN products p ON w.product_id = p.id 
                  WHERE w.user_id = ? 
                  ORDER BY w.created_at DESC";
$wishlist_stmt = mysqli_prepare($conn, $wishlist_query);
if ($wishlist_stmt) {
    mysqli_stmt_bind_param($wishlist_stmt, "i", $user_id);
    mysqli_stmt_execute($wishlist_stmt);
    $wishlist_result = mysqli_stmt_get_result($wishlist_stmt);
    
    if ($wishlist_result) {
        while ($row = mysqli_fetch_assoc($wishlist_result)) {
            $wishlist_items[] = $row;
        }
    }
}

// Get user addresses
$addresses = [];
$addresses_query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
$addresses_stmt = mysqli_prepare($conn, $addresses_query);
if ($addresses_stmt) {
    mysqli_stmt_bind_param($addresses_stmt, "i", $user_id);
    mysqli_stmt_execute($addresses_stmt);
    $addresses_result = mysqli_stmt_get_result($addresses_stmt);
    
    if ($addresses_result) {
        while ($row = mysqli_fetch_assoc($addresses_result)) {
            $addresses[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Account Banner -->
    <section class="account-banner py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-5 fw-bold text-success">My Account</h1>
                    <p class="lead">Manage your profile, orders, and wishlist</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Welcome back, <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></p>
                    <p class="text-muted">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Account Content -->
    <section class="account-content py-5">
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
                <!-- Account Navigation -->
                <div class="col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="avatar-circle bg-success text-white mx-auto mb-3">
                                    <span><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                                </div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            
                            <div class="list-group list-group-flush nav-tabs" id="accountTab" role="tablist">
                                <a class="list-group-item list-group-item-action active" id="dashboard-tab" data-bs-toggle="list" href="#dashboard" role="tab" aria-controls="dashboard" aria-selected="true">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                <a class="list-group-item list-group-item-action" id="orders-tab" data-bs-toggle="list" href="#orders" role="tab" aria-controls="orders" aria-selected="false">
                                    <i class="fas fa-shopping-bag me-2"></i> Orders
                                </a>
                                <a class="list-group-item list-group-item-action" id="wishlist-tab" data-bs-toggle="list" href="#wishlist" role="tab" aria-controls="wishlist" aria-selected="false">
                                    <i class="fas fa-heart me-2"></i> Wishlist
                                </a>
                                <a class="list-group-item list-group-item-action" id="addresses-tab" data-bs-toggle="list" href="#addresses" role="tab" aria-controls="addresses" aria-selected="false">
                                    <i class="fas fa-map-marker-alt me-2"></i> Addresses
                                </a>
                                <a class="list-group-item list-group-item-action" id="profile-tab" data-bs-toggle="list" href="#profile" role="tab" aria-controls="profile" aria-selected="false">
                                    <i class="fas fa-user me-2"></i> Profile Details
                                </a>
                                <a class="list-group-item list-group-item-action" id="change-password-tab" data-bs-toggle="list" href="#change-password" role="tab" aria-controls="change-password" aria-selected="false">
                                    <i class="fas fa-lock me-2"></i> Change Password
                                </a>
                            </div>
                            
                            <div class="mt-4">
                                <a href="logout.php" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Content -->
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="tab-content" id="accountTabContent">
                                <!-- Dashboard Tab -->
                                <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                                    <h3 class="mb-4">Account Dashboard</h3>
                                    
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0">
                                                <div class="card-body text-center">
                                                    <div class="icon-circle bg-success text-white mx-auto mb-3">
                                                        <i class="fas fa-shopping-bag"></i>
                                                    </div>
                                                    <h5><?php echo count($orders); ?></h5>
                                                    <p class="mb-0">Total Orders</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0">
                                                <div class="card-body text-center">
                                                    <div class="icon-circle bg-success text-white mx-auto mb-3">
                                                        <i class="fas fa-heart"></i>
                                                    </div>
                                                    <h5><?php echo count($wishlist_items); ?></h5>
                                                    <p class="mb-0">Wishlist Items</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0">
                                                <div class="card-body text-center">
                                                    <div class="icon-circle bg-success text-white mx-auto mb-3">
                                                        <i class="fas fa-truck"></i>
                                                    </div>
                                                    <?php 
                                                    $pending_orders = 0;
                                                    foreach ($orders as $order) {
                                                        if ($order['status'] == 'pending' || $order['status'] == 'processing' || $order['status'] == 'shipped') {
                                                            $pending_orders++;
                                                        }
                                                    }
                                                    ?>
                                                    <h5><?php echo $pending_orders; ?></h5>
                                                    <p class="mb-0">Pending Orders</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (count($orders) > 0): ?>
                                        <h5 class="mb-3">Recent Orders</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Order #</th>
                                                        <th>Date</th>
                                                        <th>Items</th>
                                                        <th>Total</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                                                        <tr>
                                                            <td>#<?php echo $order['id']; ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                            <td><?php echo $order['item_count']; ?></td>
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
                                                                    View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php if (count($orders) > 3): ?>
                                            <div class="text-end">
                                                <a href="#orders" class="btn btn-sm btn-outline-success" data-bs-toggle="list" role="tab" aria-controls="orders">
                                                    View All Orders
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            You haven't placed any orders yet. <a href="shop.php" class="alert-link">Start shopping</a> to see your orders here.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Orders Tab -->
                                <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                                    <h3 class="mb-4">My Orders</h3>
                                    
                                    <?php if (count($orders) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Order #</th>
                                                        <th>Date</th>
                                                        <th>Items</th>
                                                        <th>Total</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($orders as $order): ?>
                                                        <tr>
                                                            <td>#<?php echo $order['id']; ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                            <td><?php echo $order['item_count']; ?></td>
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
                                                                    View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            You haven't placed any orders yet. <a href="shop.php" class="alert-link">Start shopping</a> to see your orders here.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Wishlist Tab -->
                                <div class="tab-pane fade" id="wishlist" role="tabpanel" aria-labelledby="wishlist-tab">
                                    <h3 class="mb-4">My Wishlist</h3>
                                    
                                    <?php if (count($wishlist_items) > 0): ?>
                                        <div class="row">
                                            <?php foreach ($wishlist_items as $item): ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card h-100 product-card">
                                                        <img src="<?php echo $item['image_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                            <p class="card-text text-success fw-bold">₹<?php echo number_format($item['price'], 2); ?></p>
                                                            <div class="d-flex justify-content-between">
                                                                <a href="product.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-secondary">Details</a>
                                                                <a href="add_to_cart.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">Add to Cart</a>
                                                            </div>
                                                        </div>
                                                        <div class="card-footer bg-white border-top-0">
                                                            <a href="remove_from_wishlist.php?id=<?php echo $item['wishlist_id']; ?>" class="btn btn-sm btn-outline-danger w-100">
                                                                <i class="fas fa-trash me-1"></i> Remove from Wishlist
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            Your wishlist is empty. <a href="shop.php" class="alert-link">Browse our products</a> and add items to your wishlist.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Addresses Tab -->
                                <div class="tab-pane fade" id="addresses" role="tabpanel" aria-labelledby="addresses-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3>My Addresses</h3>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                            <i class="fas fa-plus me-1"></i> Add New Address
                                        </button>
                                    </div>
                                    
                                    <?php if (count($addresses) > 0): ?>
                                        <div class="row">
                                            <?php foreach ($addresses as $address): ?>
                                                <div class="col-lg-6 mb-4">
                                                    <div class="card h-100 border <?php echo $address['is_default'] ? 'border-success' : ''; ?>">
                                                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                                            <span>
                                                                <i class="fas <?php 
                                                                    switch($address['address_type']) {
                                                                        case 'home': echo 'fa-home'; break;
                                                                        case 'work': echo 'fa-briefcase'; break;
                                                                        default: echo 'fa-map-marker-alt';
                                                                    }
                                                                ?> me-2"></i>
                                                                <?php echo ucfirst($address['address_type']); ?> Address
                                                                <?php if ($address['is_default']): ?>
                                                                    <span class="badge bg-success ms-2">Default</span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        <div class="card-body">
                                                            <address class="mb-0">
                                                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong><br>
                                                                <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                                                <?php if (!empty($address['address_line2'])): ?>
                                                                    <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                                                <?php endif; ?>
                                                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['zip_code']); ?><br>
                                                                <?php echo htmlspecialchars($address['country']); ?>
                                                            </address>
                                                        </div>
                                                        <div class="card-footer bg-transparent">
                                                            <div class="btn-group w-100">
                                                                <button type="button" class="btn btn-sm btn-outline-primary edit-address-btn" 
                                                                        data-id="<?php echo $address['id']; ?>"
                                                                        data-line1="<?php echo htmlspecialchars($address['address_line1']); ?>"
                                                                        data-line2="<?php echo htmlspecialchars($address['address_line2']); ?>"
                                                                        data-city="<?php echo htmlspecialchars($address['city']); ?>"
                                                                        data-state="<?php echo htmlspecialchars($address['state']); ?>"
                                                                        data-zip="<?php echo htmlspecialchars($address['zip_code']); ?>"
                                                                        data-country="<?php echo htmlspecialchars($address['country']); ?>"
                                                                        data-type="<?php echo htmlspecialchars($address['address_type']); ?>"
                                                                        data-default="<?php echo $address['is_default']; ?>"
                                                                        data-bs-toggle="modal" data-bs-target="#editAddressModal">
                                                                    <i class="fas fa-edit me-1"></i> Edit
                                                                </button>
                                                                <?php if (!$address['is_default']): ?>
                                                                    <a href="set_default_address.php?id=<?php echo $address['id']; ?>" class="btn btn-sm btn-outline-success">
                                                                        Set as Default
                                                                    </a>
                                                                <?php endif; ?>
                                                                <?php if (count($addresses) > 1): ?>
                                                                    <a href="delete_address.php?id=<?php echo $address['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this address?');">
                                                                        <i class="fas fa-trash me-1"></i> Delete
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <p>You haven't added any addresses yet. Add a new address to manage your shipping options.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Profile Tab -->
                                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                    <h3 class="mb-4">Profile Details</h3>
                                    
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled readonly>
                                                <div class="form-text">Username cannot be changed</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                                    </form>
                                </div>
                                
                                <!-- Change Password Tab -->
                                <div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
                                    <h3 class="mb-4">Change Password</h3>
                                    
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="form-text">Password must be at least 6 characters long</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" name="change_password" class="btn btn-success">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_address.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_address_line1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="new_address_line1" name="address_line1" placeholder="Street address, P.O. box, company name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_address_line2" class="form-label">Address Line 2 <span class="text-muted">(Optional)</span></label>
                            <input type="text" class="form-control" id="new_address_line2" name="address_line2" placeholder="Apartment, suite, unit, building, floor, etc.">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="new_city" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="new_state" name="state" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_zip_code" class="form-label">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="new_zip_code" name="zip_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="new_country" name="country" value="India">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_address_type" class="form-label">Address Type</label>
                                <select class="form-select" id="new_address_type" name="address_type">
                                    <option value="home">Home</option>
                                    <option value="work">Work</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="new_is_default" name="is_default" value="1" <?php echo count($addresses) == 0 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="new_is_default">
                                        Set as default address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAddressModalLabel">Edit Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="update_address.php" method="POST">
                    <input type="hidden" id="edit_address_id" name="address_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_address_line1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="edit_address_line1" name="address_line1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address_line2" class="form-label">Address Line 2 <span class="text-muted">(Optional)</span></label>
                            <input type="text" class="form-control" id="edit_address_line2" name="address_line2">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="edit_city" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="edit_state" name="state" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_zip_code" class="form-label">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="edit_zip_code" name="zip_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="edit_country" name="country">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_address_type" class="form-label">Address Type</label>
                                <select class="form-select" id="edit_address_type" name="address_type">
                                    <option value="home">Home</option>
                                    <option value="work">Work</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default" value="1">
                                    <label class="form-check-label" for="edit_is_default">
                                        Set as default address
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript for address editing -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit address button clicks
            const editButtons = document.querySelectorAll('.edit-address-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const line1 = this.getAttribute('data-line1');
                    const line2 = this.getAttribute('data-line2');
                    const city = this.getAttribute('data-city');
                    const state = this.getAttribute('data-state');
                    const zip = this.getAttribute('data-zip');
                    const country = this.getAttribute('data-country');
                    const type = this.getAttribute('data-type');
                    const isDefault = this.getAttribute('data-default') === '1';
                    
                    // Populate the edit form
                    document.getElementById('edit_address_id').value = id;
                    document.getElementById('edit_address_line1').value = line1;
                    document.getElementById('edit_address_line2').value = line2 || '';
                    document.getElementById('edit_city').value = city;
                    document.getElementById('edit_state').value = state;
                    document.getElementById('edit_zip_code').value = zip;
                    document.getElementById('edit_country').value = country;
                    document.getElementById('edit_address_type').value = type;
                    document.getElementById('edit_is_default').checked = isDefault;
                    
                    // If address is already default, disable the checkbox
                    if (isDefault) {
                        document.getElementById('edit_is_default').disabled = true;
                    } else {
                        document.getElementById('edit_is_default').disabled = false;
                    }
                });
            });
        });
    </script>
    
    <!-- Custom CSS for My Account page -->
    <style>
        .avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
        }
        
        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
    </style>
</body>
</html> 