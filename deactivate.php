<?php
// Start session to access session variables and manage user state
session_start();

// Check if user is logged in, redirect to login page if not authenticated
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit;
}

// FIXED: Include database connection
include_once "../includes/db_connect.php";

// Get the course ID from URL parameter and convert to integer for security and validation
$id = (int)$_GET['id'];

// SQL query to deactivate course by setting IsActive to 0 (false)
$query = "UPDATE course SET IsActive = 0 WHERE CourseID = ?";

// Prepare the SQL statement to prevent SQL injection attacks
$stmt = $pdo->prepare($query);

// Execute the prepared statement with the course ID as parameter
$stmt->execute([$id]);

// Set success message in session to display to user after redirect
$_SESSION['message'] = "Course deactivated successfully";
$_SESSION['message_type'] = 'success';

// Redirect back to course list page
header("Location: list.php");
exit;
?>