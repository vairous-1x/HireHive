<?php
// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Check if maintenance mode is enabled
$maintenance_mode = false; // Default to false if setting doesn't exist

// Check if site_settings table exists and get the setting
$check_table_sql = "SHOW TABLES LIKE 'site_settings'";
$result = $conn->query($check_table_sql);
if ($result->num_rows > 0) {
    // Table exists, check the setting
    $sql = "SELECT setting_value FROM site_settings WHERE setting_name = 'maintenance_mode'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $maintenance_mode = (bool)$row['setting_value'];
    }
}

// Get site name from settings
$site_name = 'HireHive';
$sql = "SELECT setting_value FROM site_settings WHERE setting_name = 'site_name'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $site_name = $row['setting_value'];
}

// If maintenance mode is disabled or user is an admin, redirect to homepage
if (!$maintenance_mode || (isset($_SESSION["loggedin"]) && $_SESSION["role"] === "admin")) {
    header("location: index.html");
    exit;
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo htmlspecialchars($site_name); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .maintenance-container {
            max-width: 600px;
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .icon-container {
            font-size: 80px;
            color: #f9a825;
            margin-bottom: 30px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        p {
            color: #666;
            line-height: 1.7;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="maintenance-container">
            <div class="icon-container">
                <i class="fas fa-tools"></i>
            </div>
            <h1>We're Under Maintenance</h1>
            <p>We apologize for the inconvenience. <?php echo htmlspecialchars($site_name); ?> is currently undergoing scheduled maintenance to improve our services. We'll be back online shortly!</p>
            <p class="mb-0">Thank you for your patience.</p>
            
            <hr class="my-4">
            
            <p class="small text-muted mb-0">If you're an administrator, please <a href="login.php">log in</a> to access the site.</p>
        </div>
    </div>
</body>
</html> 