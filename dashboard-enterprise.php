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

// Get user data
if ($_SESSION["role"] === "admin") {
    // Redirect admin to admin dashboard
    header("location: dashboard-admin.php");
    exit;
}

$user_data = get_user_data($conn, 'enterprises');

// Get subscription info
$subscription = null;
$subscription_sql = "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY end_date DESC LIMIT 1";
$stmt = $conn->prepare($subscription_sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $subscription = $result->fetch_assoc();
}
$stmt->close();

// Get job listings for this enterprise
$job_listings = array();
$jobs_sql = "SELECT jl.*, COUNT(ja.application_id) as application_count
             FROM joblistings jl
             LEFT JOIN job_applications ja ON jl.job_id = ja.job_id
             JOIN enterprises e ON jl.enterprise_id = e.enterprise_id
             WHERE e.user_id = ?
             GROUP BY jl.job_id
             ORDER BY jl.posted_date DESC";

$stmt = $conn->prepare($jobs_sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $job_listings[] = $row;
    }
}
$stmt->close();

// Get advertisements for this enterprise
$ads = array();
$ads_sql = "SELECT a.* FROM advertisements a
            JOIN enterprises e ON a.enterprise_id = e.enterprise_id
            WHERE e.user_id = ?
            ORDER BY a.created_date DESC";

$stmt = $conn->prepare($ads_sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ads[] = $row;
    }
}
$stmt->close();

// Get recent applications for this enterprise's jobs
$applications = array();
$app_sql = "SELECT ja.*, jl.title, j.full_name, u.email
            FROM job_applications ja
            JOIN joblistings jl ON ja.job_id = jl.job_id
            JOIN jobseekers j ON ja.jobseeker_id = j.jobseeker_id
            JOIN users u ON j.user_id = u.user_id
            JOIN enterprises e ON jl.enterprise_id = e.enterprise_id
            WHERE e.user_id = ?
            ORDER BY ja.application_date DESC
            LIMIT 10";

$stmt = $conn->prepare($app_sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
}
$stmt->close();

// Simplified: Just have an empty array for matching candidates
$matching_candidates = array();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Dashboard - HireHive</title>
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
                        <a class="nav-link active" href="dashboard-enterprise.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#job-listings">Job Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#applications">Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#advertisements">Advertisements</a>
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

    <!-- Dashboard Main Content -->
    <div class="container py-5">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>Welcome, <?php echo htmlspecialchars($user_data['company_name']); ?>!</h1>
                <p class="lead">Manage your recruitment and advertising from one place.</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($subscription): ?>
                    <div class="subscription-info">
                        <span class="badge bg-success">Active Plan: <?php echo htmlspecialchars($subscription['plan_name']); ?></span>
                        <p class="small">Valid until: <?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></p>
                    </div>
                <?php else: ?>
                    <div class="subscription-info">
                        <span class="badge bg-warning">No Active Plan</span>
                        <p class="small">Upgrade to access premium features!</p>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#upgradeModal">Upgrade Now</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Active Jobs</h5>
                        <p class="card-text stat-number"><?php echo count(array_filter($job_listings, function($job) { return $job['status'] === 'open'; })); ?></p>
                        <p class="text-muted">Currently accepting applications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Applications</h5>
                        <p class="card-text stat-number"><?php echo count($applications); ?></p>
                        <p class="text-muted">Candidates applied to your jobs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Active Ads</h5>
                        <p class="card-text stat-number"><?php echo count(array_filter($ads, function($ad) { return $ad['status'] === 'active'; })); ?></p>
                        <p class="text-muted">Currently displayed on platform</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="mb-0">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <a href="post-job.php" class="btn btn-primary btn-lg action-button">
                                    <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                    <span>Post New Job</span>
                                </a>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <a href="create-ad.php" class="btn btn-success btn-lg action-button">
                                    <i class="fas fa-ad fa-2x mb-2"></i>
                                    <span>Create Advertisement</span>
                                </a>
                            </div>
                            <div class="col-md-4 text-center mb-3">
                                <a href="search-candidates.php" class="btn btn-info btn-lg action-button">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <span>Search Candidates</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Listings Section -->
        <section id="job-listings" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Your Job Listings</h2>
                <a href="post-job.php" class="btn btn-outline-primary">Post New Job</a>
            </div>
            
            <?php if (count($job_listings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Applications</th>
                                <th>Posted Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($job_listings as $job): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job['title']); ?></td>
                                    <td><?php echo htmlspecialchars($job['location']); ?></td>
                                    <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                                    <td><?php echo $job['application_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($job['posted_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $job['status'] === 'open' ? 'bg-success' : ($job['status'] === 'draft' ? 'bg-secondary' : 'bg-danger'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($job['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view-job.php?id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="edit-job.php?id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="if(confirm('Are you sure?')) window.location.href='delete-job.php?id=<?php echo $job['job_id']; ?>'">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">You haven't posted any jobs yet. <a href="post-job.php">Post your first job</a>!</div>
            <?php endif; ?>
        </section>

        <!-- Applications Section -->
        <section id="applications" class="mb-5">
            <h2 class="mb-3">Recent Applications</h2>
            
            <?php if (count($applications) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Applicant</th>
                                <th>Date Applied</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['title']); ?></td>
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
                                        <a href="review-application.php?id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-outline-primary">Review</a>
                                        <a href="mailto:<?php echo $app['email']; ?>" class="btn btn-sm btn-outline-success">Contact</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <a href="all-applications.php" class="btn btn-outline-primary">View All Applications</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">You don't have any applications yet.</div>
            <?php endif; ?>
        </section>

        <!-- Recommended Candidates Section -->
        <section id="candidates" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Recommended Candidates</h2>
                <a href="search-candidates.php" class="btn btn-outline-primary">Search All Candidates</a>
            </div>
            
            <?php if (count($matching_candidates) > 0): ?>
                <div class="row">
                    <?php foreach ($matching_candidates as $candidate): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-3">
                                            <?php if (isset($candidate['profile_image']) && !empty($candidate['profile_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($candidate['profile_image']); ?>" alt="Profile Image" class="rounded-circle" width="64" height="64">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                                    <i class="fas fa-user fa-2x text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($candidate['full_name']); ?></h5>
                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($candidate['profession']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h6 class="small fw-bold">Skills:</h6>
                                        <div>
                                            <?php
                                            $skills = explode(',', $candidate['skills']);
                                            $count = 0;
                                            foreach ($skills as $skill):
                                                $skill = trim($skill);
                                                if (!empty($skill) && $count < 5):
                                                    $count++;
                                            ?>
                                                <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($skill); ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            if (count($skills) > 5) echo '<span class="badge bg-light text-dark">+' . (count($skills) - 5) . ' more</span>';
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="view-candidate.php?id=<?php echo $candidate['jobseeker_id']; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                        <a href="mailto:<?php echo $candidate['email']; ?>" class="btn btn-sm btn-outline-success">Contact</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="search-candidates.php?match_worker_types=1" class="btn btn-primary">Find More Matching Candidates</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <p class="mb-1"><strong>No matching candidates found.</strong></p>
                            <p class="mb-0">
                                <?php if (empty($user_data['worker_types'])): ?>
                                    You haven't specified any worker types in your profile. 
                                    <a href="edit-company-profile.php">Update your profile</a> to see candidate recommendations.
                                <?php else: ?>
                                    Try <a href="search-candidates.php">searching for candidates</a> with different criteria.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Advertisements Section -->
        <section id="advertisements" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Your Advertisements</h2>
                <a href="create-ad.php" class="btn btn-outline-primary">Create New Ad</a>
            </div>
            
            <?php if (count($ads) > 0): ?>
                <div class="row">
                    <?php foreach ($ads as $ad): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card ad-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($ad['title']); ?></h5>
                                    <span class="badge <?php echo $ad['status'] === 'active' ? 'bg-success' : ($ad['status'] === 'pending' ? 'bg-warning' : 'bg-secondary'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($ad['status'])); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if ($ad['logo_path']): ?>
                                        <div class="text-center mb-3">
                                            <img src="<?php echo htmlspecialchars($ad['logo_path']); ?>" alt="Company Logo" class="img-fluid ad-logo">
                                        </div>
                                    <?php endif; ?>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($ad['description'], 0, 150)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Created <?php echo date('M d, Y', strtotime($ad['created_date'])); ?></span>
                                        <div>
                                            <a href="edit-ad.php?id=<?php echo $ad['ad_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="if(confirm('Are you sure?')) window.location.href='delete-ad.php?id=<?php echo $ad['ad_id']; ?>'">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">You haven't created any advertisements yet. <a href="create-ad.php">Create your first ad</a>!</div>
            <?php endif; ?>
        </section>

        <!-- Company Profile Section -->
        <section id="profile" class="mb-5">
            <div class="card">
                <div class="card-header bg-light">
                    <h2 class="mb-0">Company Profile</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="profile-image">
                                <i class="fas fa-building fa-6x text-primary"></i>
                            </div>
                            <h3 class="mt-3"><?php echo htmlspecialchars($user_data['company_name']); ?></h3>
                            <p class="text-muted"><?php echo htmlspecialchars($user_data['company_size']); ?> employees</p>
                            <a href="edit-company-profile.php" class="btn btn-outline-primary">Edit Profile</a>
                        </div>
                        <div class="col-md-8">
                            <h4>Company Information</h4>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label fw-bold">Email:</label>
                                <div class="col-sm-9">
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label fw-bold">Phone:</label>
                                <div class="col-sm-9">
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['phone']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($user_data['linkedin']): ?>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">LinkedIn:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">
                                            <a href="<?php echo htmlspecialchars($user_data['linkedin']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($user_data['linkedin']); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user_data['website']): ?>
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label fw-bold">Website:</label>
                                    <div class="col-sm-9">
                                        <p class="form-control-plaintext">
                                            <a href="<?php echo htmlspecialchars($user_data['website']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($user_data['website']); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <h4 class="mt-4">Product/Service Description</h4>
                            <p><?php echo nl2br(htmlspecialchars($user_data['product_service_description'])); ?></p>
                            
                            <h4 class="mt-4">Worker Types Needed</h4>
                            <div class="skills-container mb-3">
                                <?php
                                $worker_types = explode(',', $user_data['worker_types']);
                                foreach ($worker_types as $type):
                                ?>
                                    <span class="badge bg-primary me-2 mb-2"><?php echo htmlspecialchars(trim($type)); ?></span>
                                <?php endforeach; ?>
                                
                                <?php if ($user_data['other_worker_type']): ?>
                                    <span class="badge bg-secondary me-2 mb-2"><?php echo htmlspecialchars($user_data['other_worker_type']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Upgrade Modal -->
    <div class="modal fade" id="upgradeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #1a73e8 !important;">
                    <h5 class="modal-title w-100 text-center">
                        <i class="fas fa-crown me-2"></i>Upgrade Your Enterprise Plan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process-payment.php" method="POST" id="payment-form">
                    <div class="modal-body py-3">
                        <!-- Plan Selection Section -->
                        <div id="plan-selection-section">
                            <div class="row gx-4 align-items-stretch">
                                <!-- Starter Plan -->
                                <div class="col-md-4 px-md-2 d-flex">
                                    <div class="card h-100 pricing-card w-100 plan-card" data-plan="starter">
                                        <div class="card-header bg-light text-center py-2">
                                            <h5 class="my-0 fw-normal">Starter</h5>
                                        </div>
                                        <div class="card-body d-flex flex-column py-3">
                                            <h2 class="card-title pricing-card-title text-center mb-2">$29.99 <small class="text-muted">/ month</small></h2>
                                            <ul class="list-unstyled mb-3 flex-grow-1">
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Post 5 jobs</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Basic analytics</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Email support</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>1 advertisement</li>
                                            </ul>
                                            <button type="button" class="w-100 btn btn-orange select-plan" data-plan="starter">Choose Plan</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Business Plan -->
                                <div class="col-md-4 px-md-2 d-flex">
                                    <div class="card h-100 pricing-card w-100 plan-card" data-plan="business">
                                        <div class="card-header bg-light text-center py-2">
                                            <h5 class="my-0 fw-normal">Business</h5>
                                        </div>
                                        <div class="card-body d-flex flex-column py-3">
                                            <h2 class="card-title pricing-card-title text-center mb-2">$59.99 <small class="text-muted">/ month</small></h2>
                                            <ul class="list-unstyled mb-3 flex-grow-1">
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Post 20 jobs</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Advanced analytics</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Premium support</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>3 advertisements</li>
                                            </ul>
                                            <button type="button" class="w-100 btn btn-orange select-plan" data-plan="business">Choose Plan</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Enterprise Plan -->
                                <div class="col-md-4 px-md-2 d-flex">
                                    <div class="card h-100 pricing-card plan-card w-100 shadow-lg" data-plan="enterprise">
                                        <div class="card-header text-white text-center position-relative py-2" style="background-color: #1a73e8 !important;">
                                            <span class="position-absolute top-0 start-50 translate-middle badge bg-warning text-dark">
                                                Popular Choice
                                            </span>
                                            <h5 class="my-0 fw-normal">Enterprise</h5>
                                        </div>
                                        <div class="card-body d-flex flex-column py-3">
                                            <h2 class="card-title pricing-card-title text-center mb-2">$99.99 <small class="text-muted">/ month</small></h2>
                                            <ul class="list-unstyled mb-3 flex-grow-1">
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Unlimited jobs</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Custom features</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Dedicated support</li>
                                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Unlimited advertisements</li>
                                            </ul>
                                            <button type="button" class="w-100 btn btn-orange select-plan" data-plan="enterprise">Choose Plan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Section (initially hidden) -->
                        <div id="payment-section" style="display: none;">
                            <h4 class="mb-4 border-bottom pb-3"><i class="fas fa-credit-card me-2"></i>Payment Details</h4>
                            
                            <input type="hidden" name="plan_id" id="selected-plan" value="">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Payment Method</label>
                                <div class="d-flex gap-4 payment-methods">
                                    <div class="form-check form-check-inline payment-method-option border rounded p-3 flex-grow-1">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-visa" value="visa" checked>
                                        <label class="form-check-label w-100" for="payment-visa">
                                            <i class="fab fa-cc-visa fa-2x me-2 text-primary"></i>
                                            <i class="fab fa-cc-mastercard fa-2x me-2 text-danger"></i>
                                            <span class="ms-2 fw-bold">Credit / Debit Card</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline payment-method-option border rounded p-3 flex-grow-1">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-paypal" value="paypal">
                                        <label class="form-check-label w-100" for="payment-paypal">
                                            <i class="fab fa-paypal fa-2x text-primary"></i>
                                            <span class="ms-2 fw-bold">PayPal</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="card-details" class="bg-light p-4 rounded mb-4">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="card-name" class="form-label">Name on Card</label>
                                        <input type="text" class="form-control" id="card-name" placeholder="John Smith">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="card-number" class="form-label">Card Number</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="card-number" placeholder="1234 5678 9012 3456">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="expiry-date" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="expiry-date" placeholder="MM/YY">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="cvv" placeholder="123">
                                            <span class="input-group-text" title="3-digit code on the back of your card"><i class="fas fa-question-circle"></i></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="zip-code" class="form-label">Zip Code</label>
                                        <input type="text" class="form-control" id="zip-code" placeholder="12345">
                                    </div>
                                </div>
                            </div>
                            
                            <div id="paypal-info" class="bg-light p-4 rounded mb-4" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle text-primary me-3 fa-2x"></i>
                                    <p class="mb-0">
                                        You will be redirected to PayPal to complete your payment after clicking the "Complete Payment" button.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="alert alert-secondary">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-secondary me-3 fa-2x"></i>
                                    <div>
                                        <p class="mb-0 fw-bold">Demo Application</p>
                                        <p class="mb-0 small">This is a demo application. No actual payment will be processed.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="order-summary mt-4 p-3 bg-light rounded">
                                <h5 class="mb-3">Order Summary</h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Plan:</span>
                                    <span id="summary-plan-name">--</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Billing:</span>
                                    <span id="summary-billing">--</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total:</span>
                                    <span id="summary-total">--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-outline-secondary px-5" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="back-to-plans" class="btn btn-outline-primary" style="display: none;">
                            <i class="fas fa-arrow-left me-1"></i> Back to Plans
                        </button>
                        <button type="submit" id="complete-payment" class="btn btn-primary" style="display: none;">
                            <i class="fas fa-lock me-1"></i> Complete Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for payment form */
        .pricing-card {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
            margin: 0 10px;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .plan-card.border-primary {
            border-width: 2px !important;
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        /* Position badge correctly for Enterprise plan */
        .pricing-card[data-plan="enterprise"] .badge {
            top: -10px;
        }
        
        /* Custom button color */
        .btn-orange {
            background-color: #e74c3c;
            border-color: #e74c3c;
            color: white;
        }
        
        .btn-orange:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            color: white;
        }
        
        .payment-method-option {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .payment-method-option:hover {
            background-color: #f8f9fa;
        }
        
        input[type="radio"]:checked + label {
            font-weight: bold;
        }
        
        .form-check-input:checked ~ .payment-method-option {
            border-color: #0d6efd !important;
            background-color: #f0f7ff;
        }
        
        #upgradeModal .modal-header {
            border-bottom: 0;
        }
        
        #upgradeModal .modal-footer {
            border-top: 0;
        }
        
        .order-summary {
            border: 1px solid #dee2e6;
        }
        
        /* Adjust spacing for plan cards */
        @media (min-width: 768px) {
            #plan-selection-section .col-md-4 {
                padding: 0 15px;
            }
            
            #plan-selection-section .row {
                margin: 0 -15px;
            }
        }
    </style>

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
    
    <!-- Payment Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get plan selection buttons
            const planButtons = document.querySelectorAll('.select-plan');
            const backToPlansButton = document.getElementById('back-to-plans');
            const completePaymentButton = document.getElementById('complete-payment');
            const paymentSection = document.getElementById('payment-section');
            const planSelectionSection = document.getElementById('plan-selection-section');
            const selectedPlanInput = document.getElementById('selected-plan');
            const planCards = document.querySelectorAll('.plan-card');
            
            // Order summary elements
            const summaryPlanName = document.getElementById('summary-plan-name');
            const summaryBilling = document.getElementById('summary-billing');
            const summaryTotal = document.getElementById('summary-total');
            
            // Payment method toggle
            const paymentVisa = document.getElementById('payment-visa');
            const paymentPaypal = document.getElementById('payment-paypal');
            const cardDetails = document.getElementById('card-details');
            const paypalInfo = document.getElementById('paypal-info');
            
            // Plan details object
            const planDetails = {
                'starter': {
                    name: 'Starter Plan',
                    price: '$29.99',
                    billing: 'Monthly'
                },
                'business': {
                    name: 'Business Plan',
                    price: '$59.99',
                    billing: 'Monthly'
                },
                'enterprise': {
                    name: 'Enterprise Plan',
                    price: '$99.99',
                    billing: 'Monthly'
                }
            };
            
            // Handle plan selection
            planButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const planId = this.getAttribute('data-plan');
                    selectedPlanInput.value = planId;
                    
                    // Update order summary
                    if (planDetails[planId]) {
                        summaryPlanName.textContent = planDetails[planId].name;
                        summaryBilling.textContent = planDetails[planId].billing;
                        summaryTotal.textContent = planDetails[planId].price;
                    }
                    
                    // Highlight selected plan
                    planCards.forEach(card => {
                        if (card.getAttribute('data-plan') === planId) {
                            card.classList.add('border-primary');
                        } else {
                            card.classList.remove('border-primary');
                        }
                    });
                    
                    // Show payment section and update buttons
                    planSelectionSection.style.display = 'none';
                    paymentSection.style.display = 'block';
                    backToPlansButton.style.display = 'inline-block';
                    completePaymentButton.style.display = 'inline-block';
                });
            });
            
            // Handle back to plans button
            backToPlansButton.addEventListener('click', function() {
                paymentSection.style.display = 'none';
                planSelectionSection.style.display = 'block';
                backToPlansButton.style.display = 'none';
                completePaymentButton.style.display = 'none';
                
                // Remove highlighting
                planCards.forEach(card => {
                    card.classList.remove('border-primary');
                });
            });
            
            // Handle payment method toggle
            paymentVisa.addEventListener('change', togglePaymentMethod);
            paymentPaypal.addEventListener('change', togglePaymentMethod);
            
            function togglePaymentMethod() {
                if (paymentVisa.checked) {
                    cardDetails.style.display = 'block';
                    paypalInfo.style.display = 'none';
                } else {
                    cardDetails.style.display = 'none';
                    paypalInfo.style.display = 'block';
                }
            }
            
            // Add interactive effects to payment method options
            const paymentOptions = document.querySelectorAll('.payment-method-option');
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        
                        // Trigger change event to toggle visibility
                        const event = new Event('change');
                        radio.dispatchEvent(event);
                    }
                });
            });
            
            // Form validation
            const cardNumberInput = document.getElementById('card-number');
            const expiryDateInput = document.getElementById('expiry-date');
            const cvvInput = document.getElementById('cvv');
            
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function() {
                    // Format card number with spaces after every 4 digits
                    let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = '';
                    
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }
                    
                    this.value = formattedValue.substring(0, 19); // Limit to 16 digits + 3 spaces
                });
            }
            
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function() {
                    // Format expiry date as MM/YY
                    let value = this.value.replace(/\D/g, '');
                    
                    if (value.length > 2) {
                        this.value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    } else {
                        this.value = value;
                    }
                });
            }
            
            // Form submission
            document.getElementById('payment-form').addEventListener('submit', function(e) {
                // For demo purposes, we're just allowing submission
                // In a real application, you would validate card details here
                return true;
            });
        });
    </script>
</body>
</html> 