<?php
// Turn on error reporting 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
session_start([
    'cookie_lifetime' => 86400, // 1 day
    'cookie_httponly' => true,
    'cookie_path' => '/', 
    'use_cookies' => 1,
    'use_only_cookies' => 1
]);

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
} elseif (isset($_SESSION['seller_id'])) {
    header("Location: seller/index.php");
    exit();
} elseif (isset($_SESSION['admin_id'])) {
    header("Location: admin/index.php");
    exit();
}

// Include database connection
include_once("includes/db_connection.php");

// Initialize variables
$username = $password = "";
$error = "";
$success = "";

// Check if the user is coming from another page
if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
    $redirect = $_SESSION['redirect_after_login'];
} else {
    $redirect = "index.php";
}

// Check if user just registered successfully
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = "Both username and password are required";
    } else {
        // Check user in database
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Clear redirect after login
                $redirect_url = $redirect;
                unset($_SESSION['redirect_after_login']);
                
                // Set session variables based on user type
                if ($user['user_type'] == 'seller') {
                    $_SESSION['seller_id'] = $user['id'];
                    $_SESSION['seller_username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Redirect seller to seller dashboard
                    header("Location: seller/index.php");
                    exit();
                } else if ($user['user_type'] == 'admin') {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Redirect admin to admin dashboard
                    header("Location: admin/index.php");
                    exit();
                } else {
                    // Regular user/buyer
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Redirect buyer to main site
                    header("Location: " . $redirect_url);
                    exit();
                }
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Username does not exist";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <section class="login-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow border-0">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-circle text-success fa-3x mb-3"></i>
                                <h2 class="fw-bold">Welcome Back</h2>
                                <p class="text-muted">Sign in to access your account</p>
                            </div>
                            
                            <?php if(!empty($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($success)): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $success; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="login" class="btn btn-success">Login</button>
                                </div>
                            </form>
                            
                            <div class="mt-4 text-center">
                                <p>Don't have an account? <a href="register.php" class="text-success text-decoration-none">Register here</a></p>
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