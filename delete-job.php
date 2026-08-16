<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an enterprise
check_role("enterprise");

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard-enterprise.php#job-listings");
    exit;
}

$job_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error_message = '';
$success_message = '';
$job = null;

// Get enterprise_id
$stmt = $conn->prepare("SELECT enterprise_id FROM enterprises WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$enterprise = $result->fetch_assoc();
$enterprise_id = $enterprise['enterprise_id'];
$stmt->close();

// Get job details for this enterprise
$job_sql = "SELECT jl.* 
            FROM joblistings jl
            JOIN enterprises e ON jl.enterprise_id = e.enterprise_id
            WHERE jl.job_id = ? AND e.user_id = ?";

$stmt = $conn->prepare($job_sql);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard-enterprise.php?error=notfound");
    exit;
}

$job = $result->fetch_assoc();
$stmt->close();

// Process deletion if confirmed
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check for related applications
        $app_sql = "SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?";
        $stmt = $conn->prepare($app_sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $app_result = $stmt->get_result();
        $app_count = $app_result->fetch_assoc()['count'];
        $stmt->close();
        
        // If there are applications, mark job as deleted but keep in DB
        if ($app_count > 0) {
            $sql = "UPDATE joblistings SET status = 'deleted' WHERE job_id = ? AND enterprise_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $job_id, $enterprise_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error deleting job: " . $stmt->error);
            }
            
            $stmt->close();
        } else {
            // No applications, delete the job completely
            $sql = "DELETE FROM joblistings WHERE job_id = ? AND enterprise_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $job_id, $enterprise_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error deleting job: " . $stmt->error);
            }
            
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect back to dashboard with success message
        header("Location: dashboard-enterprise.php?success=deleted#job-listings");
        exit;
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $error_message = $e->getMessage();
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
    <title>Delete Job - HireHive</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="assets/images/logo.png" alt="HireHive Logo" height="40">
                HireHive
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-enterprise.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-enterprise.php#job-listings">Job Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-enterprise.php#applications">Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-enterprise.php#advertisements">Advertisements</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h1 class="card-title h5 mb-0">Delete Job: <?php echo htmlspecialchars($job['title']); ?></h1>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> You are about to delete this job listing. This action cannot be undone.
                        </div>
                        
                        <div class="mb-4">
                            <h5>Job Details:</h5>
                            <ul class="list-group mb-3">
                                <li class="list-group-item">
                                    <strong>Title:</strong> <?php echo htmlspecialchars($job['title']); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Posted Date:</strong> <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($job['status'])); ?>
                                </li>
                            </ul>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $job_id); ?>">
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="confirm" onchange="document.getElementById('confirm_btn').disabled = !this.checked;">
                                <label class="form-check-label" for="confirm">
                                    I understand that I am about to delete this job listing and all related information.
                                </label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="view-job.php?id=<?php echo $job_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                
                                <input type="hidden" name="confirm_delete" value="yes">
                                <button type="submit" id="confirm_btn" class="btn btn-danger" disabled>
                                    <i class="fas fa-trash me-2"></i>Delete Job
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; HireHive. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 