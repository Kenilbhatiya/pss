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

// Process category deletion if requested
if(isset($_POST['delete_category']) && isset($_POST['category_id'])) {
    $category_id = $_POST['category_id'];
    
    // Check if there are products using this category
    $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if($row['count'] > 0) {
        $delete_error = "Cannot delete category because it contains products. Please reassign the products first.";
    } else {
        // Delete the category
        $delete_query = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $delete_success = "Category deleted successfully.";
        } else {
            $delete_error = "Error deleting category: " . mysqli_error($conn);
        }
    }
}

// Handle adding new category
if(isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);
    
    if(empty($category_name)) {
        $add_error = "Category name is required.";
    } else {
        // Check if category name already exists
        $check_query = "SELECT COUNT(*) as count FROM categories WHERE name = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $category_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if($row['count'] > 0) {
            $add_error = "A category with this name already exists.";
        } else {
            // Process image upload if exists
            $image_path = '';
            if(isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
                $uploads_dir = '../images/categories/';
                $tmp_name = $_FILES['category_image']['tmp_name'];
                $image_name = basename($_FILES['category_image']['name']);
                $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
                $unique_image_name = 'category_' . time() . '_' . uniqid() . '.' . $image_ext;
                
                if(move_uploaded_file($tmp_name, $uploads_dir . $unique_image_name)) {
                    $image_path = 'images/categories/' . $unique_image_name;
                } else {
                    $add_error = "Error uploading image.";
                }
            }
            
            if(empty($add_error)) {
                // Insert new category
                $insert_query = "INSERT INTO categories (name, description, image_path, created_at) VALUES (?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "sss", $category_name, $category_description, $image_path);
                
                if(mysqli_stmt_execute($stmt)) {
                    $add_success = "Category added successfully.";
                    // Clear form data after successful submission
                    unset($category_name);
                    unset($category_description);
                } else {
                    $add_error = "Error adding category: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Get categories with product counts
$categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                    FROM categories c 
                    LEFT JOIN products p ON c.id = p.category_id 
                    GROUP BY c.id 
                    ORDER BY c.name ASC";
$categories_result = mysqli_query($conn, $categories_query);

$categories = [];
if($categories_result) {
    while($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin Dashboard</title>
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
                    <h1 class="h2">Categories Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye"></i> View Website
                        </a>
                    </div>
                </div>

                <?php if(isset($delete_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $delete_success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($delete_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $delete_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($add_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $add_success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($add_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $add_error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Categories List -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">All Categories</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Image</th>
                                                <th>Name</th>
                                                <th>Products</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($categories) > 0): ?>
                                                <?php foreach($categories as $category): ?>
                                                    <tr>
                                                        <td>#<?php echo $category['id']; ?></td>
                                                        <td>
                                                            <?php if(!empty($category['image_path'])): ?>
                                                                <img src="../<?php echo $category['image_path']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="img-thumbnail" width="50">
                                                            <?php else: ?>
                                                                <span class="text-muted">No image</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                            <?php if(!empty($category['description'])): ?>
                                                                <small class="text-muted d-block"><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?><?php echo strlen($category['description']) > 50 ? '...' : ''; ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $category['product_count']; ?></td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <?php if($category['product_count'] == 0): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $category['id']; ?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <!-- Delete Confirmation Modal -->
                                                            <div class="modal fade" id="deleteModal<?php echo $category['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $category['id']; ?>" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $category['id']; ?>">Confirm Deletion</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            Are you sure you want to delete the category "<?php echo htmlspecialchars($category['name']); ?>"?
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <form method="POST" action="">
                                                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                                                <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No categories found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Category Form -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Add New Category</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo isset($category_name) ? htmlspecialchars($category_name) : ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category_description" class="form-label">Description</label>
                                        <textarea class="form-control" id="category_description" name="category_description" rows="3"><?php echo isset($category_description) ? htmlspecialchars($category_description) : ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category_image" class="form-label">Category Image</label>
                                        <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                                        <small class="text-muted">Recommended size: 600x400px</small>
                                    </div>
                                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                                </form>
                            </div>
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