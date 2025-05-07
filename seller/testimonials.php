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

// Get seller ID from session
$seller_id = $_SESSION['seller_id'];

// Check if testimonials table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'testimonials'");
if($tableExists->num_rows == 0) {
    // Create testimonials table
    $createTable = "CREATE TABLE testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL DEFAULT 5,
        comment TEXT NOT NULL,
        status TINYINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTable);
}

// Handle testimonial operations (edit, delete, approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_approval':
                $id = $_POST['id'];
                $status = $_POST['status'];
                $new_status = $status == 1 ? 0 : 1;
                
                $stmt = $conn->prepare("UPDATE testimonials SET status = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_status, $id);
                $stmt->execute();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
        }
    }
}

// Get products for this seller
$products_query = "SELECT id, name FROM products WHERE seller_id = ?";
$products_stmt = $conn->prepare($products_query);
$products_stmt->bind_param("i", $seller_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$seller_products = [];
while($row = $products_result->fetch_assoc()) {
    $seller_products[] = $row;
}

// Fetch testimonials for this seller's products
$testimonials = [];
if(!empty($seller_products)) {
    $product_ids = array_column($seller_products, 'id');
    $product_ids_str = implode(',', $product_ids);
    
    if(!empty($product_ids_str)) {
        $query = "SELECT t.*, p.name as product_name 
                FROM testimonials t
                JOIN products p ON t.product_id = p.id
                WHERE p.id IN ($product_ids_str)
                ORDER BY t.created_at DESC";
        $result = $conn->query($query);
        
        if($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $testimonials[] = $row;
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
    <title>Product Reviews - Plant Nursery</title>
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
                    <h1 class="h2">Product Reviews</h1>
                </div>
                
                <!-- Testimonials List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Product Reviews</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Rating</th>
                                        <th>Review</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($testimonials) > 0): ?>
                                        <?php foreach ($testimonials as $testimonial): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($testimonial['id']); ?></td>
                                            <td><?php echo htmlspecialchars($testimonial['product_name']); ?></td>
                                            <td>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $testimonial['rating']): ?>
                                                        <i class="fas fa-star text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-warning"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($testimonial['comment'], 0, 100)) . (strlen($testimonial['comment']) > 100 ? '...' : ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $testimonial['status'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $testimonial['status'] ? 'Approved' : 'Pending'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_approval">
                                                    <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $testimonial['status']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $testimonial['status'] ? 'warning' : 'success'; ?>">
                                                        <?php echo $testimonial['status'] ? 'Unpublish' : 'Approve'; ?>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No reviews found for your products</td>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 