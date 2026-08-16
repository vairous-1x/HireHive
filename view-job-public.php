<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to access session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once "db_connect.php";

// Get job ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.html");
    exit;
}

$job_id = $_GET['id'];

// Get job details
$job = null;
$job_sql = "SELECT jl.*, e.company_name 
            FROM joblistings jl
            JOIN enterprises e ON jl.enterprise_id = e.enterprise_id
            WHERE jl.job_id = ? AND jl.status = 'open'";

$stmt = $conn->prepare($job_sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.html?error=notfound");
    exit;
}

$job = $result->fetch_assoc();
$stmt->close();

// Close connection
$conn->close();

// Determine if user is logged in
$isLoggedIn = false;
$isJobSeeker = false;
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $isLoggedIn = true;
    $isJobSeeker = ($_SESSION["role"] === "job-seeker");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - HireHive</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isJobSeeker): ?>
                            <a href="dashboard-job-seeker.php" class="btn btn-outline-light me-2">Dashboard</a>
                        <?php else: ?>
                            <a href="dashboard-enterprise.php" class="btn btn-outline-light me-2">Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-outline-light">Log Out</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">Log In</a>
                        <a href="register.php" class="btn btn-outline-light">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                    <a href="index.html" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Job Details -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="card-title h5 mb-0">Job Details</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($job['salary_range'])): ?>
                            <div class="mb-4">
                                <h3 class="h6 fw-bold">Salary Range</h3>
                                <p><?php echo htmlspecialchars($job['salary_range']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Requirements</h3>
                            <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                        </div>
                        
                        <?php if (!empty($job['responsibilities'])): ?>
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Responsibilities</h3>
                            <p><?php echo nl2br(htmlspecialchars($job['responsibilities'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($job['benefits'])): ?>
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Benefits</h3>
                            <p><?php echo nl2br(htmlspecialchars($job['benefits'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Apply for Job -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="card-title h5 mb-0">Interested in this position?</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($isLoggedIn && $isJobSeeker): ?>
                            <a href="job-details.php?id=<?php echo $job_id; ?>" class="btn btn-primary">Apply Now</a>
                        <?php else: ?>
                            <p>You need to be logged in as a job seeker to apply for this position.</p>
                            <a href="login.php?redirect=job-details.php?id=<?php echo $job_id; ?>" class="btn btn-primary me-2">Log In</a>
                            <a href="register.php" class="btn btn-outline-primary">Create an Account</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Company Info -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Company Info</h3>
                    </div>
                    <div class="card-body text-center">
                        <h4 class="h6"><?php echo htmlspecialchars($job['company_name']); ?></h4>
                    </div>
                </div>
                
                <!-- Job Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Job Summary</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0">
                                <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                <strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?>
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-briefcase me-2 text-muted"></i>
                                <strong>Job Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?>
                            </li>
                            <li class="list-group-item px-0">
                                <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                <strong>Posted:</strong> <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                            </li>
                            <?php if (!empty($job['closing_date'])): ?>
                            <li class="list-group-item px-0">
                                <i class="fas fa-hourglass-end me-2 text-muted"></i>
                                <strong>Deadline:</strong> <?php echo date('M d, Y', strtotime($job['closing_date'])); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Share Job -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Share this Job</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/view-job-public.php?id=' . $job_id); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-linkedin"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/view-job-public.php?id=' . $job_id); ?>&text=<?php echo urlencode('Check out this job: ' . $job['title']); ?>" target="_blank" class="btn btn-outline-info">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/view-job-public.php?id=' . $job_id); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode('Job Opportunity: ' . $job['title']); ?>&body=<?php echo urlencode('Check out this job opportunity: https://' . $_SERVER['HTTP_HOST'] . '/view-job-public.php?id=' . $job_id); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
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