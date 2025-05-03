<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get cart item count if user is logged in
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    include_once('db_connection.php');
    $user_id = $_SESSION['user_id'];
    $query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $cartCount = $row['total'] ? $row['total'] : 0;
    }
}
?>

<header>
    <!-- Top Bar -->
    <div class="bg-success text-white py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small><i class="fas fa-phone-alt me-2"></i> +1 234 567 8901</small>
                    <small class="ms-3"><i class="fas fa-envelope me-2"></i> info@plantnursery.com</small>
                </div>
                <div class="col-md-6 text-end">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <small>Welcome, <?php echo $_SESSION['username']; ?> | </small>
                        <a href="myaccount.php" class="text-white me-3"><small><i class="fas fa-user me-1"></i> My Account</small></a>
                        <a href="logout.php" class="text-white"><small><i class="fas fa-sign-out-alt me-1"></i> Logout</small></a>
                    <?php else: ?>
                        <a href="login.php" class="text-white me-3"><small><i class="fas fa-sign-in-alt me-1"></i> Login</small></a>
                        <a href="register.php" class="text-white"><small><i class="fas fa-user-plus me-1"></i> Register</small></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-leaf text-success me-2"></i>
                <span class="fw-bold fs-4">Plant Nursery</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">Shop</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <?php
                            // Get categories for dropdown
                            include_once('db_connection.php');
                            $query = "SELECT * FROM categories ORDER BY name ASC";
                            $result = mysqli_query($conn, $query);
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<li><a class="dropdown-item" href="category.php?id=' . $row['id'] . '">' . $row['name'] . '</a></li>';
                                }
                            } else {
                                echo '<li><a class="dropdown-item" href="#">No categories found</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <form class="d-flex me-3" action="search.php" method="GET">
                        <input class="form-control me-2" type="search" name="query" placeholder="Search plants..." aria-label="Search">
                        <button class="btn btn-outline-success" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <a href="cart.php" class="position-relative me-3 text-dark">
                        <i class="fas fa-shopping-cart fs-5"></i>
                        <?php if(isset($_SESSION['user_id']) && $cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                            <?php echo $cartCount; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="wishlist.php" class="text-dark">
                        <i class="fas fa-heart fs-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header> 