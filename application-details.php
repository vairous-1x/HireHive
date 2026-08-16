<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is a job seeker
check_role("job-seeker");

// Check if application ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard-job-seeker.php#applications");
    exit;
}

$application_id = $_GET['id'];
$user_id = $_SESSION["user_id"];

// Get application details
$application = null;
$application_sql = "SELECT ja.*, jl.title as job_title, jl.job_id, jl.description, jl.location,
                           jl.job_type, jl.salary_range, e.company_name, js.jobseeker_id
                    FROM job_applications ja
                    JOIN joblistings jl ON ja.job_id = jl.job_id
                    JOIN enterprises e ON jl.enterprise_id = e.enterprise_id
                    JOIN jobseekers js ON ja.jobseeker_id = js.jobseeker_id
                    WHERE ja.application_id = ? AND js.user_id = ?";

$stmt = $conn->prepare($application_sql);
$stmt->bind_param("ii", $application_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard-job-seeker.php?error=notfound");
    exit;
}

$application = $result->fetch_assoc();
$stmt->close();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - HireHive</title>
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
                        <a class="nav-link" href="dashboard-job-seeker.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-job-seeker.php#job-listings">Job Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-job-seeker.php#applications">My Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-job-seeker.php#profile">My Profile</a>
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
                    <h1>Application Details</h1>
                    <a href="dashboard-job-seeker.php#applications" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Applications
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Application Info -->
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
                            <h3 class="h6 fw-bold">Your Cover Letter</h3>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($application['feedback'])): ?>
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Employer Feedback</h3>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($application['feedback'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <span>
                                <?php if ($application['status'] === 'pending'): ?>
                                    Your application is currently being reviewed by the employer.
                                <?php elseif ($application['status'] === 'reviewed'): ?>
                                    Your application has been reviewed by the employer. They may contact you soon.
                                <?php elseif ($application['status'] === 'interviewed'): ?>
                                    You have been interviewed for this position. The employer will update your status soon.
                                <?php elseif ($application['status'] === 'offered'): ?>
                                    Congratulations! You have been offered this position. The employer will contact you with more details.
                                <?php elseif ($application['status'] === 'rejected'): ?>
                                    Unfortunately, the employer has decided not to proceed with your application. Don't give up, keep looking for other opportunities!
                                <?php elseif ($application['status'] === 'accepted'): ?>
                                    Congratulations! You have accepted this job offer.
                                <?php else: ?>
                                    Your application is being processed.
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Job Details -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="card-title h5 mb-0">Job Details</h2>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h3 class="h6 fw-bold">Company</h3>
                                <p><?php echo htmlspecialchars($application['company_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h3 class="h6 fw-bold">Location</h3>
                                <p><?php echo htmlspecialchars($application['location']); ?></p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h3 class="h6 fw-bold">Job Type</h3>
                                <p><?php echo htmlspecialchars($application['job_type']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($application['salary_range'])): ?>
                                <h3 class="h6 fw-bold">Salary Range</h3>
                                <p><?php echo htmlspecialchars($application['salary_range']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h3 class="h6 fw-bold">Job Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($application['description'])); ?></p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="view-job-public.php?id=<?php echo $application['job_id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i> View Complete Job Posting
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Application Status -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Application Timeline</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush application-timeline">
                            <li class="list-group-item px-0 d-flex">
                                <div class="timeline-bullet bg-success me-3 mt-1"></div>
                                <div>
                                    <h5 class="timeline-title h6 mb-0">Application Submitted</h5>
                                    <p class="text-muted small mb-0"><?php echo date('M d, Y', strtotime($application['application_date'])); ?></p>
                                </div>
                            </li>

                            <?php if (in_array($application['status'], ['reviewed', 'interviewed', 'offered', 'rejected', 'accepted'])): ?>
                            <li class="list-group-item px-0 d-flex">
                                <div class="timeline-bullet bg-info me-3 mt-1"></div>
                                <div>
                                    <h5 class="timeline-title h6 mb-0">Application Reviewed</h5>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (in_array($application['status'], ['interviewed', 'offered', 'rejected', 'accepted'])): ?>
                            <li class="list-group-item px-0 d-flex">
                                <div class="timeline-bullet bg-primary me-3 mt-1"></div>
                                <div>
                                    <h5 class="timeline-title h6 mb-0">Interview Completed</h5>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (in_array($application['status'], ['offered', 'accepted'])): ?>
                            <li class="list-group-item px-0 d-flex">
                                <div class="timeline-bullet bg-success me-3 mt-1"></div>
                                <div>
                                    <h5 class="timeline-title h6 mb-0">Offer Extended</h5>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($application['status'] === 'rejected'): ?>
                            <li class="list-group-item px-0 d-flex">
                                <div class="timeline-bullet bg-danger me-3 mt-1"></div>
                                <div>
                                    <h5 class="timeline-title h6 mb-0">Application Rejected</h5>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($application['status'] === 'accepted'): ?>
                            <li class="list-group-item px-0 d-flex">
                                <div class="timeline-bullet bg-success me-3 mt-1"></div>
                                <div>
                                    <h5 class="timeline-title h6 mb-0">Offer Accepted</h5>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Actions</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($application['status'] === 'offered'): ?>
                            <p class="text-success mb-3">
                                <i class="fas fa-trophy me-2"></i>
                                Congratulations! You've received a job offer!
                            </p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-success mb-2" type="button" onclick="alert('This feature is not implemented in the demo')">
                                    <i class="fas fa-check-circle me-2"></i> Accept Offer
                                </button>
                                <button class="btn btn-outline-danger" type="button" onclick="alert('This feature is not implemented in the demo')">
                                    <i class="fas fa-times-circle me-2"></i> Decline Offer
                                </button>
                            </div>
                        <?php elseif ($application['status'] === 'rejected'): ?>
                            <p class="mb-3">
                                <i class="fas fa-search me-2"></i>
                                Don't worry, there are other opportunities out there!
                            </p>
                            <div class="d-grid gap-2">
                                <a href="dashboard-job-seeker.php#job-listings" class="btn btn-primary">
                                    <i class="fas fa-briefcase me-2"></i> Find More Jobs
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="d-grid gap-2">
                                <a href="view-job-public.php?id=<?php echo $application['job_id']; ?>" class="btn btn-outline-primary mb-2">
                                    <i class="fas fa-eye me-2"></i> View Job
                                </a>
                                <?php if ($application['status'] === 'pending'): ?>
                                <button class="btn btn-outline-danger" type="button" onclick="alert('This feature is not implemented in the demo')">
                                    <i class="fas fa-trash me-2"></i> Withdraw Application
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Similar Jobs -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Find Similar Jobs</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Looking for more opportunities like this one?</p>
                        <div class="d-grid">
                            <a href="dashboard-job-seeker.php#job-listings" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i> Browse Jobs
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

    <style>
        /* Timeline styles */
        .application-timeline .timeline-bullet {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-top: 5px;
        }
        
        .application-timeline .list-group-item {
            border-left: 0;
            border-right: 0;
            padding-left: 0;
            padding-right: 0;
        }
        
        .application-timeline .list-group-item:first-child {
            border-top: 0;
        }
        
        .application-timeline .list-group-item:last-child {
            border-bottom: 0;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 