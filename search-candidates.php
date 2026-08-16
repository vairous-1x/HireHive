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

// Get enterprise data
$user_data = get_user_data($conn, 'enterprises');
$error_message = '';
$results = array();
$search_performed = false;

// Get enterprise worker types for matching
$enterprise_worker_types = !empty($user_data['worker_types']) ? explode(',', $user_data['worker_types']) : [];

// Define skills for select options
$skill_options = [
    'Web Development', 'Mobile Development', 'UI/UX Design', 'Graphic Design',
    'Digital Marketing', 'SEO', 'Content Writing', 'Data Analysis',
    'Machine Learning', 'Project Management', 'Customer Service', 'Sales',
    'Business Development', 'Social Media Marketing', 'Accounting', 'Finance',
    'Human Resources', 'Administrative', 'IT Support', 'Network Administration',
    'Software Development', 'Quality Assurance', 'DevOps', 'Product Management'
];

// Define experience levels
$experience_levels = [
    'Entry Level' => '0-2 years',
    'Mid Level' => '3-5 years',
    'Senior Level' => '6-10 years',
    'Expert' => '10+ years'
];

// Handle search
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['match_worker_types'])) {
    $search_performed = true;
    
    // Get search parameters (from POST or GET if redirected from dashboard)
    $keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : '';
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $experience = isset($_POST['experience']) ? $_POST['experience'] : [];
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $availability = isset($_POST['availability']) ? $_POST['availability'] : [];
    $match_worker_types = isset($_POST['match_worker_types']) ? $_POST['match_worker_types'] : (isset($_GET['match_worker_types']) ? $_GET['match_worker_types'] : false);
    
    // Build query
    $where_clauses = [];
    $params = [];
    $types = '';
    
    // Base query joining jobseekers with users
    $query = "SELECT j.*, u.email, u.signup_date
              FROM jobseekers j
              JOIN users u ON j.user_id = u.user_id
              WHERE u.is_active = 1 AND u.account_status = 'approved'";
    
    // Keywords - search in various text fields
    if (!empty($keywords)) {
        $where_clauses[] = "(j.full_name LIKE ? OR j.skills LIKE ? OR j.bio LIKE ? OR j.current_job_title LIKE ?)";
        $keyword_param = "%" . $keywords . "%";
        $params[] = $keyword_param;
        $params[] = $keyword_param;
        $params[] = $keyword_param;
        $params[] = $keyword_param;
        $types .= "ssss";
    }
    
    // Skills - search for each skill
    if (!empty($skills)) {
        $skill_clauses = [];
        foreach ($skills as $skill) {
            $skill_clauses[] = "j.skills LIKE ?";
            $params[] = "%" . $skill . "%";
            $types .= "s";
        }
        $where_clauses[] = "(" . implode(" OR ", $skill_clauses) . ")";
    }
    
    // Experience level
    if (!empty($experience)) {
        $exp_clauses = [];
        foreach ($experience as $exp) {
            switch($exp) {
                case 'Entry Level':
                    $exp_clauses[] = "(j.years_of_experience >= 0 AND j.years_of_experience <= 2)";
                    break;
                case 'Mid Level':
                    $exp_clauses[] = "(j.years_of_experience >= 3 AND j.years_of_experience <= 5)";
                    break;
                case 'Senior Level':
                    $exp_clauses[] = "(j.years_of_experience >= 6 AND j.years_of_experience <= 10)";
                    break;
                case 'Expert':
                    $exp_clauses[] = "j.years_of_experience > 10";
                    break;
            }
        }
        if (!empty($exp_clauses)) {
            $where_clauses[] = "(" . implode(" OR ", $exp_clauses) . ")";
        }
    }
    
    // Location
    if (!empty($location)) {
        $where_clauses[] = "j.location LIKE ?";
        $params[] = "%" . $location . "%";
        $types .= "s";
    }
    
    // Availability
    if (!empty($availability)) {
        $avail_clauses = [];
        foreach ($availability as $avail) {
            $avail_clauses[] = "j.availability = ?";
            $params[] = $avail;
            $types .= "s";
        }
        $where_clauses[] = "(" . implode(" OR ", $avail_clauses) . ")";
    }
    
    // Match worker types if option is selected
    if ($match_worker_types && !empty($enterprise_worker_types)) {
        $worker_type_clauses = [];
        foreach ($enterprise_worker_types as $worker_type) {
            if (!empty($worker_type)) {
                $worker_type_clauses[] = "j.profession LIKE ?";
                $params[] = "%" . trim($worker_type) . "%";
                $types .= "s";
            }
        }
        if (!empty($worker_type_clauses)) {
            $where_clauses[] = "(" . implode(" OR ", $worker_type_clauses) . ")";
        }
    }
    
    // Combine where clauses
    if (!empty($where_clauses)) {
        $query .= " AND " . implode(" AND ", $where_clauses);
    }
    
    // Add order by and limit
    $query .= " ORDER BY j.last_active DESC LIMIT 50";
    
    // Execute the query
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        $stmt->close();
    } else if ($search_performed) {
        // Empty search, get recent active profiles
        $query = "SELECT j.*, u.email, u.signup_date 
                  FROM jobseekers j
                  JOIN users u ON j.user_id = u.user_id
                  WHERE u.is_active = 1 AND u.account_status = 'approved'
                  ORDER BY j.last_active DESC LIMIT 20";
        $result = $conn->query($query);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
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
    <title>Search Candidates - HireHive</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
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

        .candidate-card {
            transition: all 0.2s ease;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            overflow: hidden;
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .candidate-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .select2-container .select2-selection--multiple {
            min-height: 38px;
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
                        <a class="nav-link active" href="search-candidates.php">Find Candidates</a>
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
                    <h1>Search Candidates</h1>
                    <a href="dashboard-enterprise.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="card-title h5 mb-0">Search Filters</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="keywords" class="form-label">Keywords</label>
                                    <input type="text" class="form-control" id="keywords" name="keywords" value="<?php echo isset($_POST['keywords']) ? htmlspecialchars($_POST['keywords']) : ''; ?>" placeholder="Job title, skills, or specific requirements">
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" placeholder="City, state, or remote">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="skills" class="form-label">Skills</label>
                                    <select class="form-select select2-multi" id="skills" name="skills[]" multiple="multiple">
                                        <?php foreach ($skill_options as $skill): ?>
                                            <option value="<?php echo $skill; ?>" <?php 
                                                if(isset($_POST['skills']) && in_array($skill, $_POST['skills'])) echo 'selected'; 
                                            ?>><?php echo $skill; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="experience" class="form-label">Experience Level</label>
                                    <div>
                                        <?php foreach ($experience_levels as $level => $years): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="experience_<?php echo str_replace(' ', '_', $level); ?>" 
                                                    name="experience[]" value="<?php echo $level; ?>"
                                                    <?php if(isset($_POST['experience']) && in_array($level, $_POST['experience'])) echo 'checked'; ?>>
                                                <label class="form-check-label" for="experience_<?php echo str_replace(' ', '_', $level); ?>">
                                                    <?php echo $level; ?> (<?php echo $years; ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Availability</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="availability_fulltime" 
                                            name="availability[]" value="Full-time"
                                            <?php if(isset($_POST['availability']) && in_array('Full-time', $_POST['availability'])) echo 'checked'; ?>>
                                        <label class="form-check-label" for="availability_fulltime">Full-time</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="availability_parttime" 
                                            name="availability[]" value="Part-time"
                                            <?php if(isset($_POST['availability']) && in_array('Part-time', $_POST['availability'])) echo 'checked'; ?>>
                                        <label class="form-check-label" for="availability_parttime">Part-time</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="availability_contract" 
                                            name="availability[]" value="Contract"
                                            <?php if(isset($_POST['availability']) && in_array('Contract', $_POST['availability'])) echo 'checked'; ?>>
                                        <label class="form-check-label" for="availability_contract">Contract</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="availability_freelance" 
                                            name="availability[]" value="Freelance"
                                            <?php if(isset($_POST['availability']) && in_array('Freelance', $_POST['availability'])) echo 'checked'; ?>>
                                        <label class="form-check-label" for="availability_freelance">Freelance</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="availability_internship" 
                                            name="availability[]" value="Internship"
                                            <?php if(isset($_POST['availability']) && in_array('Internship', $_POST['availability'])) echo 'checked'; ?>>
                                        <label class="form-check-label" for="availability_internship">Internship</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="match_worker_types" 
                                        name="match_worker_types" value="1"
                                        <?php if(isset($_POST['match_worker_types']) || isset($_GET['match_worker_types'])) echo 'checked'; ?>>
                                    <label class="form-check-label" for="match_worker_types">
                                        <strong>Match with candidates based on your worker types</strong>
                                        <div class="form-text">
                                            This will filter candidates whose professions match your company's worker types.
                                            <br>Your worker types: <?php echo !empty($user_data['worker_types']) ? htmlspecialchars($user_data['worker_types']) : '<em>None specified</em>'; ?>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="reset" class="btn btn-outline-secondary">Reset Filters</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search Candidates
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($search_performed): ?>
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h2 class="card-title h5 mb-0">Search Results</h2>
                            <span class="badge bg-primary"><?php echo count($results); ?> candidates found</span>
                        </div>
                        <div class="card-body">
                            <?php if (count($results) > 0): ?>
                                <div class="row">
                                    <?php foreach ($results as $candidate): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="candidate-card p-3">
                                                <div class="d-flex">
                                                    <div class="me-3">
                                                        <?php if (isset($candidate['profile_image']) && !empty($candidate['profile_image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($candidate['profile_image']); ?>" alt="Profile Image" class="candidate-img">
                                                        <?php else: ?>
                                                            <div class="candidate-img">
                                                                <i class="fas fa-user fa-2x text-secondary"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h3 class="h5 mb-1"><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                                        <p class="mb-1">
                                                            <?php 
                                                                echo !empty($candidate['current_job_title']) 
                                                                    ? htmlspecialchars($candidate['current_job_title']) 
                                                                    : htmlspecialchars($candidate['profession']); 
                                                            ?>
                                                        </p>
                                                        <p class="text-muted mb-1">
                                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($candidate['location'] ?? 'Not specified'); ?>
                                                            <?php if (isset($candidate['availability']) && !empty($candidate['availability'])): ?>
                                                                &nbsp;&bull;&nbsp;
                                                                <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($candidate['availability']); ?>
                                                            <?php endif; ?>
                                                            <?php if (isset($candidate['years_of_experience'])): ?>
                                                                &nbsp;&bull;&nbsp;
                                                                <i class="fas fa-clock"></i> <?php echo $candidate['years_of_experience']; ?> years experience
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <h4 class="h6 mb-2">Skills:</h4>
                                                    <div class="skills-container">
                                                        <?php
                                                        $skills = explode(',', $candidate['skills']);
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
                                                </div>
                                                
                                                <?php if (!empty($candidate['profession'])): ?>
                                                <div class="mt-3">
                                                    <h4 class="h6 mb-2">Profession:</h4>
                                                    <div class="skills-container">
                                                        <?php
                                                        $professions = explode(',', $candidate['profession']);
                                                        foreach ($professions as $profession):
                                                            $profession = trim($profession);
                                                            if (!empty($profession)):
                                                        ?>
                                                            <span class="skill-badge bg-light-success">
                                                                <?php echo htmlspecialchars($profession); ?>
                                                                <?php if (in_array($profession, $enterprise_worker_types)): ?>
                                                                    <i class="fas fa-check-circle text-success ms-1" title="Matches your worker type needs"></i>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="mt-3">
                                                    <?php if (isset($candidate['bio']) && !empty($candidate['bio'])): ?>
                                                        <p class="small"><?php echo htmlspecialchars(substr($candidate['bio'], 0, 150)) . (strlen($candidate['bio']) > 150 ? '...' : ''); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="mt-3 d-flex justify-content-between align-items-center">
                                                    <span class="small text-muted">
                                                        <?php if (isset($candidate['last_active'])): ?>
                                                            Last active: <?php echo date('M d, Y', strtotime($candidate['last_active'])); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <div>
                                                        <a href="view-candidate.php?id=<?php echo $candidate['jobseeker_id']; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                                        <?php if (isset($candidate['resume_path']) && !empty($candidate['resume_path'])): ?>
                                                            <a href="<?php echo htmlspecialchars($candidate['resume_path']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Resume</a>
                                                        <?php endif; ?>
                                                        <a href="mailto:<?php echo $candidate['email']; ?>" class="btn btn-sm btn-outline-success">Contact</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No candidates found matching your search criteria. Try broadening your search or using different keywords.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h3>Find the Perfect Candidates</h3>
                            <p class="text-muted">Use the search filters above to find candidates that match your requirements.</p>
                            <?php if (!empty($enterprise_worker_types)): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Pro Tip:</strong> Use the "Match with candidates based on your worker types" option to find candidates that match your company's needs.
                                </div>
                            <?php endif; ?>
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

    <!-- Bootstrap JS, jQuery, and Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2 for multi-select dropdowns
            $('.select2-multi').select2({
                placeholder: "Select skills",
                allowClear: true,
                tags: true
            });
            
            // Handle form reset to also reset Select2
            $('button[type="reset"]').click(function() {
                $('.select2-multi').val(null).trigger('change');
                $('#match_worker_types').prop('checked', false);
            });
        });
    </script>
</body>
</html> 