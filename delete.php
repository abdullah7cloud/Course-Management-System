<?php
// Start session to manage user authentication state and access session variables
session_start();

// Check if user is logged in, redirect to login page if not authenticated
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit; // Stop script execution to prevent further processing
}

// Include the course model file which contains the deleteCourse function
include_once "../includes/course_model.php";

// Get the course ID from URL parameter and convert to integer for security
$id = (int)$_GET['id'];

// Call the deleteCourse function from the course model to handle the deletion
$result = deleteCourse($id);

// Check if the deletion was successful and set appropriate user feedback messages
if ($result['success']) {
    // Set success message in session for display after redirect
    $_SESSION['message'] = $result['message'];
    $_SESSION['message_type'] = 'success'; // Green success alert
} else {
    // Set error message in session if deletion failed
    $_SESSION['message'] = $result['message'];
    $_SESSION['message_type'] = 'danger'; // Red error alert
}

// Redirect back to the course list page to show the result message
header("Location: list.php");
exit; // Ensure no further code execution after redirect
?>