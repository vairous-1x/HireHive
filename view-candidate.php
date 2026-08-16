<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an enterprise
check_role("enterprise");

// Get enterprise data for worker type matching
$enterprise_data = get_user_data($conn, 'enterprises');
$enterprise_worker_types = !empty($enterprise_data['worker_types']) ? explode(',', $enterprise_data['worker_types']) : [];

$error_message = '';
$candidate = null;

// Check if candidate ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error_message = "Candidate ID is required.";
} else {
    $candidate_id = intval($_GET['id']);
    
    // Get candidate data
    $query = "SELECT j.*, u.email, u.signup_date
              FROM jobseekers j
              JOIN users u ON j.user_id = u.user_id
              WHERE j.jobseeker_id = ? AND u.is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Candidate not found.";
    } else {
        $candidate = $result->fetch_assoc();
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidate - HireHive</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .skill-badge {
            display: inline-block;
            background-color: #e9ecef;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            color: #212529;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }
        
        .bg-light-success {
            background-color: #d1e7dd;
            color: #146c43;
        }
        
        .detail-card {
            transition: all 0.2s ease;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 3rem;
            margin-bottom: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #0d6efd;
        }
    </style>
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
                        <a class="nav-link" href="dashboard-enterprise.php#applications">Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-enterprise.php#advertisements">Advertisements</a>
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
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Candidate Profile</h1>
                    <a href="search-candidates.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Search
                    </a>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php elseif ($candidate): ?>
                    <div class="row">
                        <!-- Left Column: Profile Summary -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <?php if (isset($candidate['profile_image']) && !empty($candidate['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($candidate['profile_image']); ?>" alt="Profile Image" class="profile-img">
                                    <?php else: ?>
                                        <div class="profile-img">
                                            <i class="fas fa-user fa-5x text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h2 class="h3 mb-0"><?php echo htmlspecialchars($candidate['full_name']); ?></h2>
                                    <p class="text-muted mb-2"><?php echo !empty($candidate['current_job_title']) ? htmlspecialchars($candidate['current_job_title']) : htmlspecialchars($candidate['profession']); ?></p>
                                    
                                    <?php if (!empty($candidate['location'])): ?>
                                        <p class="mb-2">
                                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                            <?php echo htmlspecialchars($candidate['location']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2 mt-4">
                                        <a href="mailto:<?php echo $candidate['email']; ?>" class="btn btn-primary">
                                            <i class="fas fa-envelope me-2"></i>Contact Candidate
                                        </a>
                                        <?php if (isset($candidate['resume_path']) && !empty($candidate['resume_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($candidate['resume_path']); ?>" class="btn btn-outline-primary" target="_blank">
                                                <i class="fas fa-file-pdf me-2"></i>View Resume
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h3 class="card-title h5 mb-0">Contact Information</h3>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-envelope text-primary me-2"></i>
                                            <a href="mailto:<?php echo $candidate['email']; ?>"><?php echo htmlspecialchars($candidate['email']); ?></a>
                                        </li>
                                        <?php if (!empty($candidate['phone'])): ?>
                                            <li class="mb-2">
                                                <i class="fas fa-phone text-primary me-2"></i>
                                                <?php echo htmlspecialchars($candidate['phone']); ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($candidate['linkedin'])): ?>
                                            <li class="mb-2">
                                                <i class="fab fa-linkedin text-primary me-2"></i>
                                                <a href="<?php echo htmlspecialchars($candidate['linkedin']); ?>" target="_blank">LinkedIn Profile</a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($candidate['website'])): ?>
                                            <li>
                                                <i class="fas fa-globe text-primary me-2"></i>
                                                <a href="<?php echo htmlspecialchars($candidate['website']); ?>" target="_blank">Personal Website</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Detailed Information -->
                        <div class="col-md-8">
                            <?php if (!empty($candidate['bio'])): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h3 class="card-title h5 mb-0">About</h3>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($candidate['bio'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Skills -->
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h3 class="card-title h5 mb-0">Skills</h3>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $skills = explode(',', $candidate['skills']);
                                    if (count($skills) > 0):
                                    ?>
                                        <div class="skills-container">
                                            <?php
                                            foreach ($skills as $skill):
                                                $skill = trim($skill);
                                                if (!empty($skill)):
                                            ?>
                                                <span class="skill-badge"><?php echo htmlspecialchars($skill); ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No skills listed.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Profession/Job Type -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h3 class="card-title h5 mb-0">Profession/Worker Type</h3>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $professions = explode(',', $candidate['profession']);
                                    if (count($professions) > 0 && !empty(trim($candidate['profession']))):
                                    ?>
                                        <div class="skills-container mb-3">
                                            <?php
                                            foreach ($professions as $profession):
                                                $profession = trim($profession);
                                                if (!empty($profession)):
                                                    $is_match = in_array($profession, $enterprise_worker_types);
                                            ?>
                                                <span class="skill-badge <?php echo $is_match ? 'bg-light-success' : ''; ?>">
                                                    <?php echo htmlspecialchars($profession); ?>
                                                    <?php if ($is_match): ?>
                                                        <i class="fas fa-check-circle ms-1" title="Matches your worker type needs"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                        
                                        <?php if (!empty($candidate['other_profession'])): ?>
                                            <p class="mb-0"><strong>Other:</strong> <?php echo htmlspecialchars($candidate['other_profession']); ?></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No profession listed.</p>
                                    <?php endif; ?>
                                    
                                    <?php if (count(array_intersect($enterprise_worker_types, $professions)) > 0): ?>
                                        <div class="alert alert-success mt-3 mb-0">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Good Match!</strong> This candidate's profession matches your company's worker type needs.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Education -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h3 class="card-title h5 mb-0">Education</h3>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($candidate['education'])): ?>
                                        <p class="mb-0"><?php echo htmlspecialchars($candidate['education']); ?></p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No education information provided.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Account Information -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h3 class="card-title h5 mb-0">Account Information</h3>
                                </div>
                                <div class="card-body">
                                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($candidate['signup_date'])); ?></p>
                                    <?php if (isset($candidate['last_active'])): ?>
                                        <p class="mb-0"><strong>Last Active:</strong> <?php echo date('F j, Y', strtotime($candidate['last_active'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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