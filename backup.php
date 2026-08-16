<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';

// Get messages from URL parameters (for redirects from delete-backup.php)
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success']) && !empty($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Database backup logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Create backup filename with date and time
        $backup_file = 'hirehive_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_dir = 'backups/';
        
        // Create directory if it doesn't exist
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }
        
        $backup_path = $backup_dir . $backup_file;
        
        // Database credentials from db_connect.php
        // Assumes db_connect.php defines $hostname, $username, $password, $database
        $hostname = "localhost"; // Update with your actual values
        $username = "root";      // Update with your actual values
        $password = "";          // Update with your actual values
        $database = "hirehive";  // Update with your actual values
        
        // Command to execute (using mysqldump)
        $cmd = "mysqldump --opt --host=$hostname --user=$username ";
        if (!empty($password)) {
            $cmd .= "--password=$password ";
        }
        $cmd .= "$database > $backup_path";
        
        // Execute the command
        exec($cmd, $output, $return_var);
        
        if ($return_var === 0) {
            $success_message = "Database backup created successfully: $backup_file";
        } else {
            $error_message = "Error creating database backup. Return code: $return_var";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get list of available backups
$backups = array();
$backup_dir = 'backups/';
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && preg_match('/\.sql$/', $file)) {
            $backups[] = array(
                'filename' => $file,
                'size' => filesize($backup_dir . $file),
                'created' => filemtime($backup_dir . $file)
            );
        }
    }
    
    // Sort backups by creation date (newest first)
    usort($backups, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - HireHive Admin</title>
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
                        <h2 class="mb-0">Database Backup</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h3 class="mb-0">Create Backup</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>Click the button below to create a new backup of the database.</p>
                                        <form method="post">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-download me-2"></i> Create Backup
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-info text-white">
                                        <h3 class="mb-0">Backup Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Database:</strong> hirehive</p>
                                        <p><strong>Backup Location:</strong> backups/</p>
                                        <p><strong>Number of Backups:</strong> <?php echo count($backups); ?></p>
                                        <?php if (count($backups) > 0): ?>
                                            <p><strong>Latest Backup:</strong> <?php echo date('Y-m-d H:i:s', $backups[0]['created']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h3 class="mb-0">Backup History</h3>
                            </div>
                            <div class="card-body">
                                <?php if (count($backups) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Filename</th>
                                                    <th>Size</th>
                                                    <th>Date Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($backups as $backup): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                                        <td><?php echo formatFileSize($backup['size']); ?></td>
                                                        <td><?php echo date('Y-m-d H:i:s', $backup['created']); ?></td>
                                                        <td>
                                                            <a href="<?php echo 'backups/' . urlencode($backup['filename']); ?>" class="btn btn-sm btn-outline-primary" download>
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                            <a href="delete-backup.php?file=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this backup?')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No backups available.</div>
                                <?php endif; ?>
                            </div>
                        </div>
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

<?php
// Helper function to format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
?> 