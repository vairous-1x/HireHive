<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an enterprise
check_role("enterprise");

// Get user data
$user_data = get_user_data($conn, 'enterprises');
$error_message = '';
$success_message = '';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $company_name = $_POST['company_name'];
    $phone = $_POST['phone'];
    $company_size = $_POST['company_size'];
    $product_service_description = $_POST['product_service_description'];
    $linkedin = isset($_POST['linkedin']) ? $_POST['linkedin'] : '';
    $website = isset($_POST['website']) ? $_POST['website'] : '';
    
    // Validate worker types
    $worker_types = isset($_POST['worker_types']) ? implode(',', $_POST['worker_types']) : '';
    $other_worker_type = isset($_POST['other_worker_type']) ? $_POST['other_worker_type'] : '';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get the enterprise_id from user_id
        $stmt = $conn->prepare("SELECT enterprise_id FROM enterprises WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Enterprise not found.");
        }
        
        $enterprise = $result->fetch_assoc();
        $enterprise_id = $enterprise['enterprise_id'];
        $stmt->close();
        
        // Update enterprise profile
        $update_sql = "UPDATE enterprises SET 
                      company_name = ?,
                      phone = ?,
                      company_size = ?,
                      product_service_description = ?,
                      linkedin = ?,
                      website = ?,
                      worker_types = ?,
                      other_worker_type = ?
                      WHERE enterprise_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssssssi", 
                         $company_name,
                         $phone,
                         $company_size,
                         $product_service_description,
                         $linkedin,
                         $website,
                         $worker_types,
                         $other_worker_type,
                         $enterprise_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating profile: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $success_message = "Profile updated successfully!";
        
        // Refresh user data
        $user_data = get_user_data($conn, 'enterprises');
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Get worker types for dropdown
$worker_type_options = [
    'Full-time', 'Part-time', 'Contract', 'Freelance', 
    'Internship', 'Temporary', 'Remote', 'Hybrid'
];

// Close connection (it will be reopened by the session.php include)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company Profile - HireHive</title>
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
                    <h1>Edit Company Profile</h1>
                    <a href="dashboard-enterprise.php#profile" class="btn btn-outline-primary">
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
                            <h3 class="mb-4">Basic Information</h3>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">Company Name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user_data['company_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="company_size" class="form-label">Company Size<span class="text-danger">*</span></label>
                                    <select class="form-select" id="company_size" name="company_size" required>
                                        <option value="1-10" <?php if ($user_data['company_size'] == '1-10') echo 'selected'; ?>>1-10 employees</option>
                                        <option value="11-50" <?php if ($user_data['company_size'] == '11-50') echo 'selected'; ?>>11-50 employees</option>
                                        <option value="51-200" <?php if ($user_data['company_size'] == '51-200') echo 'selected'; ?>>51-200 employees</option>
                                        <option value="201-500" <?php if ($user_data['company_size'] == '201-500') echo 'selected'; ?>>201-500 employees</option>
                                        <option value="501-1000" <?php if ($user_data['company_size'] == '501-1000') echo 'selected'; ?>>501-1000 employees</option>
                                        <option value="1000+" <?php if ($user_data['company_size'] == '1000+') echo 'selected'; ?>>1000+ employees</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                                    <div class="form-text">Email cannot be changed. Contact support if needed.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number<span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                                </div>
                            </div>
                            
                            <h3 class="mb-4 mt-5">Online Presence</h3>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="website" class="form-label">Company Website</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($user_data['website']); ?>" placeholder="https://yourcompany.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="linkedin" class="form-label">LinkedIn Profile</label>
                                    <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($user_data['linkedin']); ?>" placeholder="https://linkedin.com/company/yourcompany">
                                </div>
                            </div>
                            
                            <h3 class="mb-4 mt-5">Company Details</h3>
                            <div class="mb-3">
                                <label for="product_service_description" class="form-label">Product/Service Description<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="product_service_description" name="product_service_description" rows="5" required><?php echo htmlspecialchars($user_data['product_service_description']); ?></textarea>
                                <div class="form-text">Describe what your company does, your products or services.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Worker Types Needed<span class="text-danger">*</span></label>
                                <div class="row">
                                    <?php 
                                    $selected_types = explode(',', $user_data['worker_types']);
                                    foreach ($worker_type_options as $index => $option): 
                                    ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="worker_types[]" id="worker_type_<?php echo $index; ?>" value="<?php echo $option; ?>" <?php if (in_array($option, $selected_types)) echo 'checked'; ?>>
                                            <label class="form-check-label" for="worker_type_<?php echo $index; ?>">
                                                <?php echo $option; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="other_worker_type" class="form-label">Other Worker Type (optional)</label>
                                <input type="text" class="form-control" id="other_worker_type" name="other_worker_type" value="<?php echo htmlspecialchars($user_data['other_worker_type']); ?>">
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard-enterprise.php#profile" class="btn btn-outline-secondary me-md-2">Cancel</a>
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