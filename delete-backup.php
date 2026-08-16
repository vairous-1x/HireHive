<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';

// Check if file parameter is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    $error_message = "No backup file specified";
} else {
    $filename = $_GET['file'];
    
    // Validate filename (only allow SQL files)
    if (!preg_match('/^hirehive_backup_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.sql$/', $filename)) {
        $error_message = "Invalid backup filename";
    } else {
        $backup_dir = 'backups/';
        $file_path = $backup_dir . $filename;
        
        // Check if file exists
        if (!file_exists($file_path)) {
            $error_message = "Backup file does not exist";
        } else {
            // Delete the file
            if (unlink($file_path)) {
                $success_message = "Backup file deleted successfully";
            } else {
                $error_message = "Error deleting backup file";
            }
        }
    }
}

// Redirect back to backup page with appropriate message
header("location: backup.php" . (!empty($error_message) ? "?error=" . urlencode($error_message) : (!empty($success_message) ? "?success=" . urlencode($success_message) : "")));
exit;
?> 