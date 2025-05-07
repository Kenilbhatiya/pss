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

// Handle category operations (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                // Handle image upload
                $image_path = '';
                if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../uploads/categories/';
                    
                    // Create directory if it doesn't exist
                    if(!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $image_name = time() . '_' . $_FILES['image']['name'];
                    $destination = $upload_dir . $image_name;
                    
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        $image_path = 'uploads/categories/' . $image_name;
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO categories (name, description, image, seller_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $name, $description, $image_path, $seller_id);
                $stmt->execute();
                break;
            
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                
                // Handle image upload for edit
                $image_path = $_POST['current_image'] ?? '';
                if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../uploads/categories/';
                    
                    // Create directory if it doesn't exist
                    if(!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $image_name = time() . '_' . $_FILES['image']['name'];
                    $destination = $upload_dir . $image_name;
                    
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        // Delete old image if exists
                        if(!empty($image_path) && file_exists('../' . $image_path)) {
                            unlink('../' . $image_path);
                        }
                        $image_path = 'uploads/categories/' . $image_name;
                    }
                }
                
                $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ? AND seller_id = ?");
                $stmt->bind_param("sssii", $name, $description, $image_path, $id, $seller_id);
                $stmt->execute();
                break;
            
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND seller_id = ?");
                $stmt->bind_param("ii", $id, $seller_id);
                $stmt->execute();
                break;
        }
    }
}

// Check if category table has image column
$result = $conn->query("SHOW COLUMNS FROM categories LIKE 'image'");
if($result->num_rows == 0) {
    // Add image column if it doesn't exist
    $conn->query("ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL");
}

// Check if category table has seller_id column
$result = $conn->query("SHOW COLUMNS FROM categories LIKE 'seller_id'");
if($result->num_rows == 0) {
    // Add seller_id column if it doesn't exist
    $conn->query("ALTER TABLE categories ADD COLUMN seller_id INT DEFAULT NULL");
}

// Fetch all categories for this seller
$stmt = $conn->prepare("SELECT * FROM categories WHERE seller_id = ? ORDER BY name");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Plant Nursery</title>
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
                    <h1 class="h2">Categories Management</h1>
                </div>
                
                <!-- Add Category Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label class="form-label">Name:</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description:</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image:</label>
                                <input type="file" class="form-control" name="image">
                            </div>
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Existing Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Image</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['id']); ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td>
                                            <?php if (!empty($category['image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($category['image']); ?>" alt="Category Image" style="width: 100px; height: 100px; object-fit: cover;">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-category-id">
                    <input type="hidden" name="current_image" id="edit-current-image">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name:</label>
                            <input type="text" class="form-control" name="name" id="edit-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description:</label>
                            <textarea class="form-control" name="description" id="edit-description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Image:</label>
                            <div id="current-image-preview"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload New Image:</label>
                            <input type="file" class="form-control" name="image">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store categories data for edit
        const categories = <?php echo json_encode($categories); ?>;
        
        function editCategory(id) {
            // Find the category data
            const category = categories.find(c => c.id == id);
            if (!category) return;
            
            // Populate the modal
            document.getElementById('edit-category-id').value = category.id;
            document.getElementById('edit-name').value = category.name;
            document.getElementById('edit-description').value = category.description;
            document.getElementById('edit-current-image').value = category.image || '';
            
            // Show current image if exists
            const imagePreview = document.getElementById('current-image-preview');
            if (category.image) {
                imagePreview.innerHTML = `<img src="../${category.image}" alt="Category Image" style="max-width: 100%; max-height: 200px; object-fit: cover;">`;
            } else {
                imagePreview.innerHTML = '<p>No image uploaded</p>';
            }
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }
    </script>
</body>
</html> 