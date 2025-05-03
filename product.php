<?php
// Start session
session_start();

// Include database connection
include_once("includes/db_connection.php");

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id = intval($_GET['id']);

// Get product details
$product = null;
$query = "SELECT p.*, c.name as category_name, c.id as category_id 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
} else {
    // Product not found, redirect to shop
    header("Location: shop.php");
    exit();
}

// Get all product images
$images = [];
$images_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
$images_stmt = mysqli_prepare($conn, $images_query);
mysqli_stmt_bind_param($images_stmt, "i", $product_id);
mysqli_stmt_execute($images_stmt);
$images_result = mysqli_stmt_get_result($images_stmt);

if ($images_result && mysqli_num_rows($images_result) > 0) {
    while ($row = mysqli_fetch_assoc($images_result)) {
        $images[] = $row;
    }
}

// If no additional images, use the main product image
if (empty($images)) {
    $images[] = [
        'image_path' => $product['image_path'],
        'is_primary' => 1
    ];
}

// Get product attributes
$attributes = [];
$attr_query = "SELECT * FROM product_attributes WHERE product_id = ? ORDER BY attribute_name";
$attr_stmt = mysqli_prepare($conn, $attr_query);
mysqli_stmt_bind_param($attr_stmt, "i", $product_id);
mysqli_stmt_execute($attr_stmt);
$attr_result = mysqli_stmt_get_result($attr_stmt);

if ($attr_result && mysqli_num_rows($attr_result) > 0) {
    while ($row = mysqli_fetch_assoc($attr_result)) {
        $attributes[] = $row;
    }
}

// Get related products from same category
$related_products = [];
if ($product['category_id']) {
    $related_query = "SELECT * FROM products 
                     WHERE category_id = ? AND id != ? 
                     ORDER BY RAND() LIMIT 4";
    $related_stmt = mysqli_prepare($conn, $related_query);
    mysqli_stmt_bind_param($related_stmt, "ii", $product['category_id'], $product_id);
    mysqli_stmt_execute($related_stmt);
    $related_result = mysqli_stmt_get_result($related_stmt);

    if ($related_result && mysqli_num_rows($related_result) > 0) {
        while ($row = mysqli_fetch_assoc($related_result)) {
            $related_products[] = $row;
        }
    }
}

// Check if product is in user's wishlist
$in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $wishlist_query = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $wishlist_stmt = mysqli_prepare($conn, $wishlist_query);
    mysqli_stmt_bind_param($wishlist_stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($wishlist_stmt);
    mysqli_stmt_store_result($wishlist_stmt);
    $in_wishlist = (mysqli_stmt_num_rows($wishlist_stmt) > 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Plant Nursery</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <!-- Product Detail Section -->
    <section class="product-detail py-5">
        <div class="container">
            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning_message'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['warning_message']; unset($_SESSION['warning_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                    <?php if ($product['category_id']): ?>
                        <li class="breadcrumb-item"><a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
            
            <div class="row">
                <!-- Product Images -->
                <div class="col-md-6 mb-4">
                    <div class="product-images">
                        <div class="main-image mb-3">
                            <img src="<?php echo $images[0]['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid rounded" id="main-product-image">
                        </div>
                        <?php if (count($images) > 1): ?>
                            <div class="thumbnail-images d-flex">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="thumbnail-image me-2 <?php echo $index === 0 ? 'active' : ''; ?>" data-image="<?php echo $image['image_path']; ?>">
                                        <img src="<?php echo $image['image_path']; ?>" alt="Thumbnail" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="col-md-6">
                    <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="price-box mb-4">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="text-decoration-line-through text-muted me-2">₹<?php echo number_format($product['price'], 2); ?></span>
                            <span class="text-success fw-bold fs-4">₹<?php echo number_format($product['sale_price'], 2); ?></span>
                            <?php 
                                $discount = round(($product['price'] - $product['sale_price']) / $product['price'] * 100);
                                echo '<span class="badge bg-danger ms-2">Save ' . $discount . '%</span>';
                            ?>
                        <?php else: ?>
                            <span class="text-success fw-bold fs-4">₹<?php echo number_format($product['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stock-status mb-3">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i> In Stock</span>
                            <span class="text-muted ms-2"><?php echo $product['stock_quantity']; ?> units available</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="description mb-4">
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($attributes)): ?>
                        <div class="product-attributes mb-4">
                            <h5 class="mb-3">Specifications</h5>
                            <div class="row">
                                <?php foreach ($attributes as $attribute): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex">
                                            <span class="text-muted me-2"><?php echo htmlspecialchars($attribute['attribute_name']); ?>:</span>
                                            <span><?php echo htmlspecialchars($attribute['attribute_value']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <form action="add_to_cart.php" method="POST" class="mb-4">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            
                            <div class="d-flex align-items-center mb-3">
                                <label for="quantity" class="me-3">Quantity:</label>
                                <div class="quantity-input d-flex align-items-center">
                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-btn" data-action="decrease"><i class="fas fa-minus"></i></button>
                                    <input type="number" class="form-control form-control-sm text-center mx-2" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="width: 60px;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-btn" data-action="increase"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            
                            <div class="d-flex">
                                <button type="submit" class="btn btn-success flex-grow-1 me-2">
                                    <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                                </button>
                                
                                <?php if ($in_wishlist): ?>
                                    <a href="remove_from_wishlist.php?id=<?php echo $product_id; ?>&redirect=product" class="btn btn-outline-danger">
                                        <i class="fas fa-heart"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="add_to_wishlist.php?id=<?php echo $product_id; ?>&redirect=product" class="btn btn-outline-danger">
                                        <i class="far fa-heart"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="product-meta">
                        <div class="mb-2">
                            <span class="text-muted">Category:</span>
                            <a href="category.php?id=<?php echo $product['category_id']; ?>" class="text-success"><?php echo htmlspecialchars($product['category_name']); ?></a>
                        </div>
                        <div>
                            <span class="text-muted">SKU:</span>
                            <span>PN-<?php echo str_pad($product_id, 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <section class="related-products py-5 bg-light">
            <div class="container">
                <h2 class="mb-4">You May Also Like</h2>
                <div class="row">
                    <?php foreach ($related_products as $related_product): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100 product-card">
                                <img src="<?php echo $related_product['image_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($related_product['name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($related_product['name']); ?></h5>
                                    <p class="card-text text-success fw-bold">₹<?php echo number_format($related_product['price'], 2); ?></p>
                                    <div class="d-flex justify-content-between">
                                        <a href="product.php?id=<?php echo $related_product['id']; ?>" class="btn btn-sm btn-outline-secondary">Details</a>
                                        <form action="add_to_cart.php" method="post" class="m-0">
                                            <input type="hidden" name="product_id" value="<?php echo $related_product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Add to Cart</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php include_once("includes/footer.php"); ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Image thumbnail functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mainImage = document.getElementById('main-product-image');
            const thumbnails = document.querySelectorAll('.thumbnail-image');
            
            thumbnails.forEach(thumb => {
                thumb.addEventListener('click', function() {
                    const imgSrc = this.getAttribute('data-image');
                    mainImage.src = imgSrc;
                    
                    // Update active state
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Quantity buttons
            const quantityInput = document.getElementById('quantity');
            const maxQuantity = parseInt(quantityInput.getAttribute('max'));
            
            document.querySelectorAll('.quantity-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    let currentValue = parseInt(quantityInput.value);
                    
                    if (this.getAttribute('data-action') === 'increase') {
                        if (currentValue < maxQuantity) {
                            quantityInput.value = currentValue + 1;
                        }
                    } else {
                        if (currentValue > 1) {
                            quantityInput.value = currentValue - 1;
                        }
                    }
                });
            });
        });
    </script>
    
    <style>
        .thumbnail-image {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .thumbnail-image:hover, .thumbnail-image.active {
            opacity: 1;
            border: 2px solid #28a745;
        }
    </style>
</body>
</html> 