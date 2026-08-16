<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include session checker
require_once "session.php";
require_once "db_connect.php";

// Check if user is logged in
check_login();

// Check if the required tables exist
function table_exists($conn, $table_name) {
    $result = $conn->query("SHOW TABLES LIKE '{$table_name}'");
    return $result->num_rows > 0;
}

// Create the subscriptions table if it doesn't exist
if (!table_exists($conn, 'subscriptions')) {
    $conn->query("CREATE TABLE subscriptions (
        subscription_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_id VARCHAR(20) NOT NULL,
        plan_name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        status ENUM('active', 'canceled', 'expired') NOT NULL DEFAULT 'active',
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Create the payments table if it doesn't exist
if (!table_exists($conn, 'payments')) {
    $conn->query("CREATE TABLE payments (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subscription_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        status VARCHAR(50) NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Initialize variables
$error_message = '';
$success_message = '';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $plan_id = isset($_POST['plan_id']) ? $_POST['plan_id'] : '';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    
    // Validate inputs
    if (empty($plan_id) || empty($payment_method)) {
        // Redirect based on user role
        if ($_SESSION["role"] === "job-seeker") {
            $_SESSION['payment_error'] = "All fields are required.";
            header("location: dashboard-job-seeker.php#upgradeModal");
        } else if ($_SESSION["role"] === "enterprise") {
            $_SESSION['payment_error'] = "All fields are required.";
            header("location: dashboard-enterprise.php#upgradeModal");
        } else {
            header("location: index.html");
        }
        exit;
    }
    
    // Set plan details based on plan_id and user role
    $plan_details = array();
    
    if ($_SESSION["role"] === "job-seeker") {
        $plan_details = array(
            'starter' => array(
                'name' => 'Starter',
                'price' => 9.99,
                'duration' => 30 // days
            ),
            'pro' => array(
                'name' => 'Pro',
                'price' => 19.99,
                'duration' => 30 // days
            )
        );
    } else if ($_SESSION["role"] === "enterprise") {
        $plan_details = array(
            'starter' => array(
                'name' => 'Starter',
                'price' => 29.99,
                'duration' => 30 // days
            ),
            'business' => array(
                'name' => 'Business',
                'price' => 59.99,
                'duration' => 30 // days
            ),
            'enterprise' => array(
                'name' => 'Enterprise',
                'price' => 99.99,
                'duration' => 30 // days
            )
        );
    }
    
    // Check if plan exists
    if (!array_key_exists($plan_id, $plan_details)) {
        // Redirect based on user role
        if ($_SESSION["role"] === "job-seeker") {
            $_SESSION['payment_error'] = "Invalid plan selected.";
            header("location: dashboard-job-seeker.php#upgradeModal");
        } else if ($_SESSION["role"] === "enterprise") {
            $_SESSION['payment_error'] = "Invalid plan selected.";
            header("location: dashboard-enterprise.php#upgradeModal");
        } else {
            header("location: index.html");
        }
        exit;
    }
    
    // Get plan information
    $plan_name = $plan_details[$plan_id]['name'];
    $price = $plan_details[$plan_id]['price'];
    $duration = $plan_details[$plan_id]['duration'];
    $start_date = date('Y-m-d H:i:s');
    $end_date = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, check for existing active subscriptions and mark them as cancelled
        $cancel_sql = "UPDATE subscriptions SET status = 'canceled' WHERE user_id = ? AND status = 'active'";
        $stmt = $conn->prepare($cancel_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $stmt->close();
        
        // Insert new subscription
        $sub_sql = "INSERT INTO subscriptions (user_id, plan_id, plan_name, price, status, start_date, end_date) 
                    VALUES (?, ?, ?, ?, 'active', ?, ?)";
        $stmt = $conn->prepare($sub_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("issdss", $_SESSION["user_id"], $plan_id, $plan_name, $price, $start_date, $end_date);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating subscription: " . $stmt->error);
        }
        
        // Get the new subscription ID
        $subscription_id = $conn->insert_id;
        $stmt->close();
        
        // Insert payment record
        $payment_sql = "INSERT INTO payments (user_id, subscription_id, amount, payment_method, status) 
                        VALUES (?, ?, ?, ?, 'successful')";
        $stmt = $conn->prepare($payment_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iids", $_SESSION["user_id"], $subscription_id, $price, $payment_method);
        
        if (!$stmt->execute()) {
            throw new Exception("Error recording payment: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Set success message and redirect based on user role
        $_SESSION['payment_success'] = "Payment processed successfully! Your {$plan_name} subscription is now active until " . date('F j, Y', strtotime($end_date));
        
        if ($_SESSION["role"] === "job-seeker") {
            header("location: dashboard-job-seeker.php");
        } else if ($_SESSION["role"] === "enterprise") {
            header("location: dashboard-enterprise.php");
        } else {
            header("location: index.html");
        }
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log the error (in a real app, store this in a proper log file)
        error_log("Payment processing error: " . $e->getMessage());
        
        $_SESSION['payment_error'] = "Payment processing failed. Please try again later or contact support.";
        
        // Redirect based on user role
        if ($_SESSION["role"] === "job-seeker") {
            header("location: dashboard-job-seeker.php#upgradeModal");
        } else if ($_SESSION["role"] === "enterprise") {
            header("location: dashboard-enterprise.php#upgradeModal");
        } else {
            header("location: index.html");
        }
        exit;
    }
}

// If not a POST request, redirect based on user role
if ($_SESSION["role"] === "job-seeker") {
    header("location: dashboard-job-seeker.php");
} else if ($_SESSION["role"] === "enterprise") {
    header("location: dashboard-enterprise.php");
} else {
    header("location: index.html");
}
exit;
?> 