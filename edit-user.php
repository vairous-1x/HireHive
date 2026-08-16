<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$user_id = $email = $role = $name = '';
$error_message = '';
$success_message = '';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: dashboard-admin.php");
    exit;
}

$user_id = $_GET['id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = sanitize_input($conn, $_POST['email']);
    $role = $_POST['role'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update user email and role
        $sql = "UPDATE users SET email = ?, role = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $email, $role, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        $success_message = "User updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error updating user: " . $e->getMessage();
    }
}

// Get user data
$sql = "SELECT u.user_id, u.email, u.role, u.created_at, 
        CASE 
            WHEN u.role = 'enterprise' THEN e.company_name
            WHEN u.role = 'job-seeker' THEN j.full_name
            ELSE 'Admin'
        END as name
        FROM users u
        LEFT JOIN enterprises e ON u.user_id = e.user_id
        LEFT JOIN jobseekers j ON u.user_id = j.user_id
        WHERE u.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $email = $user['email'];
    $role = $user['role'];
    $name = $user['name'];
} else {
    header("location: dashboard-admin.php");
    exit;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - HireHive Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="assets/images/logo.png" alt="HireHive Logo" height="40">
                HireHive Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-admin.php#messages">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-admin.php#settings">Settings</a>
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
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h2 class="mb-0">Edit User</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <form action="edit-user.php?id=<?php echo $user_id; ?>" method="POST">
                            <div class="mb-3">
                                <label for="user-id" class="form-label">User ID</label>
                                <input type="text" class="form-control" id="user-id" value="<?php echo $user_id; ?>" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label for="user-name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="user-name" value="<?php echo htmlspecialchars($name); ?>" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label for="user-email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="user-email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="user-role" class="form-label">Role</label>
                                <select class="form-select" id="user-role" name="role" required>
                                    <option value="job-seeker" <?php echo $role === 'job-seeker' ? 'selected' : ''; ?>>Job Seeker</option>
                                    <option value="enterprise" <?php echo $role === 'enterprise' ? 'selected' : ''; ?>>Enterprise</option>
                                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="user-password" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" id="user-password" name="password">
                                <div class="form-text">Only fill this field if you want to change the password.</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard-admin.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; HireHive. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-decoration-none text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-decoration-none text-white me-3">Terms of Service</a>
                    <a href="#" class="text-decoration-none text-white">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 