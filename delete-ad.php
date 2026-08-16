<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an enterprise
check_role("enterprise");

// Check if ad ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard-enterprise.php#advertisements");
    exit;
}

$ad_id = $_GET['id'];
$user_id = $_SESSION["user_id"];
$error_message = '';
$success_message = '';
$ad = null;

// Get enterprise_id
$stmt = $conn->prepare("SELECT enterprise_id FROM enterprises WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$enterprise = $result->fetch_assoc();
$enterprise_id = $enterprise['enterprise_id'];
$stmt->close();

// Get ad details for this enterprise
$ad_sql = "SELECT a.* 
           FROM advertisements a
           WHERE a.ad_id = ? AND a.enterprise_id = ?";

$stmt = $conn->prepare($ad_sql);
$stmt->bind_param("ii", $ad_id, $enterprise_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard-enterprise.php?error=notfound#advertisements");
    exit;
}

$ad = $result->fetch_assoc();
$stmt->close();

// Process deletion if confirmed
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete the advertisement
        $sql = "DELETE FROM advertisements WHERE ad_id = ? AND enterprise_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $ad_id, $enterprise_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting advertisement: " . $stmt->error);
        }
        
        // Delete the associated logo file if it exists
        if (!empty($ad['logo_path']) && file_exists($ad['logo_path'])) {
            unlink($ad['logo_path']);
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect back to dashboard with success message
        header("Location: dashboard-enterprise.php?success=deleted#advertisements");
        exit;
        
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
    <title>Delete Advertisement - HireHive</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .ad-preview-image {
            max-width: 100%;
            max-height: 200px;
            margin-bottom: 15px;
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h1 class="card-title h5 mb-0">Delete Advertisement: <?php echo htmlspecialchars($ad['title']); ?></h1>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> You are about to delete this advertisement. This action cannot be undone.
                        </div>
                        
                        <div class="mb-4">
                            <h5>Advertisement Details:</h5>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <?php if ($ad['logo_path']): ?>
                                        <div class="text-center mb-3">
                                            <img src="<?php echo htmlspecialchars($ad['logo_path']); ?>" alt="Advertisement Logo" class="ad-preview-image">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h5 class="card-title"><?php echo htmlspecialchars($ad['title']); ?></h5>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($ad['description'])); ?></p>
                                    
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item">
                                            <strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($ad['status'])); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Created Date:</strong> <?php echo date('M d, Y', strtotime($ad['created_date'])); ?>
                                        </li>
                                        <?php if ($ad['expire_date']): ?>
                                            <li class="list-group-item">
                                                <strong>Expires:</strong> <?php echo date('M d, Y', strtotime($ad['expire_date'])); ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($ad['url']): ?>
                                            <li class="list-group-item">
                                                <strong>URL:</strong> <a href="<?php echo htmlspecialchars($ad['url']); ?>" target="_blank"><?php echo htmlspecialchars($ad['url']); ?></a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $ad_id); ?>">
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="confirm" onchange="document.getElementById('confirm_btn').disabled = !this.checked;">
                                <label class="form-check-label" for="confirm">
                                    I understand that I am about to delete this advertisement and this action cannot be undone.
                                </label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard-enterprise.php#advertisements" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                
                                <input type="hidden" name="confirm_delete" value="yes">
                                <button type="submit" id="confirm_btn" class="btn btn-danger" disabled>
                                    <i class="fas fa-trash me-2"></i>Delete Advertisement
                                </button>
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