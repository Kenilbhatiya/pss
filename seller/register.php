<?php
// Start session
session_start();

// Include database connection
include_once("../includes/db_connection.php");

// Check if admin registration is allowed - this adds security by requiring a secret code
// defined in the database or a config file
$allow_registration = false;
$register_code = "PLANTADMIN2025"; // This should ideally be stored in a config file or database
$submitted_code = "";

// Initialize variables
$username = "";
$email = "";
$first_name = "";
$last_name = "";
$password = "";
$confirm_password = "";
$admin_code = "";
$store_name = "";
$address = "";
$phone = "";
$errors = [];
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_code = trim($_POST['admin_code']);
    $store_name = trim($_POST['store_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    
    // Validate admin code
    if ($admin_code !== $register_code) {
        $errors[] = "Invalid admin registration code";
    } else {
        $allow_registration = true;
    }
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{5,20}$/', $username)) {
        $errors[] = "Username must be 5-20 characters and can only contain letters, numbers, and underscores";
    } else {
        // Check if username already exists
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    // Validate first and last name
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    // Validate store name
    if (empty($store_name)) {
        $errors[] = "Store name is required";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
    }
    
    // Confirm password
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If registration is allowed and no errors, proceed with admin creation
    if ($allow_registration && empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new seller into the users table
        $query = "INSERT INTO users (username, email, password, first_name, last_name, user_type, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'seller', 1, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $hashed_password, $first_name, $last_name);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            
            // Insert into sellers table
            $seller_query = "INSERT INTO sellers (user_id, username, password, email, store_name, phone, address, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $seller_stmt = mysqli_prepare($conn, $seller_query);
            mysqli_stmt_bind_param($seller_stmt, "issssss", $user_id, $username, $hashed_password, $email, $store_name, $phone, $address);
            
            if (!mysqli_stmt_execute($seller_stmt)) {
                $errors[] = "Error creating seller record: " . mysqli_error($conn);
            } else {
                $success = "Seller account created successfully. You can now login.";
                
                // Clear form fields after successful registration
                $username = "";
                $email = "";
                $first_name = "";
                $last_name = "";
                $admin_code = "";
                $store_name = "";
                $address = "";
                $phone = "";
            }
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Registration - Plant Nursery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .register-card {
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card register-card shadow">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-leaf text-success fa-3x mb-3"></i>
                    <h3>Plant Nursery Seller Registration</h3>
                    <p class="text-muted">Create a new seller account</p>
                </div>
                
                <?php if(!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="login.php" class="alert-link">Login now</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="form-text">5-20 characters, letters, numbers, and underscores only</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="store_name" class="form-label">Store Name *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-store"></i></span>
                            <input type="text" class="form-control" id="store_name" name="store_name" value="<?php echo htmlspecialchars($store_name); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Store Address *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="password-requirements mt-1">
                            Must be at least 8 characters and include uppercase, lowercase, and numbers
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="admin_code" class="form-label">Seller Registration Code *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="text" class="form-control" id="admin_code" name="admin_code" value="<?php echo htmlspecialchars($admin_code); ?>" required>
                        </div>
                        <div class="form-text">Enter the special code provided to create seller accounts</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Register Seller Account</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php" class="text-success text-decoration-none">Login</a></p>
                    <a href="../index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 