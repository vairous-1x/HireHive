<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an enterprise
check_role("enterprise");

// Check if application ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard-enterprise.php#applications");
    exit;
}

$application_id = $_GET['id'];
$user_id = $_SESSION["user_id"];

// Get application details
$application = null;
$application_sql = "SELECT ja.*, jl.title as job_title, jl.job_id, js.full_name, js.phone, 
                          js.profession, js.education, js.skills, js.jobseeker_id, 
                          u.email, e.enterprise_id
                   FROM job_applications ja
                   JOIN joblistings jl ON ja.job_id = jl.job_id
                   JOIN jobseekers js ON ja.jobseeker_id = js.jobseeker_id
                   JOIN users u ON js.user_id = u.user_id
                   JOIN enterprises e ON jl.enterprise_id = e.enterprise_id
                   WHERE ja.application_id = ? AND e.user_id = ?";

$stmt = $conn->prepare($application_sql);
$stmt->bind_param("ii", $application_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard-enterprise.php?error=notfound");
    exit;
}

$application = $result->fetch_assoc();
$stmt->close();

// Process status update if form is submitted
$status_updated = false;
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $feedback = isset($_POST['feedback']) ? $_POST['feedback'] : '';
    
    // Valid statuses
    $valid_statuses = ['pending', 'reviewed', 'interviewed', 'offered', 'rejected', 'accepted'];
    
    if (in_array($new_status, $valid_statuses)) {
        // Only update the status field without attempting to update the feedback field
        $update_sql = "UPDATE job_applications SET status = ? WHERE application_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_status, $application_id);
        
        if ($stmt->execute()) {
            $status_updated = true;
            $application['status'] = $new_status;
            // No need to update feedback in application array since we're not storing it
        } else {
            $error_message = "Error updating application status: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Invalid status provided";
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
    <title>Review Application - HireHive</title>
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
                        <a class="nav-link" href="dashboard-enterprise.php#job-listings">Job Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-enterprise.php#applications">Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search-candidates.php">Find Candidates</a>
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Review Application</h1>
                    <div>
                        <a href="view-job.php?id=<?php echo $application['job_id']; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-eye"></i> View Job
                        </a>
                        <a href="dashboard-enterprise.php#applications" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($status_updated): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Application status has been updated successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Job & Application Info -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="card-title h5 mb-0">Application for: <?php echo htmlspecialchars($application['job_title']); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h3 class="h6 fw-bold">Application Date</h3>
                                <p><?php echo date('F d, Y', strtotime($application['application_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h3 class="h6 fw-bold">Current Status</h3>
                                <p>
                                    <span class="badge <?php
                                        switch($application['status']) {
                                            case 'pending': echo 'bg-warning'; break;
                                            case 'reviewed': echo 'bg-info'; break;
                                            case 'interviewed': echo 'bg-primary'; break;
                                            case 'offered': echo 'bg-success'; break;
                                            case 'rejected': echo 'bg-danger'; break;
                                            case 'accepted': echo 'bg-success'; break;
                                            default: echo 'bg-secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($application['status'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <?php if (!empty($application['cover_letter'])): ?>
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Cover Letter</h3>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($application['feedback']) && !empty($application['feedback'])): ?>
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Your Feedback</h3>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($application['feedback'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Update Status Form -->
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $application_id); ?>" class="mt-4">
                            <h3 class="h6 fw-bold mb-3">Update Application Status</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <select class="form-select" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="pending" <?php if ($application['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                        <option value="reviewed" <?php if ($application['status'] == 'reviewed') echo 'selected'; ?>>Reviewed</option>
                                        <option value="interviewed" <?php if ($application['status'] == 'interviewed') echo 'selected'; ?>>Interviewed</option>
                                        <option value="offered" <?php if ($application['status'] == 'offered') echo 'selected'; ?>>Offer Extended</option>
                                        <option value="rejected" <?php if ($application['status'] == 'rejected') echo 'selected'; ?>>Rejected</option>
                                        <option value="accepted" <?php if ($application['status'] == 'accepted') echo 'selected'; ?>>Accepted</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                </div>
                                <div class="col-12">
                                    <label for="feedback" class="form-label">Feedback/Notes (Optional)</label>
                                    <textarea class="form-control" id="feedback" name="feedback" rows="3"><?php echo isset($application['feedback']) ? htmlspecialchars($application['feedback']) : ''; ?></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Applicant Info -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Applicant Information</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="h6 fw-bold mb-3"><?php echo htmlspecialchars($application['full_name']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($application['profession']); ?></p>
                        
                        <h5 class="h6 fw-bold">Contact</h5>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item px-0">
                                <i class="fas fa-envelope me-2 text-muted"></i>
                                <?php echo htmlspecialchars($application['email']); ?>
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-phone me-2 text-muted"></i>
                                <?php echo htmlspecialchars($application['phone']); ?>
                            </li>
                        </ul>
                        
                        <h5 class="h6 fw-bold">Education</h5>
                        <p class="mb-3"><?php echo htmlspecialchars($application['education']); ?></p>
                        
                        <h5 class="h6 fw-bold">Skills</h5>
                        <div class="skills-container mb-3">
                            <?php
                            $skills = explode(',', $application['skills']);
                            foreach ($skills as $skill):
                                if (trim($skill) != ''):
                            ?>
                                <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars(trim($skill)); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>?subject=Your application for <?php echo htmlspecialchars($application['job_title']); ?>" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-envelope me-2"></i> Email Candidate
                        </a>
                        <a href="view-candidate.php?id=<?php echo $application['jobseeker_id']; ?>" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-user me-2"></i> View Full Profile
                        </a>
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