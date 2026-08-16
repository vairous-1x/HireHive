<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is a job seeker
check_role("job-seeker");

// Get user data
$user_data = get_user_data($conn, 'jobseekers');
$error_message = '';
$success_message = '';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $education = $_POST['education'];
    $skills = $_POST['skills'];
    $profession = isset($_POST['profession']) ? implode(',', $_POST['profession']) : '';
    $other_profession = isset($_POST['other_profession']) ? $_POST['other_profession'] : '';
    $linkedin = isset($_POST['linkedin']) ? $_POST['linkedin'] : '';
    $website = isset($_POST['website']) ? $_POST['website'] : '';
    
    // Prepare the update statement
    $sql = "UPDATE jobseekers SET 
            full_name = ?,
            phone = ?,
            education = ?,
            skills = ?,
            profession = ?,
            other_profession = ?,
            linkedin = ?,
            website = ?
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $full_name, $phone, $education, $skills, $profession, 
                     $other_profession, $linkedin, $website, $_SESSION["user_id"]);
    
    // Handle portfolio/CV upload if a new file was submitted
    if (isset($_FILES['portfolio']) && $_FILES['portfolio']['error'] == 0) {
        $upload_dir = 'uploads/portfolios/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['portfolio']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['portfolio']['tmp_name'], $target_file)) {
            // Update the portfolio field
            $portfolio_sql = "UPDATE jobseekers SET portfolio = ? WHERE user_id = ?";
            $portfolio_stmt = $conn->prepare($portfolio_sql);
            $portfolio_stmt->bind_param("si", $target_file, $_SESSION["user_id"]);
            $portfolio_stmt->execute();
            $portfolio_stmt->close();
        }
    }
    
    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
        // Refresh user data
        $user_data = get_user_data($conn, 'jobseekers');
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
    
    $stmt->close();
}

// Extract profession values
$professions = explode(',', $user_data['profession']);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - HireHive</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                        <a class="nav-link" href="dashboard-job-seeker.php#applications">My Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-job-seeker.php#profile">My Profile</a>
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h2 class="mb-0">Edit Profile</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="education" class="form-label">Education</label>
                                <select class="form-select" id="education" name="education" required>
                                    <option value="High School" <?php echo $user_data['education'] == 'High School' ? 'selected' : ''; ?>>High School</option>
                                    <option value="Associate's Degree" <?php echo $user_data['education'] == 'Associate\'s Degree' ? 'selected' : ''; ?>>Associate's Degree</option>
                                    <option value="Bachelor's Degree" <?php echo $user_data['education'] == 'Bachelor\'s Degree' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                    <option value="Master's Degree" <?php echo $user_data['education'] == 'Master\'s Degree' ? 'selected' : ''; ?>>Master's Degree</option>
                                    <option value="Doctorate" <?php echo $user_data['education'] == 'Doctorate' ? 'selected' : ''; ?>>Doctorate</option>
                                    <option value="Other" <?php echo $user_data['education'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profession" class="form-label">Profession/Job Title</label>
                                <select class="form-select select2" id="profession" name="profession[]" multiple required>
                                    <option value="developer" <?php echo in_array('developer', $professions) ? 'selected' : ''; ?>>Developer</option>
                                    <option value="designer" <?php echo in_array('designer', $professions) ? 'selected' : ''; ?>>Designer</option>
                                    <option value="marketing" <?php echo in_array('marketing', $professions) ? 'selected' : ''; ?>>Marketing Specialist</option>
                                    <option value="finance" <?php echo in_array('finance', $professions) ? 'selected' : ''; ?>>Finance/Accounting</option>
                                    <option value="hr" <?php echo in_array('hr', $professions) ? 'selected' : ''; ?>>HR/Recruitment</option>
                                    <option value="content" <?php echo in_array('content', $professions) ? 'selected' : ''; ?>>Content Writer</option>
                                    <option value="other" <?php echo in_array('other', $professions) ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="other-profession-container" style="display: <?php echo in_array('other', $professions) ? 'block' : 'none'; ?>;">
                                <label for="other_profession" class="form-label">Other Profession</label>
                                <input type="text" class="form-control" id="other_profession" name="other_profession" value="<?php echo htmlspecialchars($user_data['other_profession']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills (comma-separated)</label>
                                <textarea class="form-control" id="skills" name="skills" rows="3" required><?php echo htmlspecialchars($user_data['skills']); ?></textarea>
                                <div class="form-text">List your key skills separated by commas (e.g., JavaScript, Project Management, Photoshop)</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="linkedin" class="form-label">LinkedIn Profile</label>
                                    <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($user_data['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/yourprofile">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="website" class="form-label">Personal Website</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($user_data['website'] ?? ''); ?>" placeholder="https://yourwebsite.com">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="portfolio" class="form-label">CV/Portfolio</label>
                                <input type="file" class="form-control" id="portfolio" name="portfolio">
                                <?php if ($user_data['portfolio']): ?>
                                    <div class="form-text">
                                        Current file: <a href="<?php echo htmlspecialchars($user_data['portfolio']); ?>" target="_blank"><?php echo basename($user_data['portfolio']); ?></a>
                                        <br>Upload a new file to replace the current one.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="dashboard-job-seeker.php#profile" class="btn btn-secondary">Cancel</a>
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
    <!-- jQuery and Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();
            
            // Show/hide other profession field
            $('#profession').on('change', function() {
                const selected = $(this).val();
                if (selected && selected.includes('other')) {
                    $('#other-profession-container').show();
                } else {
                    $('#other-profession-container').hide();
                }
            });
        });
    </script>
</body>
</html> 