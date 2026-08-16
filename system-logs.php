<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';
$log_files = array();
$current_log = '';
$log_content = '';

// Define log directories to check
$log_directories = array(
    'logs/',                        // Application logs
    '../logs/',                     // One level up logs
    'D:/xampp/apache/logs/',       // XAMPP Apache logs
    'D:/xampp/mysql/data/logs/',   // XAMPP MySQL logs
    'D:/xampp/php/logs/'           // XAMPP PHP logs
);

// Check if a specific log file is requested
if (isset($_GET['log']) && !empty($_GET['log'])) {
    $requested_log = $_GET['log'];
    $log_found = false;
    
    // Security check (prevent directory traversal)
    if (strpos($requested_log, '..') !== false || strpos($requested_log, '/') !== false) {
        $error_message = "Invalid log file specified";
    } else {
        // Search for the requested log in all directories
        foreach ($log_directories as $dir) {
            $log_path = $dir . $requested_log;
            if (file_exists($log_path) && is_file($log_path)) {
                $current_log = $requested_log;
                $log_content = file_get_contents($log_path);
                $log_found = true;
                break;
            }
        }
        
        if (!$log_found) {
            $error_message = "Log file not found";
        }
    }
}

// Scan for available log files
foreach ($log_directories as $dir) {
    if (file_exists($dir) && is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && is_file($dir . $file) && 
                (strpos($file, '.log') !== false || strpos($file, '.txt') !== false)) {
                $log_files[] = array(
                    'filename' => $file,
                    'size' => filesize($dir . $file),
                    'modified' => filemtime($dir . $file),
                    'path' => $dir
                );
            }
        }
    }
}

// Sort log files by modification date (newest first)
usort($log_files, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - HireHive Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-content {
            max-height: 500px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
        }
        .log-table tr {
            cursor: pointer;
        }
    </style>
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
                        <h2 class="mb-0">System Logs</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h3 class="mb-0">Available Log Files</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($log_files) > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover log-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Filename</th>
                                                            <th>Size</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($log_files as $log): ?>
                                                            <tr onclick="window.location='system-logs.php?log=<?php echo urlencode($log['filename']); ?>'">
                                                                <td><?php echo htmlspecialchars($log['filename']); ?></td>
                                                                <td><?php echo formatFileSize($log['size']); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">No log files found.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h3 class="mb-0"><?php echo !empty($current_log) ? htmlspecialchars($current_log) : 'Log Content'; ?></h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($log_content)): ?>
                                            <div class="log-content">
                                                <?php echo htmlspecialchars($log_content); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">Select a log file to view its contents.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
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