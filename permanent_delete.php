<?php
// Start session to manage user authentication state and access session variables
session_start();

// Check if user is logged in, redirect to login page if not authenticated
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit; // Stop script execution to prevent unauthorized access
}

// Include the course model file for database operations
include_once "../includes/course_model.php";

// Get the course ID from URL parameter and convert to integer for security
$id = (int)$_GET['id'];

// SQL query to permanently delete course from the database
// WARNING: This is a hard delete - the record will be completely removed
$query = "DELETE FROM Course WHERE CourseID = ?";

// Prepare the SQL statement to prevent SQL injection attacks
$stmt = $pdo->prepare($query);

// Execute the prepared statement with the course ID as parameter
$stmt->execute([$id]);

// Set success message in session to display to user after redirect
$_SESSION['message'] = "Course permanently deleted";
$_SESSION['message_type'] = 'success'; // Green success alert

// Redirect to database management page to show the result
header("Location: database_management.php");
exit; // Ensure no further code is executed after redirect
?>