<?php
// Start session
session_start();

// Check if user is logged in as admin
if(!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include_once("../includes/db_connection.php");

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query conditions
$conditions = [];
$params = [];
$param_types = "";

if(!empty($category_filter)) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= "i";
}

if(!empty($status_filter)) {
    $conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if(!empty($search)) {
    $search_term = '%' . $search . '%';
    $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "ss";
}

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total records count for pagination
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$stmt = mysqli_prepare($conn, $count_query);

if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_records = $row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get categories for filter dropdown
$categories_query = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if($categories_result) {
    while($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}

// Get products with pagination
$products_query = "SELECT p.*, c.name as category_name, u.username as seller_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN users u ON p.seller_id = u.id
                  $where_clause 
                  ORDER BY p.created_at DESC 
                  LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $products_query);
$param_types .= "ii";
$params[] = $offset;
$params[] = $records_per_page;

mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Admin Dashboard</title>
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
                    <h1 class="h2">Products Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-product.php" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-plus"></i> Add Product
                        </a>
                        <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye"></i> View Website
                        </a>
                    </div>
                </div>

                <!-- Products Filter and Search -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="category" class="form-label">Filter by Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Filter by Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="in_stock" <?php echo $status_filter == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                    <option value="out_of_stock" <?php echo $status_filter == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                    <option value="coming_soon" <?php echo $status_filter == 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by Name or Description" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="products.php" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products List -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($products) > 0): ?>
                                        <?php foreach($products as $product): ?>
                                            <tr>
                                                <td>#<?php echo $product['id']; ?></td>
                                                <td>
                                                    <img src="../<?php echo $product['image_path'] ?: 'images/products/default.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail" width="50">
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                    <small class="text-muted d-block">Seller: <?php echo htmlspecialchars($product['seller_name'] ?? 'Admin'); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                                <td>
                                                    <?php if(!empty($product['sale_price'])): ?>
                                                        <span class="text-decoration-line-through text-muted">₹<?php echo number_format($product['price'], 2); ?></span>
                                                        <span class="fw-bold">₹<?php echo number_format($product['sale_price'], 2); ?></span>
                                                    <?php else: ?>
                                                        <span>₹<?php echo number_format($product['price'], 2); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        if($product['stock_quantity'] <= $product['reorder_level']) {
                                                            echo '<span class="text-danger fw-bold">' . $product['stock_quantity'] . '</span>';
                                                        } else {
                                                            echo $product['stock_quantity'];
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($product['status']) {
                                                            case 'in_stock': echo 'success'; break;
                                                            case 'out_of_stock': echo 'danger'; break;
                                                            case 'coming_soon': echo 'info'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $product['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No products found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 