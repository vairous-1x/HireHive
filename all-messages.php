<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';
$messages = array();

// Check for status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get success or error messages from URL parameters (for redirects from other pages)
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success']) && !empty($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Get all messages with filter
$sql = "SELECT * FROM contact_messages";
if ($status_filter != 'all') {
    $sql .= " WHERE status = ?";
}
$sql .= " ORDER BY submission_date DESC";

if ($stmt = $conn->prepare($sql)) {
    if ($status_filter != 'all') {
        $stmt->bind_param("s", $status_filter);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
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
    <title>All Messages - HireHive Admin</title>
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
                        <a class="nav-link" href="dashboard-admin.php#users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-admin.php#messages">Messages</a>
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
                        <h2 class="mb-0">All Contact Messages</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <div class="btn-group" role="group">
                                <a href="all-messages.php" class="btn btn-outline-primary <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All Messages</a>
                                <a href="all-messages.php?status=unread" class="btn btn-outline-primary <?php echo $status_filter == 'unread' ? 'active' : ''; ?>">Unread</a>
                                <a href="all-messages.php?status=read" class="btn btn-outline-primary <?php echo $status_filter == 'read' ? 'active' : ''; ?>">Read</a>
                            </div>
                        </div>
                        
                        <?php if (count($messages) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $message): ?>
                                            <tr class="<?php echo $message['status'] == 'unread' ? 'table-primary' : ''; ?>">
                                                <td><?php echo $message['message_id']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $message['status'] == 'unread' ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                                        <?php echo ucfirst($message['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                <td><?php echo isset($message['subject']) ? htmlspecialchars($message['subject']) : '-'; ?></td>
                                                <td><?php echo htmlspecialchars(substr($message['message'], 0, 50)) . (strlen($message['message']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($message['submission_date'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view-message.php?id=<?php echo $message['message_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                        <a href="mailto:<?php echo $message['email']; ?>" class="btn btn-sm btn-outline-success">Reply</a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="if(confirm('Are you sure you want to delete this message?')) window.location.href='delete-message.php?id=<?php echo $message['message_id']; ?>'">
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
                            <div class="alert alert-info">No messages found.</div>
                        <?php endif; ?>
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