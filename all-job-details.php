<?php
// This is a stub file that redirects job-details links to the new view-job-public.php page
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