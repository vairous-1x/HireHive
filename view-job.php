<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an enterprise
check_role("enterprise");

// Get job ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard-enterprise.php#job-listings");
    exit;
}

$job_id = $_GET['id'];
$user_id = $_SESSION["user_id"];

// Get job details
$job = null;
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

// Get applicants for this job
$applicants = array();
$app_sql = "SELECT ja.*, j.full_name, u.email, j.resume_path
            FROM job_applications ja
            JOIN jobseekers j ON ja.jobseeker_id = j.jobseeker_id
            JOIN users u ON j.user_id = u.user_id
            WHERE ja.job_id = ?
            ORDER BY ja.application_date DESC";

$stmt = $conn->prepare($app_sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $applicants[] = $row;
    }
}
$stmt->close();

// Get count of applications per status
$status_counts = array(
    'pending' => 0,
    'reviewed' => 0,
    'interviewed' => 0,
    'offered' => 0,
    'rejected' => 0,
    'accepted' => 0
);

foreach ($applicants as $app) {
    if (isset($status_counts[$app['status']])) {
        $status_counts[$app['status']]++;
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
    <title>View Job - HireHive</title>
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div>
                        <a href="dashboard-enterprise.php#job-listings" class="btn btn-outline-primary me-2">
                            <i class="fas fa-arrow-left"></i> Back to Jobs
                        </a>
                        <div class="btn-group">
                            <a href="edit-job.php?id=<?php echo $job_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="if(confirm('Are you sure you want to delete this job?')) window.location.href='delete-job.php?id=<?php echo $job_id; ?>'">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="mt-2">
                    <span class="badge <?php echo $job['status'] === 'open' ? 'bg-success' : ($job['status'] === 'draft' ? 'bg-secondary' : 'bg-danger'); ?>">
                        <?php echo ucfirst(htmlspecialchars($job['status'])); ?>
                    </span>
                    <span class="ms-3 text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                    </span>
                    <span class="ms-3 text-muted">
                        <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($job['job_type']); ?>
                    </span>
                    <span class="ms-3 text-muted">
                        <i class="fas fa-calendar-alt"></i> Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                    </span>
                    <?php if ($job['application_deadline']): ?>
                    <span class="ms-3 text-muted">
                        <i class="fas fa-clock"></i> Deadline: <?php echo date('M d, Y', strtotime($job['application_deadline'])); ?>
                    </span>
                    <?php endif; ?>
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
                        <?php if ($job['salary_min'] > 0 || $job['salary_max'] > 0): ?>
                            <div class="mb-4">
                                <h3 class="h6 fw-bold">Salary Range</h3>
                                <p>
                                    <?php
                                    if ($job['salary_min'] > 0 && $job['salary_max'] > 0) {
                                        echo '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']) . ' per year';
                                    } elseif ($job['salary_min'] > 0) {
                                        echo 'From $' . number_format($job['salary_min']) . ' per year';
                                    } elseif ($job['salary_max'] > 0) {
                                        echo 'Up to $' . number_format($job['salary_max']) . ' per year';
                                    }
                                    ?>
                                </p>
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
                        
                        <div class="mb-4">
                            <h3 class="h6 fw-bold">Responsibilities</h3>
                            <p><?php echo nl2br(htmlspecialchars($job['responsibilities'])); ?></p>
                        </div>
                        
                        <?php if (!empty($job['benefits'])): ?>
                            <div>
                                <h3 class="h6 fw-bold">Benefits</h3>
                                <p><?php echo nl2br(htmlspecialchars($job['benefits'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Applicants List -->
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h2 class="card-title h5 mb-0">Applicants (<?php echo count($applicants); ?>)</h2>
                        <?php if (count($applicants) > 0): ?>
                            <a href="all-applications.php?job_id=<?php echo $job_id; ?>" class="btn btn-sm btn-outline-primary">View All</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($applicants) > 0): ?>
                            <!-- Status summary -->
                            <div class="mb-4">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($status_counts as $status => $count): ?>
                                        <?php if ($count > 0): ?>
                                            <div class="status-pill">
                                                <span class="badge bg-<?php
                                                    switch($status) {
                                                        case 'pending': echo 'warning'; break;
                                                        case 'reviewed': echo 'info'; break;
                                                        case 'interviewed': echo 'primary'; break;
                                                        case 'offered': echo 'success'; break;
                                                        case 'rejected': echo 'danger'; break;
                                                        case 'accepted': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?> rounded-pill">
                                                    <?php echo ucfirst($status); ?>: <?php echo $count; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Date Applied</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($applicants, 0, 5) as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                                                <td>
                                                    <span class="badge <?php
                                                        switch($app['status']) {
                                                            case 'pending': echo 'bg-warning'; break;
                                                            case 'reviewed': echo 'bg-info'; break;
                                                            case 'interviewed': echo 'bg-primary'; break;
                                                            case 'offered': echo 'bg-success'; break;
                                                            case 'rejected': echo 'bg-danger'; break;
                                                            case 'accepted': echo 'bg-success'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="review-application.php?id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-outline-primary">Review</a>
                                                        <a href="mailto:<?php echo $app['email']; ?>" class="btn btn-sm btn-outline-success">Contact</a>
                                                        <?php if ($app['resume_path']): ?>
                                                            <a href="<?php echo htmlspecialchars($app['resume_path']); ?>" class="btn btn-sm btn-outline-info" target="_blank">Resume</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (count($applicants) > 5): ?>
                                <div class="text-center mt-3">
                                    <a href="all-applications.php?job_id=<?php echo $job_id; ?>" class="btn btn-outline-primary">View All Applicants</a>
                                </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No applications yet for this job listing.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Action Card -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Job Actions</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($job['status'] === 'draft'): ?>
                            <p class="mb-3">This job is currently saved as a draft and is not visible to job seekers.</p>
                            <a href="publish-job.php?id=<?php echo $job_id; ?>" class="btn btn-success w-100 mb-3">
                                <i class="fas fa-check-circle me-2"></i> Publish Job
                            </a>
                        <?php elseif ($job['status'] === 'open'): ?>
                            <p class="mb-3">This job is currently published and visible to job seekers.</p>
                            <a href="close-job.php?id=<?php echo $job_id; ?>" class="btn btn-warning w-100 mb-3">
                                <i class="fas fa-pause-circle me-2"></i> Close Job
                            </a>
                        <?php else: ?>
                            <p class="mb-3">This job is currently closed and not visible to job seekers.</p>
                            <a href="reopen-job.php?id=<?php echo $job_id; ?>" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-redo me-2"></i> Reopen Job
                            </a>
                        <?php endif; ?>
                        
                        <a href="edit-job.php?id=<?php echo $job_id; ?>" class="btn btn-outline-primary w-100 mb-3">
                            <i class="fas fa-edit me-2"></i> Edit Job
                        </a>
                        
                        <a href="duplicate-job.php?id=<?php echo $job_id; ?>" class="btn btn-outline-secondary w-100 mb-3">
                            <i class="fas fa-copy me-2"></i> Duplicate Job
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger w-100" 
                                onclick="if(confirm('Are you sure you want to delete this job?')) window.location.href='delete-job.php?id=<?php echo $job_id; ?>'">
                            <i class="fas fa-trash me-2"></i> Delete Job
                        </button>
                    </div>
                </div>
                
                <!-- Share Card -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Share Job</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Share this job on social media or via email:</p>
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/job/' . $job_id); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-linkedin fa-lg"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/job/' . $job_id); ?>&text=<?php echo urlencode('Check out this job: ' . $job['title']); ?>" target="_blank" class="btn btn-outline-info">
                                <i class="fab fa-twitter fa-lg"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/job/' . $job_id); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-facebook fa-lg"></i>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode('Job Opportunity: ' . $job['title']); ?>&body=<?php echo urlencode('Check out this job opportunity: https://' . $_SERVER['HTTP_HOST'] . '/job/' . $job_id); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope fa-lg"></i>
                            </a>
                        </div>
                        
                        <div class="input-group">
                            <input type="text" class="form-control" id="job-url" value="https://<?php echo $_SERVER['HTTP_HOST']; ?>/job/<?php echo $job_id; ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyJobUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Card -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="card-title h5 mb-0">Job Stats</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Views:</span>
                                <span class="fw-bold"><?php echo isset($job['views']) ? $job['views'] : '0'; ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Applications:</span>
                                <span class="fw-bold"><?php echo count($applicants); ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Conversion Rate:</span>
                                <span class="fw-bold">
                                    <?php
                                    $views = isset($job['views']) ? $job['views'] : 0;
                                    $apps = count($applicants);
                                    
                                    if ($views > 0) {
                                        echo round(($apps / $views) * 100, 1) . '%';
                                    } else {
                                        echo '0%';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between">
                                <span>Days Active:</span>
                                <span class="fw-bold">
                                    <?php
                                    $posted_date = new DateTime($job['posted_date']);
                                    $today = new DateTime();
                                    $interval = $posted_date->diff($today);
                                    echo $interval->days;
                                    ?>
                                </span>
                            </div>
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
    
    <script>
    function copyJobUrl() {
        var copyText = document.getElementById("job-url");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        
        // Optional: Show a tooltip or message that URL was copied
        alert("Job URL copied to clipboard!");
    }
    </script>
</body>
</html> 