<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include_once("includes/db_connection.php");

// Initialize variables
$username = "";
$email = "";
$first_name = "";
$last_name = "";
$password = "";
$confirm_password = "";
$user_type = "buyer"; // Default to buyer
$phone = "";
$address_line1 = "";
$address_line2 = "";
$city = "";
$state = "";
$zip_code = "";
$country = "";
$address_type = "home";
$errors = [];

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    $phone = trim($_POST['phone'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $address_type = $_POST['address_type'] ?? 'home';
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    } else {
        // Check if username exists
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Username already exists. Please choose another one.";
        }
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already exists. Please use another one or try to login.";
        }
    }
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, insert user data
    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert user
            $query = "INSERT INTO users (username, email, password, first_name, last_name, user_type, phone) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssss", $username, $email, $password_hash, $first_name, $last_name, $user_type, $phone);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting user: " . mysqli_error($conn));
            }
            
            // Get the user ID
            $user_id = mysqli_insert_id($conn);
            
            // Insert address if address line 1 is provided
            if (!empty($address_line1)) {
                $query = "INSERT INTO user_addresses (user_id, address_line1, address_line2, city, state, zip_code, country, address_type, is_default) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $address_line1, $address_line2, $city, $state, $zip_code, $country, $address_type);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting address: " . mysqli_error($conn));
                }
            }
            
            // If we got here, commit the transaction
            mysqli_commit($conn);
            
            // Set success message and redirect to login
            $_SESSION['registration_success'] = "Registration successful! You can now login with your credentials.";
            header("Location: login.php");
            exit();
            
        } catch (Exception $e) {
            // Something went wrong, rollback the transaction
            mysqli_rollback($conn);
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <section class="register-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow border-0">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-plus text-success fa-3x mb-3"></i>
                                <h2 class="fw-bold">Create an Account</h2>
                                <p class="text-muted">Join us and enjoy shopping for plants</p>
                            </div>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label class="form-label">User Type</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="user_type" id="userTypeBuyer" value="buyer" checked>
                                            <label class="form-check-label" for="userTypeBuyer">
                                                Buyer
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="user_type" id="userTypeSeller" value="seller">
                                            <label class="form-check-label" for="userTypeSeller">
                                                Seller
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address_line1" class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($address_line1); ?>" placeholder="Street address, P.O. box, company name">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address_line2" class="form-label">Address Line 2 <span class="text-muted">(Optional)</span></label>
                                    <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($address_line2); ?>" placeholder="Apartment, suite, unit, building, floor, etc.">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="state" class="form-label">State/Province</label>
                                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($state); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                        <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($country); ?>" placeholder="India">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address_type" class="form-label">Address Type</label>
                                    <select class="form-select" id="address_type" name="address_type">
                                        <option value="home" <?php echo $address_type == 'home' ? 'selected' : ''; ?>>Home</option>
                                        <option value="work" <?php echo $address_type == 'work' ? 'selected' : ''; ?>>Work</option>
                                        <option value="other" <?php echo $address_type == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="terms-conditions.php" class="text-success">Terms and Conditions</a>
                                    </label>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">Register</button>
                                </div>
                            </form>
                            
                            <div class="mt-4 text-center">
                                <p>Already have an account? <a href="login.php" class="text-success text-decoration-none">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 