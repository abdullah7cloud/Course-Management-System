<?php
// Start the session to access session variables
session_start();

// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit;
}

// FIXED: Include database connection
include_once "../includes/db_connect.php";

// Get the course ID from URL parameter and convert to integer for security
$id = (int)$_GET['id'];

// SQL query to activate course by setting IsActive to 1
$query = "UPDATE course SET IsActive = 1 WHERE CourseID = ?";

// Prepare the SQL statement to prevent SQL injection
$stmt = $pdo->prepare($query);

// Execute the prepared statement with the course ID parameter
$stmt->execute([$id]);

// Set success message in session to display to user
$_SESSION['message'] = "Course activated successfully";
$_SESSION['message_type'] = 'success';

// Redirect back to course list page
header("Location: list.php");
exit;
?>