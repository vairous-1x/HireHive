<?php
// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in and is an admin
check_role("admin");

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate email (check if it already exists)
    $email_check_sql = "SELECT user_id FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($email_check_sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // Email already exists
            $error_message = "This email is already registered.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert into users table
                $sql = "INSERT INTO users (email, password, role) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $email, $hashed_password, $role);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting user: " . $stmt->error);
                }
                
                $user_id = $conn->insert_id;
                $stmt->close();
                
                // If role is enterprise or job-seeker, create additional info
                if ($role == 'enterprise') {
                    $sql = "INSERT INTO enterprises (user_id, company_name, company_size, worker_types, product_service_description, phone) 
                            VALUES (?, 'New Enterprise', '1-10', 'other', 'No description provided', 'Not provided')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error creating enterprise record: " . $stmt->error);
                    }
                } elseif ($role == 'job-seeker') {
                    $sql = "INSERT INTO jobseekers (user_id, full_name, profession, education, skills, phone) 
                            VALUES (?, 'New User', 'other', 'Not provided', 'Not provided', 'Not provided')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error creating job seeker record: " . $stmt->error);
                    }
                }
                
                // Commit transaction
                $conn->commit();
                $success_message = "User added successfully!";
                
                // Redirect to edit the new user
                header("location: edit-user.php?id=$user_id");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Error adding user: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
}

// Close connection
$conn->close();

// Redirect back to admin dashboard
header("location: dashboard-admin.php" . (!empty($error_message) ? "?error=" . urlencode($error_message) : (!empty($success_message) ? "?success=" . urlencode($success_message) : "")));
exit;
?> 