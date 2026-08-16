<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';

// Check if message ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: dashboard-admin.php?error=" . urlencode("No message ID provided"));
    exit;
}

$message_id = $_GET['id'];

// Delete the message
$sql = "DELETE FROM contact_messages WHERE message_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $message_id);

if ($stmt->execute()) {
    $success_message = "Message deleted successfully!";
} else {
    $error_message = "Error deleting message: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to admin dashboard
header("location: dashboard-admin.php#messages" . (!empty($error_message) ? "?error=" . urlencode($error_message) : (!empty($success_message) ? "?success=" . urlencode($success_message) : "")));
exit;
?> 