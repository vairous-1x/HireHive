<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$message_id = $name = $email = $message = $submission_date = $status = '';

// Check if message ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: dashboard-admin.php#messages");
    exit;
}

$message_id = $_GET['id'];

// Mark message as read
$sql = "UPDATE contact_messages SET status = 'read' WHERE message_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $message_id);
$stmt->execute();

// Get message data
$sql = "SELECT * FROM contact_messages WHERE message_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $message_data = $result->fetch_assoc();
    $name = $message_data['name'];
    $email = $message_data['email'];
    $message = $message_data['message'];
    $submission_date = $message_data['submission_date'];
    $status = $message_data['status'];
} else {
    header("location: dashboard-admin.php#messages");
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
    <title>View Message - HireHive Admin</title>
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
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Contact Message</h2>
                        <span class="badge <?php echo $status === 'unread' ? 'bg-warning' : ($status === 'replied' ? 'bg-success' : 'bg-info'); ?>">
                            <?php echo ucfirst(htmlspecialchars($status)); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 border-bottom pb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>From: <?php echo htmlspecialchars($name); ?></h5>
                                    <p class="text-muted mb-0">
                                        <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p class="text-muted mb-0">Received: <?php echo date('M d, Y h:i A', strtotime($submission_date)); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="message-content mb-4">
                            <h5>Message</h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($message)); ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard-admin.php#messages" class="btn btn-secondary">Back to Messages</a>
                            <div>
                                <a href="mailto:<?php echo htmlspecialchars($email); ?>" class="btn btn-primary">
                                    <i class="fas fa-reply me-1"></i> Reply via Email
                                </a>
                                <button type="button" class="btn btn-danger ms-2" 
                                        onclick="if(confirm('Are you sure you want to delete this message?')) window.location.href='delete-message.php?id=<?php echo $message_id; ?>'">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            </div>
                        </div>
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