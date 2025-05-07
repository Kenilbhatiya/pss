<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Nursery - Premium Plants for Your Home</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Success Message -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Bring Nature Into Your Home</h1>
                    <p class="lead">Discover our collection of premium plants that transform your living space into a green sanctuary.</p>
                    <a href="shop.php" class="btn btn-success btn-lg">Shop Now</a>
                </div>
                <div class="col-md-6">
                    <img src="images/img1.jpg" alt="Beautiful plant" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="featured-categories py-5">
        <div class="container">
            <h2 class="text-center mb-4">Shop by Category</h2>
            <div class="row">
                <?php
                // Include database connection
                include_once("includes/db_connection.php");
                
                // Get categories from database
                $query = "SELECT DISTINCT id, name, image_path, description FROM categories LIMIT 4";
                $result = mysqli_query($conn, $query);
                
                if (!$result) {
                    echo '<div class="col-12 text-center"><p>Error retrieving categories: ' . mysqli_error($conn) . '</p></div>';
                } else if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="col-md-3 mb-4">';
                        echo '<div class="card h-100">';
                        // Use the image field from our database, with a fallback
                        if (!empty($row['image_path'])) {
                            echo '<img src="' . $row['image_path'] . '" class="card-img-top" alt="' . htmlspecialchars($row['name']) . '" style="height: 200px; object-fit: cover;">';
                        } else {
                            // Default images based on category name
                            $default_image = "images/default-category.jpg";
                            
                            // Try to match category name for better defaults
                            $category_name = strtolower($row['name']);
                            if (strpos($category_name, 'indoor') !== false) {
                                $default_image = "images/indoor-plants.jpg";
                            } else if (strpos($category_name, 'outdoor') !== false) {
                                $default_image = "images/outdoor-plants.jpg";
                            } else if (strpos($category_name, 'succulent') !== false) {
                                $default_image = "images/succulents.jpg";
                            } else if (strpos($category_name, 'flower') !== false) {
                                $default_image = "images/flowering-plants.jpg";
                            }
                            
                            echo '<img src="' . $default_image . '" class="card-img-top" alt="' . htmlspecialchars($row['name']) . '" style="height: 200px; object-fit: cover;">';
                        }
                        echo '<div class="card-body text-center">';
                        echo '<h5 class="card-title">' . htmlspecialchars($row['name']) . '</h5>';
                        echo '<a href="category.php?id=' . $row['id'] . '" class="btn btn-outline-success">Explore</a>';
                        echo '</div></div></div>';
                    }
                } else {
                    // Fallback if no categories found
                    echo '<div class="col-12 text-center"><p>Categories coming soon!</p></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Featured Plants</h2>
            <div class="row">
                <?php
                // Get featured products from database
                $query = "SELECT * FROM products WHERE featured = 1 LIMIT 4";
                $result = mysqli_query($conn, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="col-md-3 mb-4">';
                        echo '<div class="card product-card">';
                        echo '<img src="' . $row['image_path'] . '" class="card-img-top" alt="' . $row['name'] . '">';
                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">' . $row['name'] . '</h5>';
                        echo '<p class="card-text text-success fw-bold">$' . number_format($row['price'], 2) . '</p>';
                        echo '<div class="d-flex justify-content-between">';
                        echo '<a href="product.php?id=' . $row['id'] . '" class="btn btn-sm btn-outline-secondary">Details</a>';
                        echo '<form action="add_to_cart.php" method="post" class="m-0">';
                        echo '<input type="hidden" name="product_id" value="' . $row['id'] . '">';
                        echo '<button type="submit" class="btn btn-sm btn-success">Add to Cart</button>';
                        echo '</form>';
                        echo '</div></div></div></div>';
                    }
                } else {
                    // Fallback if no featured products found
                    echo '<div class="col-12 text-center"><p>Featured products coming soon!</p></div>';
                }
                ?>
            </div>
            <div class="text-center mt-4">
                <a href="shop.php" class="btn btn-outline-success">View All Plants</a>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Us</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="benefit-item">
                        <i class="fas fa-truck-fast fa-3x text-success mb-3"></i>
                        <h4>Fast Delivery</h4>
                        <p>We deliver your plants within 2-3 business days, ensuring they arrive fresh and healthy.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="benefit-item">
                        <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                        <h4>Quality Guaranteed</h4>
                        <p>All our plants are grown with care and come with a 30-day health guarantee.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="benefit-item">
                        <i class="fas fa-headset fa-3x text-success mb-3"></i>
                        <h4>Expert Support</h4>
                        <p>Our team of plant experts is available to answer any questions about plant care.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">What Our Customers Say</h2>
            <div class="row">
                <?php
                // Get testimonials from database
                $query = "SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 3";
                $result = mysqli_query($conn, $query);
                
                if (!$result) {
                    echo '<div class="col-12 text-center"><p>Error retrieving testimonials: ' . mysqli_error($conn) . '</p></div>';
                } else if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<div class="col-md-4 mb-4">';
                        echo '<div class="card h-100">';
                        echo '<div class="card-body">';
                        echo '<div class="d-flex align-items-center mb-3">';
                        
                        // Display customer image with fallback
                        if (!empty($row['image_path'])) {
                            echo '<img src="' . $row['image_path'] . '" alt="' . htmlspecialchars($row['name']) . '" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">';
                        } else {
                            // Generate default avatar based on first letter of name
                            $firstLetter = strtoupper(substr($row['name'], 0, 1));
                            $colors = ['#007bff', '#28a745', '#dc3545', '#fd7e14', '#6f42c1'];
                            $colorIndex = ord($firstLetter) % count($colors);
                            $bgColor = $colors[$colorIndex];
                            
                            echo '<div class="rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: ' . $bgColor . '; color: white; font-weight: bold; font-size: 24px;">';
                            echo $firstLetter;
                            echo '</div>';
                        }
                        
                        echo '<div>';
                        echo '<h5 class="mb-0">' . htmlspecialchars($row['name']) . '</h5>';
                        echo '</div></div>';
                        echo '<p class="card-text">"' . htmlspecialchars($row['comment']) . '"</p>';
                        echo '<div class="text-warning">';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $row['rating']) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        echo '</div>';
                        echo '</div></div></div>';
                    }
                } else {
                    // Add default testimonials if none are found
                    $default_testimonials = [
                        [
                            'name' => 'Sarah Johnson',
                            'comment' => 'I received my Peace Lily in perfect condition. It\'s been thriving in my apartment and has already produced two beautiful flowers!',
                            'rating' => 5,
                            'first_letter' => 'S',
                            'color' => '#28a745'
                        ],
                        [
                            'name' => 'Mike Thompson',
                            'comment' => 'The customer service was outstanding. When one of my plants arrived damaged, they immediately shipped a replacement.',
                            'rating' => 5,
                            'first_letter' => 'M',
                            'color' => '#007bff'
                        ],
                        [
                            'name' => 'Jennifer Davis',
                            'comment' => 'I\'ve ordered plants from many online shops, but Plant Nursery has the best quality by far. My Monstera is growing so fast!',
                            'rating' => 4,
                            'first_letter' => 'J',
                            'color' => '#6f42c1'
                        ]
                    ];
                    
                    foreach ($default_testimonials as $testimonial) {
                        echo '<div class="col-md-4 mb-4">';
                        echo '<div class="card h-100">';
                        echo '<div class="card-body">';
                        echo '<div class="d-flex align-items-center mb-3">';
                        
                        // Display default avatar
                        echo '<div class="rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: ' . $testimonial['color'] . '; color: white; font-weight: bold; font-size: 24px;">';
                        echo $testimonial['first_letter'];
                        echo '</div>';
                        
                        echo '<div>';
                        echo '<h5 class="mb-0">' . htmlspecialchars($testimonial['name']) . '</h5>';
                        echo '</div></div>';
                        echo '<p class="card-text">"' . htmlspecialchars($testimonial['comment']) . '"</p>';
                        echo '<div class="text-warning">';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $testimonial['rating']) {
                                echo '<i class="fas fa-star"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        echo '</div>';
                        echo '</div></div></div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter py-5 bg-success text-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h3>Subscribe to Our Newsletter</h3>
                    <p class="mb-4">Get plant care tips, special offers, and updates delivered to your inbox.</p>
                    <form action="newsletter_subscribe.php" method="POST" class="d-flex justify-content-center">
                        <div class="input-group mb-3" style="max-width: 500px;">
                            <input type="email" class="form-control" placeholder="Your email address" required name="email">
                            <button class="btn btn-light" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 