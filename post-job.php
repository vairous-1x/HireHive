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
$user_data = get_user_data($conn, 'enterprises');
$error_message = '';
$success_message = '';

// Get enterprise_id
$stmt = $conn->prepare("SELECT enterprise_id FROM enterprises WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$enterprise = $result->fetch_assoc();
$enterprise_id = $enterprise['enterprise_id'];
$stmt->close();

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = $_POST['title'];
    $location = $_POST['location'];
    $job_type = $_POST['job_type'];
    
    // Create salary range from min and max fields
    $salary_min = isset($_POST['salary_min']) ? $_POST['salary_min'] : '';
    $salary_max = isset($_POST['salary_max']) ? $_POST['salary_max'] : '';
    $salary_range = '';
    if (!empty($salary_min) && !empty($salary_max)) {
        $salary_range = '$' . number_format($salary_min) . ' - $' . number_format($salary_max);
    } elseif (!empty($salary_min)) {
        $salary_range = '$' . number_format($salary_min) . '+';
    } elseif (!empty($salary_max)) {
        $salary_range = 'Up to $' . number_format($salary_max);
    }
    
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $responsibilities = $_POST['responsibilities'];
    $benefits = isset($_POST['benefits']) ? $_POST['benefits'] : '';
    $status = $_POST['status'];
    $closing_date = $_POST['application_deadline'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert new job listing
        $sql = "INSERT INTO joblistings (
                    enterprise_id, 
                    title, 
                    description, 
                    requirements, 
                    location, 
                    job_type, 
                    salary_range, 
                    status, 
                    posted_date, 
                    closing_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issssssss", 
            $enterprise_id,
            $title, 
            $description, 
            $requirements, 
            $location, 
            $job_type, 
            $salary_range, 
            $status, 
            $closing_date
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error posting job: " . $stmt->error);
        }
        
        $job_id = $conn->insert_id;
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $success_message = "Job posted successfully!";
        
        // Redirect to job listings if status is not draft
        if ($status !== 'draft') {
            header("Location: dashboard-enterprise.php#job-listings");
            exit;
        }
        
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
    <title>Post New Job - HireHive</title>
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
                    <h1>Post a New Job</h1>
                    <a href="dashboard-enterprise.php#job-listings" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <h3 class="mb-4">Job Details</h3>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Job Title<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="location" name="location" placeholder="City, State or Remote" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="job_type" class="form-label">Job Type<span class="text-danger">*</span></label>
                                    <select class="form-select" id="job_type" name="job_type" required>
                                        <option value="">Select Type</option>
                                        <option value="full-time">Full-time</option>
                                        <option value="part-time">Part-time</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                        <option value="remote">Remote</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="salary_min" class="form-label">Minimum Salary</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="salary_min" name="salary_min" min="0" step="1000" placeholder="Min yearly salary">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="salary_max" class="form-label">Maximum Salary</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="salary_max" name="salary_max" min="0" step="1000" placeholder="Max yearly salary">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                <div class="form-text">Provide a detailed description of the role and responsibilities.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="4" required></textarea>
                                <div class="form-text">List qualifications, skills, and experience needed for the role.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="responsibilities" class="form-label">Responsibilities</label>
                                <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4"></textarea>
                                <div class="form-text">Detail the day-to-day responsibilities for this position.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="benefits" class="form-label">Benefits</label>
                                <textarea class="form-control" id="benefits" name="benefits" rows="3"></textarea>
                                <div class="form-text">List any benefits or perks offered with this position.</div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Posting Status<span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="open">Open - Publish Now</option>
                                        <option value="draft">Draft - Save for Later</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="application_deadline" class="form-label">Application Deadline<span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="application_deadline" name="application_deadline" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard-enterprise.php#job-listings" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Post Job</button>
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
    
    <!-- Custom Script -->
    <script>
        // Set minimum date for deadline to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const dateString = today.toISOString().split('T')[0];
            document.getElementById('application_deadline').min = dateString;
            
            // Default to 30 days from now
            const defaultDeadline = new Date();
            defaultDeadline.setDate(today.getDate() + 30);
            document.getElementById('application_deadline').value = defaultDeadline.toISOString().split('T')[0];
        });
    </script>
</body>
</html> 