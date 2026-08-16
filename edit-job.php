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

// Get job details for this enterprise
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
        // Update job listing
        $sql = "UPDATE joblistings SET 
                    title = ?, 
                    location = ?, 
                    job_type = ?, 
                    salary_range = ?, 
                    description = ?, 
                    requirements = ?, 
                    status = ?, 
                    closing_date = ?
                WHERE job_id = ? AND enterprise_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssii", 
            $title, 
            $location, 
            $job_type, 
            $salary_range, 
            $description, 
            $requirements, 
            $status, 
            $closing_date,
            $job_id,
            $enterprise_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating job: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $success_message = "Job updated successfully!";
        
        // Refresh job data
        $stmt = $conn->prepare($job_sql);
        $stmt->bind_param("ii", $job_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $job = $result->fetch_assoc();
        $stmt->close();
        
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
    <title>Edit Job - HireHive</title>
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
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Edit Job</h1>
                    <div>
                        <a href="view-job.php?id=<?php echo $job_id; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-eye me-2"></i>View Job
                        </a>
                        <a href="dashboard-enterprise.php#job-listings" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
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
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $job_id); ?>">
                            <h3 class="mb-4">Job Details</h3>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Job Title<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="location" name="location" placeholder="City, State or Remote" value="<?php echo htmlspecialchars($job['location']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="job_type" class="form-label">Job Type<span class="text-danger">*</span></label>
                                    <select class="form-select" id="job_type" name="job_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Full-time" <?php if ($job['job_type'] == 'Full-time') echo 'selected'; ?>>Full-time</option>
                                        <option value="Part-time" <?php if ($job['job_type'] == 'Part-time') echo 'selected'; ?>>Part-time</option>
                                        <option value="Contract" <?php if ($job['job_type'] == 'Contract') echo 'selected'; ?>>Contract</option>
                                        <option value="Freelance" <?php if ($job['job_type'] == 'Freelance') echo 'selected'; ?>>Freelance</option>
                                        <option value="Internship" <?php if ($job['job_type'] == 'Internship') echo 'selected'; ?>>Internship</option>
                                        <option value="Temporary" <?php if ($job['job_type'] == 'Temporary') echo 'selected'; ?>>Temporary</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="salary_min" class="form-label">Minimum Salary</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="salary_min" name="salary_min" min="0" step="1000" placeholder="Min yearly salary" value="<?php echo $job['salary_min']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="salary_max" class="form-label">Maximum Salary</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="salary_max" name="salary_max" min="0" step="1000" placeholder="Max yearly salary" value="<?php echo $job['salary_max']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                                <div class="form-text">Provide a detailed description of the role and responsibilities.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="4" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                                <div class="form-text">List qualifications, skills, and experience needed for the role.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="responsibilities" class="form-label">Responsibilities<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4" required><?php echo htmlspecialchars($job['responsibilities']); ?></textarea>
                                <div class="form-text">Detail the day-to-day responsibilities for this position.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="benefits" class="form-label">Benefits</label>
                                <textarea class="form-control" id="benefits" name="benefits" rows="3"><?php echo htmlspecialchars($job['benefits']); ?></textarea>
                                <div class="form-text">List any benefits or perks offered with this position.</div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Posting Status<span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="open" <?php if ($job['status'] == 'open') echo 'selected'; ?>>Open - Accepting Applications</option>
                                        <option value="draft" <?php if ($job['status'] == 'draft') echo 'selected'; ?>>Draft - Not Visible</option>
                                        <option value="closed" <?php if ($job['status'] == 'closed') echo 'selected'; ?>>Closed - No Longer Accepting Applications</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="application_deadline" class="form-label">Application Deadline</label>
                                    <input type="date" class="form-control" id="application_deadline" name="application_deadline" value="<?php echo $job['application_deadline']; ?>" min="<?php echo date('Y-m-d'); ?>">
                                    <div class="form-text">If left blank, no deadline will be displayed.</div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view-job.php?id=<?php echo $job_id; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
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