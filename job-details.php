<?php
// This is a redirect file for job-details.php to view-job-public.php
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $job_id = $_GET['id'];
    header("Location: view-job-public.php?id=$job_id");
    exit;
} else {
    // If no job ID is provided, redirect to the main page
    header("Location: index.html");
    exit;
}
?> 