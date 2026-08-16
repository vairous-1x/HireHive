<?php
// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Initialize the error message variable
$error_message = "";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and validate form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Prepare a select statement
        $sql = "SELECT user_id, email, password, role FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $email);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if email exists
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($user_id, $email, $hashed_password, $role);
                    
                    if ($stmt->fetch()) {
                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;
                            
                            // Redirect based on role
                            if ($role == "job-seeker") {
                                header("location: dashboard-job-seeker.php");
                            } elseif ($role == "enterprise") {
                                header("location: dashboard-enterprise.php");
                            } elseif ($role == "admin") {
                                header("location: dashboard-admin.php");
                            } else {
                                header("location: index.html");
                            }
                            exit;
                        } else {
                            // Password is incorrect
                            $error_message = "Invalid email or password";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $error_message = "Invalid email or password";
                }
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
    
    // Store error message in session if there is one
    if (!empty($error_message)) {
        $_SESSION['login_error'] = $error_message;
        header("location: index.html");
        exit;
    }
}

// If not a POST request, redirect to login page
header("location: index.html");
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HireHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center">Login to HireHive</h2>
                    </div>
                    <div class="card-body">
                        <?php 
                        if(!empty($error_message)){
                            echo '<div class="alert alert-danger">' . $error_message . '</div>';
                        }        
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>    
                            <div class="form-group mb-3">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group mb-3 text-center">
                                <input type="submit" class="btn btn-primary" value="Login">
                            </div>
                            <p class="text-center">Don't have an account? <a href="index.html">Sign up now</a>.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>    
</body>
</html> 