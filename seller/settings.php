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

// Get seller ID from session
$seller_id = $_SESSION['seller_id'];

// Debug database connection
$debug = [];
$debug[] = "Seller ID from session: " . $seller_id;

if(!$conn) {
    $debug[] = "Database connection failed: " . mysqli_connect_error();
}

// Check if sellers table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'sellers'");
if($tableExists->num_rows == 0) {
    $debug[] = "Creating sellers table...";
    // Create sellers table
    $createTable = "CREATE TABLE sellers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        store_name VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        status TINYINT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $result = $conn->query($createTable);
    if(!$result) {
        $debug[] = "Error creating sellers table: " . $conn->error;
    } else {
        $debug[] = "Sellers table created successfully";
    }
    
    // Update the seller's record with the user_id
    $insertSeller = "INSERT INTO sellers (id, username, password, email, status) 
                     SELECT id, username, password, email, status FROM users 
                     WHERE id = ? AND role = 'seller'";
    $stmt = $conn->prepare($insertSeller);
    $stmt->bind_param("i", $seller_id);
    $result = $stmt->execute();
    if(!$result) {
        $debug[] = "Error inserting seller data: " . $stmt->error;
    } else {
        $debug[] = "Seller data inserted successfully";
    }
}

// Get user info as fallback
$user_query = "SELECT username, email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $seller_id);
$result = $user_stmt->execute();
if(!$result) {
    $debug[] = "Error fetching user data: " . $user_stmt->error;
}
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
if(!$user) {
    $debug[] = "No user found with ID: " . $seller_id;
} else {
    $debug[] = "User found: " . $user['username'];
}

// Get current seller info
$seller = [];
$stmt = $conn->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->bind_param("i", $seller_id);
$result = $stmt->execute();
if(!$result) {
    $debug[] = "Error fetching seller data: " . $stmt->error;
}
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $seller = $result->fetch_assoc();
    $debug[] = "Seller data found for ID: " . $seller_id;
    $debug[] = "Store Name: " . ($seller['store_name'] ?? 'Not set');
    $debug[] = "Email: " . ($seller['email'] ?? 'Not set');
} else {
    $debug[] = "No seller data found for ID: " . $seller_id;
    
    // If seller record doesn't exist, create it
    $insertSeller = "INSERT INTO sellers (id, username, email) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insertSeller);
    $insert_stmt->bind_param("iss", $seller_id, $user['username'], $user['email']);
    $result = $insert_stmt->execute();
    if(!$result) {
        $debug[] = "Error creating seller record: " . $insert_stmt->error;
    } else {
        $debug[] = "Seller record created successfully";
    }
    
    // Use user data as fallback
    $seller = [
        'id' => $seller_id,
        'username' => $user['username'] ?? '',
        'email' => $user['email'] ?? '',
        'store_name' => '',
        'phone' => '',
        'address' => ''
    ];
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        $store_name = $_POST['store_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        $debug[] = "Form submitted - Store Name: $store_name, Email: $email";
        
        // Update seller info
        $query = "UPDATE sellers SET store_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
        $debug[] = "SQL Query: $query";
        
        $stmt = $conn->prepare($query);
        if(!$stmt) {
            $debug[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("ssssi", $store_name, $email, $phone, $address, $seller_id);
            $result = $stmt->execute();
            
            if(!$result) {
                $debug[] = "Error updating seller info: " . $stmt->error;
            } else {
                $debug[] = "Seller info updated successfully";
                $success_message = "Settings updated successfully!";
                
                // Update local data
                $seller['store_name'] = $store_name;
                $seller['email'] = $email;
                $seller['phone'] = $phone;
                $seller['address'] = $address;
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password match
        if ($new_password !== $confirm_password) {
            $password_error = "New passwords do not match";
        } else {
            // Verify current password from users table
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $seller_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if (password_verify($current_password, $user_data['password'])) {
                // Update password in both tables
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update users table
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $seller_id);
                $stmt->execute();
                
                // Update sellers table
                $stmt = $conn->prepare("UPDATE sellers SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $seller_id);
                $stmt->execute();
                
                $password_success = "Password updated successfully";
            } else {
                $password_error = "Current password is incorrect";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Settings - Plant Nursery</title>
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
                    <h1 class="h2">Seller Settings</h1>
                </div>
                
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($debug)): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h5>Debug Information</h5>
                    <ul>
                        <?php foreach ($debug as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- General Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Store Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="store_name" class="form-label">Store Name:</label>
                                <input type="text" class="form-control" id="store_name" name="store_name" value="<?php echo htmlspecialchars($seller['store_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($seller['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone:</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($seller['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address:</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($seller['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>

                <!-- Password Change Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger"><?php echo $password_error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($password_success)): ?>
                            <div class="alert alert-success"><?php echo $password_success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label class="form-label">Current Password:</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password:</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password:</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 