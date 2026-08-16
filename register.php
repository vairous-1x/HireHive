<?php
// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Check if registrations are allowed
$allow_registrations = true; // Default to true if setting doesn't exist

// Check if site_settings table exists and get the setting
$check_table_sql = "SHOW TABLES LIKE 'site_settings'";
$result = $conn->query($check_table_sql);
if ($result->num_rows > 0) {
    // Table exists, check the setting
    $sql = "SELECT setting_value FROM site_settings WHERE setting_name = 'allow_registrations'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $allow_registrations = (bool)$row['setting_value'];
    }
}

// If registrations are not allowed, redirect to the homepage with an error
if (!$allow_registrations) {
    $_SESSION['registration_error'] = "User registrations are currently disabled. Please contact the administrator.";
    header("location: index.html");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Determine user role (enterprise or job-seeker)
    $role = '';
    $email = '';
    $password = '';
    
    // Check if it's an enterprise registration
    if (isset($_POST['email_e']) && isset($_POST['password_e'])) {
        $role = 'enterprise';
        $email = sanitize_input($conn, $_POST['email_e']);
        $password = $_POST['password_e'];
    } 
    // Check if it's a job seeker registration
    elseif (isset($_POST['email_j']) && isset($_POST['password_j'])) {
        $role = 'job-seeker';
        $email = sanitize_input($conn, $_POST['email_j']);
        $password = $_POST['password_j'];
    } else {
        die("Error: Form submitted without required fields");
    }
    
    // Validate email (check if it already exists)
    $email_check_sql = "SELECT user_id FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($email_check_sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // Email already exists
            $_SESSION['registration_error'] = "This email is already registered.";
            header("location: index.html");
            exit;
        }
        $stmt->close();
    }
    
    // Hash the password
    $hashed_password = hash_password($password);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into users table
        $user_sql = "INSERT INTO users (email, password, role) VALUES (?, ?, ?)";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("sss", $email, $hashed_password, $role);
        
        if (!$user_stmt->execute()) {
            throw new Exception("Error inserting user: " . $user_stmt->error);
        }
        
        $user_id = $conn->insert_id;
        $user_stmt->close();
        
        // Process based on role
        if ($role == 'enterprise') {
            // Handle enterprise registration
            $company_name = sanitize_input($conn, $_POST['cn']);
            $company_size = sanitize_input($conn, $_POST['cs']);
            
            // Handle worker_types array
            if (isset($_POST['worker_type']) && is_array($_POST['worker_type'])) {
                // Sanitize each value in the array
                $sanitized_worker_types = array_map(function($type) use ($conn) {
                    return sanitize_input($conn, $type);
                }, $_POST['worker_type']);
                
                $worker_types = implode(',', $sanitized_worker_types);
            } else {
                $worker_types = '';
            }
            
            $other_worker = isset($_POST['other_worker']) ? sanitize_input($conn, $_POST['other_worker']) : '';
            $product_service_description = sanitize_input($conn, $_POST['descrip']);
            $phone = sanitize_input($conn, $_POST['tel_e']);
            $linkedin = isset($_POST['linkedin']) ? sanitize_input($conn, $_POST['linkedin']) : '';
            $website = isset($_POST['website']) ? sanitize_input($conn, $_POST['website']) : '';
            
            $enterprise_sql = "INSERT INTO enterprises (user_id, company_name, company_size, worker_types, 
                               product_service_description, phone, linkedin, website, other_worker_type) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $enterprise_stmt = $conn->prepare($enterprise_sql);
            $enterprise_stmt->bind_param("issssssss", $user_id, $company_name, $company_size, $worker_types, 
                                     $product_service_description, $phone, $linkedin, $website, $other_worker);
            
            if (!$enterprise_stmt->execute()) {
                throw new Exception("Error inserting enterprise: " . $enterprise_stmt->error);
            }
            
            $enterprise_stmt->close();
            
        } elseif ($role == 'job-seeker') {
            // Handle job seeker registration
            $full_name = sanitize_input($conn, $_POST['nom']);
            
            // Handle worker_type_j array
            if (isset($_POST['worker_type_j']) && is_array($_POST['worker_type_j'])) {
                // Sanitize each value in the array
                $sanitized_professions = array_map(function($type) use ($conn) {
                    return sanitize_input($conn, $type);
                }, $_POST['worker_type_j']);
                
                $profession = implode(',', $sanitized_professions);
            } else {
                $profession = '';
            }
            
            $other_profession = isset($_POST['other_worker_j']) ? sanitize_input($conn, $_POST['other_worker_j']) : '';
            $education = sanitize_input($conn, $_POST['education']);
            $skills = sanitize_input($conn, $_POST['skills']);
            $phone = sanitize_input($conn, $_POST['tel_j']);
            
            // Handle file upload if CV/portfolio was submitted
            $portfolio = '';
            if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
                $upload_dir = 'uploads/portfolios/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['cv']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['cv']['tmp_name'], $target_file)) {
                    $portfolio = $target_file;
                }
            }
            
            $jobseeker_sql = "INSERT INTO jobseekers (user_id, full_name, profession, education, skills, 
                              portfolio, phone, other_profession) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $jobseeker_stmt = $conn->prepare($jobseeker_sql);
            $jobseeker_stmt->bind_param("isssssss", $user_id, $full_name, $profession, $education, 
                                    $skills, $portfolio, $phone, $other_profession);
            
            if (!$jobseeker_stmt->execute()) {
                throw new Exception("Error inserting job seeker: " . $jobseeker_stmt->error);
            }
            
            $jobseeker_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['registration_success'] = true;
        
        // Log in the user
        $_SESSION["loggedin"] = true;
        $_SESSION["user_id"] = $user_id;
        $_SESSION["email"] = $email;
        $_SESSION["role"] = $role;
        
        // Redirect to appropriate dashboard
        if ($role == 'job-seeker') {
            header("location: dashboard-job-seeker.php");
        } else {
            header("location: dashboard-enterprise.php");
        }
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['registration_error'] = "Registration failed: " . $e->getMessage();
        header("location: index.html");
        exit;
    }
    
    // Close connection
    $conn->close();
}
else {
    // If not a POST request, redirect to the main page
    header("location: index.html");
    exit;
}
?> 