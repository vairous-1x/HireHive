<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';

// Define default settings if they don't exist
$settings = array(
    'site_name' => 'HireHive',
    'site_description' => 'Connect with top talent and employers',
    'admin_email' => 'admin@hirehive.com',
    'jobs_per_page' => 10,
    'allow_registrations' => 1,
    'maintenance_mode' => 0
);

// Create settings table if it doesn't exist
$check_table_sql = "SHOW TABLES LIKE 'site_settings'";
$result = $conn->query($check_table_sql);
if ($result->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_sql = "CREATE TABLE site_settings (
        setting_name VARCHAR(50) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql)) {
        // Insert default settings
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
            $stmt->close();
        }
        $success_message = "Settings table created and initialized with default values.";
    } else {
        $error_message = "Error creating settings table: " . $conn->error;
    }
} else {
    // Table exists, load current settings
    $sql = "SELECT * FROM site_settings";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update settings
    $conn->begin_transaction();
    
    try {
        foreach ($_POST as $key => $value) {
            // Skip submit button and any non-setting fields
            if ($key == 'submit' || !array_key_exists($key, $settings)) {
                continue;
            }
            
            // For checkboxes, convert to 0/1
            if ($key == 'allow_registrations' || $key == 'maintenance_mode') {
                $value = isset($_POST[$key]) ? 1 : 0;
            }
            
            // Update the setting
            $sql = "UPDATE site_settings SET setting_value = ? WHERE setting_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
            $stmt->close();
            
            // Update local array for display
            $settings[$key] = $value;
        }
        
        $conn->commit();
        $success_message = "Settings updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - HireHive Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="assets/images/logo.png" alt="HireHive Logo" height="40">
                HireHive Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-admin.php#users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-admin.php#messages">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-admin.php#settings">Settings</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-outline-light">Log Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h2 class="mb-0">Site Settings</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-primary text-white">
                                            <h3 class="mb-0">General Settings</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="site_name" class="form-label">Site Name</label>
                                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="site_description" class="form-label">Site Description</label>
                                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="admin_email" class="form-label">Admin Email</label>
                                                <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-info text-white">
                                            <h3 class="mb-0">System Settings</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="jobs_per_page" class="form-label">Jobs Per Page</label>
                                                <input type="number" class="form-control" id="jobs_per_page" name="jobs_per_page" value="<?php echo htmlspecialchars($settings['jobs_per_page']); ?>" min="5" max="50" required>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="allow_registrations" name="allow_registrations" <?php echo $settings['allow_registrations'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="allow_registrations">Allow User Registrations</label>
                                                <div class="form-text">Disabling this will prevent new users from registering.</div>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                                <div class="form-text">Enable this to show a maintenance page to visitors.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; HireHive. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-decoration-none text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-decoration-none text-white me-3">Terms of Service</a>
                    <a href="#" class="text-decoration-none text-white">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 