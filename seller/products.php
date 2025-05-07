<?php
// Start session
session_start();

// Check if user is logged in as seller
if(!isset($_SESSION['seller_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Get seller ID
$seller_id = $_SESSION['seller_id'];

// Delete product if requested
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // Check if product exists and belongs to the current seller
    $check_query = "SELECT image_path FROM products WHERE id = ? AND seller_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ii", $product_id, $seller_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if(mysqli_stmt_num_rows($check_stmt) > 0) {
        // First get the image path to delete the file later
        mysqli_stmt_bind_result($check_stmt, $image_path);
        mysqli_stmt_fetch($check_stmt);
        
        // Delete from database
        $delete_query = "DELETE FROM products WHERE id = ? AND seller_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $product_id, $seller_id);
        
        if(mysqli_stmt_execute($delete_stmt)) {
            // Delete product image if it exists and is not a default image
            if(!empty($image_path) && file_exists("../" . $image_path) && !strpos($image_path, "default")) {
                unlink("../" . $image_path);
            }
            
            $_SESSION['success_message'] = "Product deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete product.";
        }
    } else {
        $_SESSION['error_message'] = "Product not found or you don't have permission to delete it.";
    }
    
    // Redirect to prevent resubmission
    header("Location: products.php");
    exit();
}

// Get filter and search values
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare query with seller_id filter
$query = "SELECT p.*, c.name as category_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.seller_id = ?";

// Add category filter
if ($category_filter > 0) {
    $query .= " AND p.category_id = ?";
}

// Add search filter
if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
}

$query .= " ORDER BY p.id DESC";

// Prepare statement
$stmt = mysqli_prepare($conn, $query);

// Bind parameters
if ($category_filter > 0 && !empty($search)) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt, "iiss", $seller_id, $category_filter, $search_param, $search_param);
} elseif ($category_filter > 0) {
    mysqli_stmt_bind_param($stmt, "ii", $seller_id, $category_filter);
} elseif (!empty($search)) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt, "iss", $seller_id, $search_param, $search_param);
} else {
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
}

// Execute query
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// Get categories for filter
$categories_query = "SELECT DISTINCT c.* 
                    FROM categories c 
                    JOIN products p ON c.id = p.category_id 
                    WHERE p.seller_id = ? 
                    ORDER BY c.name ASC";
$cat_stmt = mysqli_prepare($conn, $categories_query);
mysqli_stmt_bind_param($cat_stmt, "i", $seller_id);
mysqli_stmt_execute($cat_stmt);
$categories_result = mysqli_stmt_get_result($cat_stmt);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}

// Count seller products
$count_query = "SELECT COUNT(*) as total FROM products WHERE seller_id = ?";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $seller_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_products = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_products = $count_row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Plant Nursery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/seller-style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once('includes/sidebar.php'); ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Products</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-product.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    </div>
                </div>

                <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="products.php" method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="category" class="form-label">Filter by Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="0">All Categories</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search Products</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Enter product name or description" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Product List</h5>
                            <span class="badge bg-primary"><?php echo count($products); ?> of <?php echo $total_products; ?> products</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Sale Price</th>
                                        <th>Inventory</th>
                                        <th>Featured</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($products) > 0): ?>
                                        <?php foreach($products as $product): ?>
                                            <tr>
                                                <td><?php echo $product['id']; ?></td>
                                                <td>
                                                    <img src="<?php echo '../' . $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                </td>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'None'); ?></td>
                                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                                <td>
                                                    <?php if($product['sale_price']): ?>
                                                        ₹<?php echo number_format($product['sale_price'], 2); ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($product['stock_quantity'] <= 5): ?>
                                                        <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?></span>
                                                    <?php elseif($product['stock_quantity'] <= 20): ?>
                                                        <span class="badge bg-warning text-dark"><?php echo $product['stock_quantity']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><?php echo $product['stock_quantity']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($product['featured']): ?>
                                                        <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><i class="fas fa-times"></i></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-danger delete-product" 
                                                           data-id="<?php echo $product['id']; ?>" 
                                                           data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                           title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No products found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <span id="product-name"></span>?
                    <p class="text-danger mt-2 mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirm-delete" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete product confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const productNameSpan = document.getElementById('product-name');
            const confirmDeleteLink = document.getElementById('confirm-delete');
            
            document.querySelectorAll('.delete-product').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');
                    
                    productNameSpan.textContent = '"' + productName + '"';
                    confirmDeleteLink.href = 'products.php?delete=' + productId;
                    
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html> 