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

// Process testimonial addition
$success_message = "";
$error_message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_testimonial'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);
        $rating = intval($_POST['rating']);
        
        // Validate input
        if(empty($name) || empty($comment) || $rating < 1 || $rating > 5) {
            $error_message = "Please fill in all fields correctly.";
        } else {
            // Process image upload if exists
            $image_path = '';
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploads_dir = '../images/testimonials/';
                
                // Create directory if it doesn't exist
                if(!file_exists($uploads_dir)) {
                    mkdir($uploads_dir, 0777, true);
                }
                
                $tmp_name = $_FILES['image']['tmp_name'];
                $image_name = basename($_FILES['image']['name']);
                $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
                $unique_image_name = 'testimonial_' . time() . '_' . uniqid() . '.' . $image_ext;
                
                if(move_uploaded_file($tmp_name, $uploads_dir . $unique_image_name)) {
                    $image_path = 'images/testimonials/' . $unique_image_name;
                } else {
                    $error_message = "Error uploading image.";
                }
            }
            
            // Insert testimonial into database
            if(empty($error_message)) {
                $query = "INSERT INTO testimonials (name, image_path, comment, rating, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssi", $name, $image_path, $comment, $rating);
                
                if(mysqli_stmt_execute($stmt)) {
                    $success_message = "Testimonial added successfully!";
                } else {
                    $error_message = "Error adding testimonial: " . mysqli_error($conn);
                }
            }
        }
    } elseif(isset($_POST['delete_testimonial'])) {
        $testimonial_id = intval($_POST['testimonial_id']);
        
        // Get image path before deletion
        $query = "SELECT image_path FROM testimonials WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $testimonial_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        // Delete testimonial
        $query = "DELETE FROM testimonials WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $testimonial_id);
        
        if(mysqli_stmt_execute($stmt)) {
            // Delete image file if exists
            if(!empty($row['image_path']) && file_exists('../' . $row['image_path'])) {
                unlink('../' . $row['image_path']);
            }
            
            $success_message = "Testimonial deleted successfully!";
        } else {
            $error_message = "Error deleting testimonial: " . mysqli_error($conn);
        }
    }
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total records count for pagination
$count_query = "SELECT COUNT(*) as total FROM testimonials";
$count_result = mysqli_query($conn, $count_query);
$row = mysqli_fetch_assoc($count_result);
$total_records = $row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get testimonials with pagination
$testimonials_query = "SELECT t.*, u.username, u.email 
                      FROM testimonials t
                      LEFT JOIN users u ON t.user_id = u.id
                      ORDER BY t.created_at DESC LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $testimonials_query);
mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$testimonials = [];
if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $testimonials[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - Admin Dashboard</title>
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
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye"></i> View Website
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Testimonials List -->
                    <div class="col-md-8 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">All Testimonials</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Image</th>
                                                <th>Name</th>
                                                <th>Comment</th>
                                                <th>Rating</th>
                                                <th>User</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($testimonials) > 0): ?>
                                                <?php foreach($testimonials as $testimonial): ?>
                                                    <tr>
                                                        <td>#<?php echo $testimonial['id']; ?></td>
                                                        <td>
                                                            <?php if(!empty($testimonial['image_path'])): ?>
                                                                <img src="../<?php echo $testimonial['image_path']; ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" class="img-thumbnail" width="50" height="50" style="object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                    <?php echo strtoupper(substr($testimonial['name'], 0, 1)); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($testimonial['name']); ?></td>
                                                        <td>
                                                            <?php
                                                                $comment = htmlspecialchars($testimonial['comment']);
                                                                echo strlen($comment) > 50 ? substr($comment, 0, 50) . '...' : $comment;
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <div class="text-warning">
                                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                                <?php if($i <= $testimonial['rating']): ?>
                                                                    <i class="fas fa-star"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-star"></i>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if(!empty($testimonial['username'])): ?>
                                                                <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($testimonial['email']); ?>">
                                                                    <i class="fas fa-user text-primary me-1"></i> <?php echo htmlspecialchars($testimonial['username']); ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted"><i class="fas fa-user-slash me-1"></i> Not logged in</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($testimonial['created_at'])); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $testimonial['id']; ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            
                                                            <!-- Delete Modal -->
                                                            <div class="modal fade" id="deleteModal<?php echo $testimonial['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            Are you sure you want to delete this testimonial from <strong><?php echo htmlspecialchars($testimonial['name']); ?></strong>?
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <form action="" method="POST">
                                                                                <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                                                                <button type="submit" name="delete_testimonial" class="btn btn-danger">Delete</button>
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
                                                    <td colspan="7" class="text-center">No testimonials found</td>
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
                                                <a class="page-link" href="?page=<?php echo $page-1; ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page+1; ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Testimonial Form -->
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Add New Testimonial</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="comment" class="form-label">Comment <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="rating" class="form-label">Rating <span class="text-danger">*</span></label>
                                        <select class="form-select" id="rating" name="rating" required>
                                            <option value="">Select Rating</option>
                                            <option value="5">5 Stars</option>
                                            <option value="4">4 Stars</option>
                                            <option value="3">3 Stars</option>
                                            <option value="2">2 Stars</option>
                                            <option value="1">1 Star</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Customer Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <small class="text-muted">Optional. Recommended size: 200x200px</small>
                                    </div>
                                    <button type="submit" name="add_testimonial" class="btn btn-success w-100">Add Testimonial</button>
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
    
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
</html> 