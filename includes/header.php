<?php
// Check if a session is already active before starting a new one
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include_once("includes/db_connection.php");

// Get categories for the dropdown menu
$categories_query = "SELECT DISTINCT id, name FROM categories ORDER BY name ASC";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) ? true : false;
$is_seller = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'seller' ? true : false;
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-leaf text-success me-2"></i>
            <span class="fw-bold text-success ms-2">Plant Nursery</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Categories
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                        <?php foreach($categories as $category): ?>
                            <li><a class="dropdown-item" href="category.php?id=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="shop.php">All Categories</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shop.php">Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
            </ul>
            
            <form class="d-flex me-3" action="shop.php" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="search" placeholder="Search plants..." aria-label="Search">
                    <button class="btn btn-outline-success" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            
            <div class="d-flex align-items-center">
                <a href="cart.php" class="btn btn-outline-success me-2 position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                        echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . 
                            count($_SESSION['cart']) . 
                            '<span class="visually-hidden">items in cart</span></span>';
                    }
                    ?>
                </a>
                
                <?php if($is_logged_in): ?>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="myaccount.php">My Account</a></li>
                            <?php if($is_seller): ?>
                                <li><a class="dropdown-item" href="seller/index.php">Seller Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-success me-2">Login</a>
                    <a href="register.php" class="btn btn-success">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<!-- End Navigation Bar --> 