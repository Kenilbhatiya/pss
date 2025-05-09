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

// Define settings groups and their settings
$settings_groups = [
    'general' => [
        'title' => 'General Settings',
        'settings' => [
            'site_title' => [
                'label' => 'Site Title',
                'type' => 'text',
                'description' => 'The title of your website',
            ],
            'site_description' => [
                'label' => 'Site Description',
                'type' => 'textarea',
                'description' => 'A short description of your website (used in meta tags)',
            ],
            'contact_email' => [
                'label' => 'Contact Email',
                'type' => 'email',
                'description' => 'The main contact email address',
            ],
            'contact_phone' => [
                'label' => 'Contact Phone',
                'type' => 'text',
                'description' => 'The main contact phone number',
            ],
        ],
    ],
    'store' => [
        'title' => 'Store Settings',
        'settings' => [
            'currency_symbol' => [
                'label' => 'Currency Symbol',
                'type' => 'text',
                'description' => 'The currency symbol (e.g., ₹, $, €)',
            ],
            'currency_code' => [
                'label' => 'Currency Code',
                'type' => 'text',
                'description' => 'The currency code (e.g., INR, USD, EUR)',
            ],
            'min_order_amount' => [
                'label' => 'Minimum Order Amount',
                'type' => 'number',
                'description' => 'Minimum order amount for checkout',
            ],
            'shipping_fee' => [
                'label' => 'Shipping Fee',
                'type' => 'number',
                'description' => 'Standard shipping fee',
            ],
            'free_shipping_threshold' => [
                'label' => 'Free Shipping Threshold',
                'type' => 'number',
                'description' => 'Order amount above which shipping is free',
            ],
        ],
    ],
    'appearance' => [
        'title' => 'Appearance Settings',
        'settings' => [
            'primary_color' => [
                'label' => 'Primary Color',
                'type' => 'color',
                'description' => 'Primary color for buttons and accents',
            ],
            'secondary_color' => [
                'label' => 'Secondary Color',
                'type' => 'color',
                'description' => 'Secondary color for elements',
            ],
            'enable_dark_mode' => [
                'label' => 'Enable Dark Mode',
                'type' => 'checkbox',
                'description' => 'Allow users to switch to dark mode',
            ],
            'logo_path' => [
                'label' => 'Logo Path',
                'type' => 'text',
                'description' => 'Path to the site logo image',
            ],
        ],
    ],
];

// Get current settings from database
$settings = [];
$settings_query = "SELECT name, value FROM settings";
$settings_result = mysqli_query($conn, $settings_query);

if($settings_result) {
    while($row = mysqli_fetch_assoc($settings_result)) {
        $settings[$row['name']] = $row['value'];
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_settings = $_POST['settings'] ?? [];
    $errors = [];
    $success = false;
    
    foreach($updated_settings as $name => $value) {
        // Validate settings here if needed
        
        // Check if setting exists
        $check_query = "SELECT id FROM settings WHERE name = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            // Update existing setting
            $update_query = "UPDATE settings SET value = ? WHERE name = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ss", $value, $name);
            
            if(!mysqli_stmt_execute($stmt)) {
                $errors[] = "Error updating setting '$name': " . mysqli_error($conn);
            }
        } else {
            // Insert new setting
            $insert_query = "INSERT INTO settings (name, value) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "ss", $name, $value);
            
            if(!mysqli_stmt_execute($stmt)) {
                $errors[] = "Error adding setting '$name': " . mysqli_error($conn);
            }
        }
        
        // Update local settings array
        $settings[$name] = $value;
    }
    
    if(empty($errors)) {
        $success = true;
    }
}

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
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
                    <h1 class="h2">Settings</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye"></i> View Website
                        </a>
                    </div>
                </div>

                <?php if(isset($success) && $success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Settings saved successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4">
                            <?php foreach($settings_groups as $group_key => $group): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $active_tab === $group_key ? 'active' : ''; ?>" href="?tab=<?php echo $group_key; ?>">
                                        <?php echo $group['title']; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if(isset($settings_groups[$active_tab])): ?>
                            <form method="POST" action="?tab=<?php echo $active_tab; ?>">
                                <div class="row">
                                    <?php foreach($settings_groups[$active_tab]['settings'] as $setting_key => $setting): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <?php if($setting['type'] === 'checkbox'): ?>
                                                        <div class="form-check">
                                                            <input 
                                                                type="checkbox" 
                                                                class="form-check-input" 
                                                                id="<?php echo $setting_key; ?>"
                                                                name="settings[<?php echo $setting_key; ?>]"
                                                                value="1"
                                                                <?php echo isset($settings[$setting_key]) && $settings[$setting_key] == 1 ? 'checked' : ''; ?>
                                                            >
                                                            <label class="form-check-label" for="<?php echo $setting_key; ?>">
                                                                <?php echo $setting['label']; ?>
                                                            </label>
                                                        </div>
                                                    <?php else: ?>
                                                        <label for="<?php echo $setting_key; ?>" class="form-label">
                                                            <?php echo $setting['label']; ?>
                                                        </label>
                                                        
                                                        <?php if($setting['type'] === 'textarea'): ?>
                                                            <textarea 
                                                                class="form-control" 
                                                                id="<?php echo $setting_key; ?>"
                                                                name="settings[<?php echo $setting_key; ?>]"
                                                                rows="3"
                                                            ><?php echo isset($settings[$setting_key]) ? htmlspecialchars($settings[$setting_key]) : ''; ?></textarea>
                                                        <?php else: ?>
                                                            <input 
                                                                type="<?php echo $setting['type']; ?>" 
                                                                class="form-control" 
                                                                id="<?php echo $setting_key; ?>"
                                                                name="settings[<?php echo $setting_key; ?>]"
                                                                value="<?php echo isset($settings[$setting_key]) ? htmlspecialchars($settings[$setting_key]) : ''; ?>"
                                                            >
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if(isset($setting['description'])): ?>
                                                        <div class="form-text"><?php echo $setting['description']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                    <a href="?tab=<?php echo $active_tab; ?>" class="btn btn-outline-secondary ms-2">Reset</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                Invalid settings tab.
                            </div>
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