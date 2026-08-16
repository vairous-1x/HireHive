<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';
$job_count = 0;
$user_count = 0;
$message_count = 0;
$company_count = 0;

// Get database statistics
$stats = array();

// Count jobs - using joblistings instead of jobs
$sql = "SELECT COUNT(*) AS count FROM joblistings";
if ($result = $conn->query($sql)) {
    $row = $result->fetch_assoc();
    $job_count = $row['count'];
}

// Count users
$sql = "SELECT COUNT(*) AS count FROM users";
if ($result = $conn->query($sql)) {
    $row = $result->fetch_assoc();
    $user_count = $row['count'];
}

// Count contact messages
$sql = "SELECT COUNT(*) AS count FROM contact_messages";
if ($result = $conn->query($sql)) {
    $row = $result->fetch_assoc();
    $message_count = $row['count'];
}

// Count companies
$sql = "SELECT COUNT(*) AS count FROM enterprises";
if ($result = $conn->query($sql)) {
    $row = $result->fetch_assoc();
    $company_count = $row['count'];
}

// Handle cleanup actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which action was requested
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            switch ($action) {
                case 'old_jobs':
                    // Delete jobs older than 6 months - updated table name
                    $months = isset($_POST['months']) ? intval($_POST['months']) : 6;
                    $sql = "DELETE FROM joblistings WHERE posted_date < DATE_SUB(NOW(), INTERVAL ? MONTH)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $months);
                    $stmt->execute();
                    $affected = $stmt->affected_rows;
                    $stmt->close();
                    
                    $success_message = "Successfully deleted $affected old job listings.";
                    break;
                    
                case 'inactive_users':
                    // Delete users who haven't logged in for a year
                    $months = isset($_POST['months']) ? intval($_POST['months']) : 12;
                    // Since we don't have last_login field, use created_at instead
                    $sql = "DELETE FROM users WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MONTH) AND role NOT IN ('admin', 'enterprise')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $months);
                    $stmt->execute();
                    $affected = $stmt->affected_rows;
                    $stmt->close();
                    
                    $success_message = "Successfully deleted $affected inactive user accounts.";
                    break;
                    
                case 'old_messages':
                    // Delete old contact messages
                    $months = isset($_POST['months']) ? intval($_POST['months']) : 6;
                    // Update field name if needed
                    $sql = "DELETE FROM contact_messages WHERE submission_date < DATE_SUB(NOW(), INTERVAL ? MONTH)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $months);
                    $stmt->execute();
                    $affected = $stmt->affected_rows;
                    $stmt->close();
                    
                    $success_message = "Successfully deleted $affected old contact messages.";
                    break;
                    
                case 'optimize':
                    // Optimize tables - update table names
                    $tables = array('users', 'joblistings', 'enterprises', 'contact_messages', 'jobseekers');
                    $optimized = 0;
                    
                    foreach ($tables as $table) {
                        $sql = "OPTIMIZE TABLE $table";
                        if ($conn->query($sql)) {
                            $optimized++;
                        }
                    }
                    
                    $success_message = "Successfully optimized $optimized database tables.";
                    break;
                    
                default:
                    $error_message = "Unknown action requested.";
                    break;
            }
            
            // Commit transaction
            $conn->commit();
            
            // Refresh counts after cleanup
            $sql = "SELECT COUNT(*) AS count FROM joblistings";
            if ($result = $conn->query($sql)) {
                $row = $result->fetch_assoc();
                $job_count = $row['count'];
            }
            
            $sql = "SELECT COUNT(*) AS count FROM users";
            if ($result = $conn->query($sql)) {
                $row = $result->fetch_assoc();
                $user_count = $row['count'];
            }
            
            $sql = "SELECT COUNT(*) AS count FROM contact_messages";
            if ($result = $conn->query($sql)) {
                $row = $result->fetch_assoc();
                $message_count = $row['count'];
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = "No action specified.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Cleanup - HireHive Admin</title>
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
                        <h2 class="mb-0">Database Cleanup</h2>
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
                                <div class="card h-100">
                                    <div class="card-header bg-primary text-white">
                                        <h3 class="mb-0">Database Statistics</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="list-group">
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                Job Listings
                                                <span class="badge bg-primary rounded-pill"><?php echo $job_count; ?></span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                User Accounts
                                                <span class="badge bg-primary rounded-pill"><?php echo $user_count; ?></span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                Contact Messages
                                                <span class="badge bg-primary rounded-pill"><?php echo $message_count; ?></span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                Enterprises
                                                <span class="badge bg-primary rounded-pill"><?php echo $company_count; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-warning text-dark">
                                        <h3 class="mb-0">Cleanup Warning</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning">
                                            <h4><i class="fas fa-exclamation-triangle"></i> Warning</h4>
                                            <p>Cleanup operations permanently delete data from the database. This action cannot be undone.</p>
                                            <p>It is recommended to create a backup before performing any cleanup operations.</p>
                                            <a href="backup.php" class="btn btn-warning">
                                                <i class="fas fa-database me-2"></i> Backup Database
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h3 class="mb-0">Cleanup Options</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-info text-white">
                                                <h4 class="mb-0">Remove Old Job Listings</h4>
                                            </div>
                                            <div class="card-body">
                                                <p>Remove job listings that are older than the specified number of months.</p>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="old_jobs">
                                                    <div class="mb-3">
                                                        <label for="jobMonths" class="form-label">Months:</label>
                                                        <input type="number" class="form-control" id="jobMonths" name="months" value="6" min="1" max="60">
                                                        <div class="form-text">Job listings older than this many months will be removed.</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete old job listings? This cannot be undone.')">
                                                        <i class="fas fa-trash me-2"></i> Remove Old Jobs
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-info text-white">
                                                <h4 class="mb-0">Remove Inactive Users</h4>
                                            </div>
                                            <div class="card-body">
                                                <p>Remove user accounts that have been inactive for the specified number of months.</p>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="inactive_users">
                                                    <div class="mb-3">
                                                        <label for="userMonths" class="form-label">Months:</label>
                                                        <input type="number" class="form-control" id="userMonths" name="months" value="12" min="3" max="60">
                                                        <div class="form-text">Users inactive for this many months will be removed.</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete inactive user accounts? This cannot be undone.')">
                                                        <i class="fas fa-user-times me-2"></i> Remove Inactive Users
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-info text-white">
                                                <h4 class="mb-0">Remove Old Messages</h4>
                                            </div>
                                            <div class="card-body">
                                                <p>Remove contact messages that are older than the specified number of months.</p>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="old_messages">
                                                    <div class="mb-3">
                                                        <label for="messageMonths" class="form-label">Months:</label>
                                                        <input type="number" class="form-control" id="messageMonths" name="months" value="6" min="1" max="60">
                                                        <div class="form-text">Messages older than this many months will be removed.</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete old contact messages? This cannot be undone.')">
                                                        <i class="fas fa-envelope-open-text me-2"></i> Remove Old Messages
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-info text-white">
                                                <h4 class="mb-0">Optimize Database</h4>
                                            </div>
                                            <div class="card-body">
                                                <p>Optimize database tables to improve performance and reclaim unused space.</p>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="optimize">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-database me-2"></i> Optimize Database
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
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