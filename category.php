<?php
include_once("includes/db_connection.php");

// Get category ID
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no category ID provided, redirect to shop page
if ($category_id <= 0) {
    header("Location: shop.php");
    exit();
}

// Get category information
$category = null;
$query = "SELECT * FROM categories WHERE id = $category_id";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $category = mysqli_fetch_assoc($result);
} else {
    // Category not found, redirect to shop
    header("Location: shop.php");
    exit();
}

// Get products for this category
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Prepare query
$query = "SELECT * FROM products WHERE category_id = $category_id";

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY name DESC";
        break;
    default:
        $query .= " ORDER BY id DESC";
}

// Execute query
$result = mysqli_query($conn, $query);
$products = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Category Banner -->
    <section class="category-banner py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category['name']); ?></li>
                        </ol>
                    </nav>
                    <h1 class="display-4 fw-bold text-success"><?php echo htmlspecialchars($category['name']); ?></h1>
                    <p class="lead"><?php echo htmlspecialchars($category['description']); ?></p>
                </div>
                <div class="col-md-6">
                    <img src="<?php echo $category['image_path']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2><?php echo count($products); ?> Products</h2>
                        <div class="sort-options">
                            <form action="category.php" method="GET" class="d-flex align-items-center">
                                <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                                <label for="sort" class="me-2">Sort by:</label>
                                <select class="form-select form-select-sm" name="sort" id="sort" onchange="this.form.submit()">
                                    <option value="default" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Default</option>
                                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $product): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
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
                                <div class="card-footer bg-white border-top-0">
                                    <a href="add_to_wishlist.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-danger w-100"><i class="far fa-heart"></i> Add to Wishlist</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No products available in this category at the moment. Please check back later.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 