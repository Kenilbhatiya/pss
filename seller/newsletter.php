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

// Handle newsletter operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_newsletter':
                $subject = $_POST['subject'];
                $content = $_POST['content'];
                $subscribers = $conn->query("SELECT email FROM newsletter_subscribers WHERE status = 'active'")->fetch_all(MYSQLI_ASSOC);
                
                // In a real application, you would want to:
                // 1. Use a proper email service (like SendGrid, Mailgun, etc.)
                // 2. Queue the emails for sending
                // 3. Handle bounces and failures
                // For demo purposes, we'll just show a success message
                $message = "Newsletter scheduled for sending to " . count($subscribers) . " subscribers.";
                break;

            case 'delete_subscriber':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;

            case 'toggle_status':
                $id = $_POST['id'];
                $status = $_POST['status'];
                $new_status = $status === 'active' ? 'inactive' : 'active';
                $stmt = $conn->prepare("UPDATE newsletter_subscribers SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $id);
                $stmt->execute();
                break;
        }
    }
}

// Fetch all subscribers
$subscribers = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Management - Plant Nursery</title>
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
                    <h1 class="h2">Newsletter Management</h1>
                </div>
                
                <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Send Newsletter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Send Newsletter</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="send_newsletter">
                            <div class="mb-3">
                                <label class="form-label">Subject:</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content:</label>
                                <textarea class="form-control" name="content" rows="10" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Newsletter</button>
                        </form>
                    </div>
                </div>

                <!-- Subscribers List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Newsletter Subscribers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Subscribed Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscribers as $subscriber): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo $subscriber['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $subscriber['status']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $subscriber['status'] === 'active' ? 'btn-success' : 'btn-secondary'; ?>">
                                                    <?php echo ucfirst($subscriber['status']); ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($subscriber['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_subscriber">
                                                <input type="hidden" name="id" value="<?php echo $subscriber['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subscriber?')">
                                                    <i class="fas fa-trash"></i>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 