<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function check_login() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: login.php");
        exit;
    }
}

// Check if user is logged in AND has the correct role
function check_role($required_role) {
    check_login();
    
    // If user is an admin, always redirect to admin dashboard unless that's what's required
    if ($_SESSION["role"] === "admin" && $required_role !== "admin") {
        header("location: dashboard-admin.php");
        exit;
    }
    
    // For non-admin users, redirect to the appropriate dashboard if role doesn't match
    if ($_SESSION["role"] !== $required_role) {
        // Redirect to the correct dashboard based on role
        if ($_SESSION["role"] === "job-seeker") {
            header("location: dashboard-job-seeker.php");
        } elseif ($_SESSION["role"] === "enterprise") {
            header("location: dashboard-enterprise.php");
        } else {
            header("location: dashboard-admin.php");
        }
        exit;
    }
}

// Function to get user info from database based on user_id in session
function get_user_data($conn, $table) {
    $user_id = $_SESSION["user_id"];
    
    // Get user data based on table type
    if ($table === 'jobseekers') {
        $sql = "SELECT j.*, u.email FROM jobseekers j 
                JOIN users u ON j.user_id = u.user_id 
                WHERE j.user_id = ?";
    } else if ($table === 'enterprises') {
        $sql = "SELECT e.*, u.email FROM enterprises e 
                JOIN users u ON e.user_id = u.user_id 
                WHERE e.user_id = ?";
    } else if ($table === 'admin') {
        // For admin users, just return basic information
        $sql = "SELECT u.user_id, u.email, u.role, 'Admin' as name, 'System Administrator' as company_name
                FROM users u 
                WHERE u.user_id = ? AND u.role = 'admin'";
    } else {
        return false;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}
?> 