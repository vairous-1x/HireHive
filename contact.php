<?php
// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Helper function to sanitize input if not already defined
if (!function_exists('sanitize_input')) {
    function sanitize_input($conn, $data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $conn->real_escape_string($data);
    }
}

// Create the contact_messages table if it doesn't exist
$check_table_sql = "SHOW TABLES LIKE 'contact_messages'";
$result = $conn->query($check_table_sql);
if ($result->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_sql = "CREATE TABLE contact_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('unread', 'read', 'replied') DEFAULT 'unread'
    )";
    
    $conn->query($create_table_sql);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $name = isset($_POST['contact-name']) ? sanitize_input($conn, $_POST['contact-name']) : '';
    $email = isset($_POST['email-contact']) ? sanitize_input($conn, $_POST['email-contact']) : '';
    $message = isset($_POST['contact-message']) ? sanitize_input($conn, $_POST['contact-message']) : '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($message)) {
        $_SESSION['contact_error'] = "All fields are required.";
        header("location: index.html#contact");
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['contact_error'] = "Invalid email format.";
        header("location: index.html#contact");
        exit;
    }
    
    // Insert contact message into database
    $sql = "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $name, $email, $message);
        
        if ($stmt->execute()) {
            // Success
            $_SESSION['contact_success'] = "Your message has been sent successfully!";
        } else {
            // Error
            $_SESSION['contact_error'] = "Something went wrong. Please try again.";
        }
        
        // Close statement
        $stmt->close();
    } else {
        $_SESSION['contact_error'] = "Database error. Please try again later.";
    }
    
    // Close connection
    $conn->close();
    
    // Redirect back to contact section
    header("location: index.html#contact");
    exit;
} else {
    // If not POST request, redirect to home page
    header("location: index.html");
    exit;
}
?> 