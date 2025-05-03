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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        $settings = [
            'site_name' => $_POST['site_name'],
            'site_email' => $_POST['site_email'],
            'currency' => $_POST['currency'],
            'shipping_cost' => $_POST['shipping_cost'],
            'tax_rate' => $_POST['tax_rate'],
            'min_order_amount' => $_POST['min_order_amount'],
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];

        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
        }

        $message = "Settings updated successfully!";
    }
}

// Fetch current settings
$result = $conn->query("SELECT * FROM settings");
$settings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['name']] = $row['value'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Plant Nursery</title>
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
                    <h1 class="h2">Site Settings</h1>
                </div>
                
                <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- General Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">General Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Site Name:</label>
                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Site Email:</label>
                                <input type="email" class="form-control" name="site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Store Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Store Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Currency:</label>
                                <select class="form-select" name="currency">
                                    <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP" <?php echo ($settings['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Shipping Cost:</label>
                                <input type="number" class="form-control" name="shipping_cost" step="0.01" value="<?php echo htmlspecialchars($settings['shipping_cost'] ?? '0'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tax Rate (%):</label>
                                <input type="number" class="form-control" name="tax_rate" step="0.01" value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Minimum Order Amount:</label>
                                <input type="number" class="form-control" name="min_order_amount" step="0.01" value="<?php echo htmlspecialchars($settings['min_order_amount'] ?? '0'); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Site Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Site Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] ?? '') == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    Enable Maintenance Mode
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 