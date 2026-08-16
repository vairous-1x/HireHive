<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: dashboard-admin.php?error=" . urlencode("No user ID provided"));
    exit;
}

$user_id = $_GET['id'];

// Prevent admin from deleting themselves
if ($user_id == $_SESSION["user_id"]) {
    header("location: dashboard-admin.php?error=" . urlencode("You cannot delete your own account"));
    exit;
}

// Check if the user is an admin (for added protection)
$sql = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if ($user['role'] === 'admin') {
        header("location: dashboard-admin.php?error=" . urlencode("Admin accounts cannot be deleted"));
        exit;
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete the user (cascade will handle related records)
    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting user: " . $stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    $success_message = "User deleted successfully!";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $error_message = "Error deleting user: " . $e->getMessage();
}

// Close connection
$conn->close();

// Redirect back to admin dashboard
header("location: dashboard-admin.php" . (!empty($error_message) ? "?error=" . urlencode($error_message) : (!empty($success_message) ? "?success=" . urlencode($success_message) : "")));
exit;
?> 