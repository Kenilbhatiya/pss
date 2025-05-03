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

// Handle testimonial operations (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $comment = $_POST['comment'];
                $rating = $_POST['rating'];
                
                // Handle image upload
                $image_path = '';
                if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../uploads/testimonials/';
                    
                    // Create directory if it doesn't exist
                    if(!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $image_name = time() . '_' . $_FILES['image']['name'];
                    $destination = $upload_dir . $image_name;
                    
                    if(move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        $image_path = 'uploads/testimonials/' . $image_name;
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO testimonials (name, comment, rating, image_path) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $name, $comment, $rating, $image_path);
                $stmt->execute();
                break;
            
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $comment = $_POST['comment'];
                $rating = $_POST['rating'];
                
                // Handle image upload for edit
                $image_path = $_POST['current_image'] ?? '';
                if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../uploads/testimonials/';
                    
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
                        $image_path = 'uploads/testimonials/' . $image_name;
                    }
                }
                
                $stmt = $conn->prepare("UPDATE testimonials SET name = ?, comment = ?, rating = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("ssisi", $name, $comment, $rating, $image_path, $id);
                $stmt->execute();
                break;
            
            case 'delete':
                $id = $_POST['id'];
                
                // Get the image path before deleting
                $stmt = $conn->prepare("SELECT image_path FROM testimonials WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if($row = $result->fetch_assoc()) {
                    // Delete the image file if it exists
                    if(!empty($row['image_path']) && file_exists('../' . $row['image_path'])) {
                        unlink('../' . $row['image_path']);
                    }
                }
                
                // Delete the testimonial
                $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
        }
    }
}

// Check if testimonials table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'testimonials'");
if($table_exists->num_rows == 0) {
    // Create testimonials table if it doesn't exist
    $create_table = "CREATE TABLE `testimonials` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `comment` text NOT NULL,
        `rating` int(11) NOT NULL DEFAULT 5,
        `image_path` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($create_table);
}

// Fetch all testimonials
$result = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC");
$testimonials = [];
if($result && $result->num_rows > 0) {
    $testimonials = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - Plant Nursery</title>
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
                    <h1 class="h2">Testimonials Management</h1>
                </div>
                
                <!-- Add Testimonial Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Add New Testimonial</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label class="form-label">Customer Name:</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Testimonial:</label>
                                <textarea class="form-control" name="comment" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rating (1-5):</label>
                                <select class="form-select" name="rating" required>
                                    <option value="5">5 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="2">2 Stars</option>
                                    <option value="1">1 Star</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer Image:</label>
                                <input type="file" class="form-control" name="image">
                                <small class="text-muted">Recommended size: 200x200 pixels</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Testimonial</button>
                        </form>
                    </div>
                </div>

                <!-- Testimonials List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Existing Testimonials</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Testimonial</th>
                                        <th>Rating</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($testimonials) > 0): ?>
                                        <?php foreach ($testimonials as $testimonial): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($testimonial['id']); ?></td>
                                            <td>
                                                <?php if (!empty($testimonial['image_path'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($testimonial['image_path']); ?>" alt="Customer Image" style="width: 50px; height: 50px; object-fit: cover;" class="rounded-circle">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($testimonial['name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($testimonial['comment'], 0, 100)) . (strlen($testimonial['comment']) > 100 ? '...' : ''); ?></td>
                                            <td>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $testimonial['rating']): ?>
                                                        <i class="fas fa-star text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-warning"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editTestimonial(<?php echo $testimonial['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No testimonials found</td>
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

    <!-- Edit Testimonial Modal -->
    <div class="modal fade" id="editTestimonialModal" tabindex="-1" aria-labelledby="editTestimonialModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-testimonial-id">
                    <input type="hidden" name="current_image" id="edit-current-image">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTestimonialModalLabel">Edit Testimonial</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Customer Name:</label>
                            <input type="text" class="form-control" name="name" id="edit-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Testimonial:</label>
                            <textarea class="form-control" name="comment" id="edit-comment" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating (1-5):</label>
                            <select class="form-select" name="rating" id="edit-rating" required>
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="2">2 Stars</option>
                                <option value="1">1 Star</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Image:</label>
                            <div id="current-image-preview" class="text-center mb-2"></div>
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
        // Store testimonials data for edit
        const testimonials = <?php echo json_encode($testimonials); ?>;
        
        function editTestimonial(id) {
            // Find the testimonial data
            const testimonial = testimonials.find(t => t.id == id);
            if (!testimonial) return;
            
            // Populate the modal
            document.getElementById('edit-testimonial-id').value = testimonial.id;
            document.getElementById('edit-name').value = testimonial.name;
            document.getElementById('edit-comment').value = testimonial.comment;
            document.getElementById('edit-rating').value = testimonial.rating;
            document.getElementById('edit-current-image').value = testimonial.image_path || '';
            
            // Show current image if exists
            const imagePreview = document.getElementById('current-image-preview');
            if (testimonial.image_path) {
                imagePreview.innerHTML = `<img src="../${testimonial.image_path}" alt="Customer Image" style="max-width: 150px; max-height: 150px; object-fit: cover;" class="rounded-circle">`;
            } else {
                imagePreview.innerHTML = '<p>No image uploaded</p>';
            }
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('editTestimonialModal'));
            modal.show();
        }
    </script>
</body>
</html> 