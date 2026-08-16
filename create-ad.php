<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an enterprise
check_role("enterprise");

// Get enterprise data
$user_data = get_user_data($conn, 'enterprises');
$error_message = '';
$success_message = '';

// Check if the advertisements table exists
function table_exists($conn, $table_name) {
    $result = $conn->query("SHOW TABLES LIKE '{$table_name}'");
    return $result->num_rows > 0;
}

// Create the advertisements table if it doesn't exist
if (!table_exists($conn, 'advertisements')) {
    $conn->query("CREATE TABLE advertisements (
        ad_id INT AUTO_INCREMENT PRIMARY KEY,
        enterprise_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        logo_path VARCHAR(255),
        url VARCHAR(255),
        status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
        created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expire_date DATE,
        FOREIGN KEY (enterprise_id) REFERENCES enterprises(enterprise_id)
    )");
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';
    $expire_date = isset($_POST['expire_date']) ? $_POST['expire_date'] : NULL;
    
    // Validate form data
    if (empty($title) || empty($description)) {
        $error_message = "Title and description are required.";
    } else {
        // Process logo upload if provided
        $logo_path = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['logo']['type'], $allowed_types)) {
                $error_message = "Only JPG, PNG, and GIF images are allowed.";
            } elseif ($_FILES['logo']['size'] > $max_size) {
                $error_message = "Image size must be less than 2MB.";
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/ads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'ad_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                    $logo_path = $target_file;
                } else {
                    $error_message = "Failed to upload image.";
                }
            }
        }
        
        if (empty($error_message)) {
            // Get enterprise ID
            $stmt = $conn->prepare("SELECT enterprise_id FROM enterprises WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION["user_id"]);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error_message = "Enterprise not found.";
            } else {
                $enterprise = $result->fetch_assoc();
                $enterprise_id = $enterprise['enterprise_id'];
                $stmt->close();
                
                // Insert advertisement
                $sql = "INSERT INTO advertisements (enterprise_id, title, description, logo_path, url, expire_date) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssss", $enterprise_id, $title, $description, $logo_path, $url, $expire_date);
                
                if ($stmt->execute()) {
                    $success_message = "Advertisement created successfully!";
                    // Reset form data after successful submission
                    $title = $description = $url = $expire_date = "";
                } else {
                    $error_message = "Error creating advertisement: " . $stmt->error;
                }
                $stmt->close();
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
    <title>Create Advertisement - HireHive</title>
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
                        <a class="nav-link active" href="dashboard-enterprise.php#advertisements">Advertisements</a>
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
                    <h1>Create Advertisement</h1>
                    <a href="dashboard-enterprise.php#advertisements" class="btn btn-outline-primary">
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
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Advertisement Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                                <div class="form-text">Choose a catchy title for your advertisement</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                <div class="form-text">Describe what makes your company unique and attractive for potential candidates</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="logo" class="form-label">Company Logo (optional)</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">Upload your company logo (max 2MB, JPG/PNG/GIF formats)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="url" class="form-label">Website URL (optional)</label>
                                <input type="url" class="form-control" id="url" name="url" value="<?php echo isset($url) ? htmlspecialchars($url) : ''; ?>" placeholder="https://example.com">
                                <div class="form-text">Link to your careers page or company website</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="expire_date" class="form-label">Expiration Date (optional)</label>
                                <input type="date" class="form-control" id="expire_date" name="expire_date" value="<?php echo isset($expire_date) ? htmlspecialchars($expire_date) : ''; ?>" min="<?php echo date('Y-m-d'); ?>">
                                <div class="form-text">When should this advertisement stop being displayed?</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard-enterprise.php#advertisements" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-success">Create Advertisement</button>
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