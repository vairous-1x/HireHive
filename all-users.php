<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';
$users = array();

// Check for role filter
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Get success or error messages from URL parameters (for redirects from other pages)
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success']) && !empty($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Get all users with filter - removed last_login field that doesn't exist
$sql = "SELECT u.user_id, u.email, u.role, u.created_at, 
        CASE 
            WHEN u.role = 'enterprise' THEN e.company_name
            WHEN u.role = 'job-seeker' THEN j.full_name
            ELSE 'Admin'
        END as name
        FROM users u
        LEFT JOIN enterprises e ON u.user_id = e.user_id
        LEFT JOIN jobseekers j ON u.user_id = j.user_id";

if ($role_filter != 'all') {
    $sql .= " WHERE u.role = ?";
}
$sql .= " ORDER BY u.created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    if ($role_filter != 'all') {
        $stmt->bind_param("s", $role_filter);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
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
    <title>All Users - HireHive Admin</title>
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
                        <a class="nav-link active" href="dashboard-admin.php#users">Users</a>
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h2 class="mb-0">All Users</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn-group" role="group">
                                        <a href="all-users.php" class="btn btn-outline-primary <?php echo $role_filter == 'all' ? 'active' : ''; ?>">All Users</a>
                                        <a href="all-users.php?role=admin" class="btn btn-outline-danger <?php echo $role_filter == 'admin' ? 'active' : ''; ?>">Admin</a>
                                        <a href="all-users.php?role=enterprise" class="btn btn-outline-primary <?php echo $role_filter == 'enterprise' ? 'active' : ''; ?>">Enterprise</a>
                                        <a href="all-users.php?role=job-seeker" class="btn btn-outline-success <?php echo $role_filter == 'job-seeker' ? 'active' : ''; ?>">Job Seeker</a>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="fas fa-user-plus me-2"></i> Add New User
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (count($users) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Registered On</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['user_id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $user['role'] === 'admin' ? 'bg-danger' : 
                                                            ($user['role'] === 'enterprise' ? 'bg-primary' : 'bg-success'); 
                                                    ?>">
                                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td>Not Available</td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit-user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                        <?php if ($user['role'] !== 'admin'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='delete-user.php?id=<?php echo $user['user_id']; ?>'">
                                                                Delete
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No users found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add-user.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="user-email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="user-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="user-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="user-password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="user-role" class="form-label">Role</label>
                            <select class="form-select" id="user-role" name="role" required>
                                <option value="job-seeker">Job Seeker</option>
                                <option value="enterprise">Enterprise</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
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