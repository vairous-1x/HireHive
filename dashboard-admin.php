<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Get counts
$user_count = 0;
$enterprise_count = 0;
$jobseeker_count = 0;
$job_count = 0;
$contact_count = 0;

// Count total users
$count_sql = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($count_sql);
if ($result && $row = $result->fetch_assoc()) {
    $user_count = $row['count'];
}

// Count enterprises
$count_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'enterprise'";
$result = $conn->query($count_sql);
if ($result && $row = $result->fetch_assoc()) {
    $enterprise_count = $row['count'];
}

// Count job seekers
$count_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'job-seeker'";
$result = $conn->query($count_sql);
if ($result && $row = $result->fetch_assoc()) {
    $jobseeker_count = $row['count'];
}

// Count job listings
$count_sql = "SELECT COUNT(*) as count FROM joblistings";
$result = $conn->query($count_sql);
if ($result && $row = $result->fetch_assoc()) {
    $job_count = $row['count'];
}

// Count contact messages
$count_sql = "SELECT COUNT(*) as count FROM contact_messages";
$result = $conn->query($count_sql);
if ($result && $row = $result->fetch_assoc()) {
    $contact_count = $row['count'];
}

// Get recent users
$recent_users = array();
$sql = "SELECT u.user_id, u.email, u.role, u.created_at, 
        CASE 
            WHEN u.role = 'enterprise' THEN e.company_name
            WHEN u.role = 'job-seeker' THEN j.full_name
            ELSE 'Admin'
        END as name
        FROM users u
        LEFT JOIN enterprises e ON u.user_id = e.user_id
        LEFT JOIN jobseekers j ON u.user_id = j.user_id
        ORDER BY u.created_at DESC
        LIMIT 10";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
}

// Get unread contact messages
$messages = array();
$sql = "SELECT * FROM contact_messages WHERE status = 'unread' ORDER BY submission_date DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
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
    <title>Admin Dashboard - HireHive</title>
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
                        <a class="nav-link active" href="dashboard-admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#messages">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#settings">Settings</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-outline-light">Log Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Main Content -->
    <div class="container py-5">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h1>Admin Dashboard</h1>
                <p class="lead">Manage users, view messages, and maintain the HireHive platform.</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text stat-number"><?php echo $user_count; ?></p>
                        <p class="text-muted">Registered accounts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Enterprises</h5>
                        <p class="card-text stat-number"><?php echo $enterprise_count; ?></p>
                        <p class="text-muted">Business accounts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Job Seekers</h5>
                        <p class="card-text stat-number"><?php echo $jobseeker_count; ?></p>
                        <p class="text-muted">Individual accounts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">New Messages</h5>
                        <p class="card-text stat-number"><?php echo count($messages); ?></p>
                        <p class="text-muted">Unread contact messages</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center mb-3">
                                <a href="#" class="btn btn-primary btn-lg action-button" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                                    <span>Add New User</span>
                                </a>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <a href="#messages" class="btn btn-success btn-lg action-button">
                                    <i class="fas fa-envelope fa-2x mb-2"></i>
                                    <span>View Messages</span>
                                </a>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <a href="all-users.php" class="btn btn-info btn-lg action-button text-white">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <span>Manage Users</span>
                                </a>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <a href="backup.php" class="btn btn-warning btn-lg action-button text-dark">
                                    <i class="fas fa-database fa-2x mb-2"></i>
                                    <span>Backup Database</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <section id="users" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Recent Users</h2>
                <a href="all-users.php" class="btn btn-outline-primary">View All Users</a>
            </div>
            
            <?php if (count($recent_users) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
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
                <div class="alert alert-info">No users found in the system.</div>
            <?php endif; ?>
        </section>

        <!-- Messages Section -->
        <section id="messages" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Recent Messages</h2>
                <a href="all-messages.php" class="btn btn-outline-primary">View All Messages</a>
            </div>
            
            <?php if (count($messages) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><?php echo $message['message_id']; ?></td>
                                    <td><?php echo htmlspecialchars($message['name']); ?></td>
                                    <td><?php echo htmlspecialchars($message['email']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($message['message'], 0, 50)) . (strlen($message['message']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($message['submission_date'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view-message.php?id=<?php echo $message['message_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="mailto:<?php echo $message['email']; ?>" class="btn btn-sm btn-outline-success">Reply</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="if(confirm('Are you sure?')) window.location.href='delete-message.php?id=<?php echo $message['message_id']; ?>'">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No new messages.</div>
            <?php endif; ?>
        </section>

        <!-- System Status Section -->
        <section id="settings" class="mb-5">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h2 class="mb-0">System Status</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Platform Statistics</h4>
                            <ul class="list-group mb-4">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Total Users
                                    <span class="badge bg-primary rounded-pill"><?php echo $user_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Enterprises
                                    <span class="badge bg-primary rounded-pill"><?php echo $enterprise_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Job Seekers
                                    <span class="badge bg-primary rounded-pill"><?php echo $jobseeker_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Job Listings
                                    <span class="badge bg-primary rounded-pill"><?php echo $job_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Contact Messages
                                    <span class="badge bg-primary rounded-pill"><?php echo $contact_count; ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4>Administrative Tools</h4>
                            <div class="list-group">
                                <a href="backup.php" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Backup Database</h5>
                                        <small>System</small>
                                    </div>
                                    <p class="mb-1">Create a backup of the entire database.</p>
                                </a>
                                <a href="system-logs.php" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">System Logs</h5>
                                        <small>Monitoring</small>
                                    </div>
                                    <p class="mb-1">View system logs and error reports.</p>
                                </a>
                                <a href="cleanup.php" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Database Cleanup</h5>
                                        <small>Maintenance</small>
                                    </div>
                                    <p class="mb-1">Remove old and unused data.</p>
                                </a>
                                <a href="site-settings.php" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Site Settings</h5>
                                        <small>Configuration</small>
                                    </div>
                                    <p class="mb-1">Configure site-wide settings and options.</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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