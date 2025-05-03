<?php
// Start session
session_start();

// Redirect if already logged in
if(isset($_SESSION['seller_id'])) {
    header("Location: add-product.php");
    exit();
} elseif(isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Initialize variables
$username = "";
$password = "";
$error = "";

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Query for user (any type)
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables based on user type
                if ($user['user_type'] == 'seller') {
                    $_SESSION['seller_id'] = $user['id'];
                    $_SESSION['seller_username'] = $user['username'];
                    
                    // Redirect seller to add product page
                    header("Location: add-product.php");
                    exit();
                } elseif ($user['user_type'] == 'admin') {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    
                    // Redirect admin to dashboard
                    header("Location: index.php");
                    exit();
                } elseif ($user['user_type'] == 'buyer') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Redirect buyer to main site
                    header("Location: ../index.php");
                    exit();
                }
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login - Plant Nursery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/seller-style.css">
</head>
<body>
    <div class="login-container">
        <div class="card login-card shadow">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-leaf text-success fa-3x mb-3"></i>
                    <h3>Plant Nursery Seller</h3>
                    <p class="text-muted">Enter your credentials to access the seller panel</p>
                </div>
                
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
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
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Login</button>
                </form>
                
                <div class="text-center mt-4">
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