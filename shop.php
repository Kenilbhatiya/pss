<?php
include_once("includes/db_connection.php");

// Get filter values
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare query
$query = "SELECT p.*, c.name as category_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE 1=1";

// Add category filter
if ($category_id > 0) {
    $query .= " AND p.category_id = $category_id";
}

// Add search filter
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    default:
        $query .= " ORDER BY p.id DESC";
}

// Execute query
$result = mysqli_query($conn, $query);
$products = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Shop Banner -->
    <section class="shop-banner bg-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold text-success">Our Plants Collection</h1>
                    <p class="lead">Discover the perfect plants to bring nature into your space</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Shop Content -->
    <section class="shop-content py-5">
        <div class="container">
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Filters</h5>
                        </div>
                        <div class="card-body">
                            <form action="shop.php" method="GET">
                                <?php if(!empty($search)): ?>
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <?php endif; ?>
                                
                                <div class="mb-4">
                                    <h6 class="fw-bold">Categories</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="category_id" id="category_all" value="0" <?php echo $category_id == 0 ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category_all">
                                            All Categories
                                        </label>
                                    </div>
                                    <?php foreach($categories as $category): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="category_id" id="category_<?php echo $category['id']; ?>" value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="category_<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mb-4">
                                    <h6 class="fw-bold">Sort By</h6>
                                    <select class="form-select" name="sort">
                                        <option value="default" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Default</option>
                                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">Apply Filters</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="col-lg-9">
                    <?php if(!empty($search)): ?>
                        <div class="alert alert-info mb-4">
                            Search results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                            <a href="shop.php" class="float-end">Clear Search</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php if(count($products) > 0): ?>
                            <?php foreach($products as $product): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 product-card">
                                        <img src="<?php echo $product['image_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p class="card-text text-success fw-bold">₹<?php echo number_format($product['price'], 2); ?></p>
                                            <p class="card-text small text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                            <div class="d-flex justify-content-between">
                                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-secondary">Details</a>
                                                <form action="add_to_cart.php" method="post" class="m-0">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">Add to Cart</button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white border-top-0">
                                            <form action="add_to_wishlist.php" method="post" class="m-0">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="far fa-heart"></i> Add to Wishlist</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    No products found. Please try different filters or check back later.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 