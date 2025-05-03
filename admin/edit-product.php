<?php
// Start session
session_start();

// Check if user is logged in as admin
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Initialize variables
$errors = [];

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID.";
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Get product information
$query = "SELECT * FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
    
    // Set form variables from product data
    $name = $product['name'];
    $description = $product['description'];
    $price = $product['price'];
    $sale_price = $product['sale_price'] ?? '';
    $quantity = $product['quantity'];
    $category_id = $product['category_id'] ?? '';
    $featured = $product['featured'];
    $status = $product['status'];
    $current_image = $product['image_path'];
} else {
    $_SESSION['error_message'] = "Product not found.";
    header("Location: products.php");
    exit();
}

// Get categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $sale_price = trim($_POST['sale_price']);
    $quantity = trim($_POST['quantity']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'];
    
    // Validate required fields
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    
    if (empty($description)) {
        $errors[] = "Product description is required";
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = "Valid price is required";
    }
    
    if (!empty($sale_price) && (!is_numeric($sale_price) || $sale_price <= 0 || $sale_price >= $price)) {
        $errors[] = "Sale price must be less than regular price";
    }
    
    if (empty($quantity) || !is_numeric($quantity) || $quantity < 0) {
        $errors[] = "Valid quantity is required";
    }
    
    // Process image upload if a new one is provided
    $image_path = $current_image; // Default to current image
    
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $target_dir = "../images/products/";
        $allowed_types = ["jpg", "jpeg", "png", "webp"];
        $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
        
        // Check if directory exists, create if not
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        // Check file extension
        if (in_array($file_extension, $allowed_types)) {
            // Generate unique filename
            $new_filename = uniqid() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            $relative_path = "images/products/" . $new_filename;
            
            // Try to move uploaded file
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $image_path = $relative_path;
                
                // Delete old image if it exists and is not the default
                if ($current_image !== "images/products/default-plant.jpg" && file_exists("../" . $current_image)) {
                    unlink("../" . $current_image);
                }
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Only JPG, JPEG, PNG & WEBP files are allowed";
        }
    }
    
    // If no errors, proceed with database update
    if (empty($errors)) {
        $query = "UPDATE products SET 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    sale_price = ?, 
                    quantity = ?, 
                    image_path = ?, 
                    category_id = ?, 
                    featured = ?, 
                    status = ?
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        
        // Handle null values for sale_price and category_id
        $sale_price_param = empty($sale_price) ? null : $sale_price;
        $category_id_param = ($category_id === 0) ? null : $category_id;
        
        mysqli_stmt_bind_param($stmt, "ssddisiisi", $name, $description, $price, $sale_price_param, $quantity, $image_path, $category_id_param, $featured, $status, $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Product updated successfully!";
            header("Location: products.php");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
            
            // If database update fails and we uploaded a new image, delete it
            if ($image_path !== $current_image && file_exists("../" . $image_path)) {
                unlink("../" . $image_path);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Plant Nursery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once('includes/sidebar.php'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Product</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
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
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Product: <?php echo htmlspecialchars($name); ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars($description); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="price" class="form-label">Regular Price <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sale_price" class="form-label">Sale Price (optional)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01" min="0" value="<?php echo htmlspecialchars($sale_price); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="quantity" class="form-label">Inventory Quantity <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="<?php echo htmlspecialchars($quantity); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="in_stock" <?php echo $status === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                                    <option value="out_of_stock" <?php echo $status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                                    <option value="coming_soon" <?php echo $status === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="product_image" class="form-label">Product Image</label>
                                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/jpeg, image/png, image/webp">
                                        <div class="form-text">Recommended size: 800x800 pixels. Max 2MB.</div>
                                        <div class="mt-3">
                                            <div class="image-preview border rounded p-2 text-center" id="imagePreview">
                                                <img src="<?php echo '../' . $current_image; ?>" class="img-fluid" id="preview-image" alt="Product Image Preview" style="max-height: 200px;">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="0">Select Category</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="featured">Feature on Homepage</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 d-flex justify-content-between">
                                <a href="products.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Update Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('product_image');
            const previewImage = document.getElementById('preview-image');
            
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html> 