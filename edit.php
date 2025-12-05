<?php
// Start session to manage user authentication state and access session variables
session_start();

// Check if user is logged in, redirect to login page if not authenticated
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit; // Stop script execution to prevent unauthorized access
}

// Include database connection and course model for data operations
include_once "../includes/db_connect.php";
include_once "../includes/course_model.php";

// Get course ID from URL parameter and convert to integer for security
$id = (int)$_GET['id'];

// Retrieve course data from database using the course model
$course = getCourseById($id);

// Check if course exists, if not redirect with error message
if (!$course) {
    $_SESSION['message'] = "Course not found!";
    $_SESSION['message_type'] = 'danger'; // Red alert for error
    header("Location: list.php");
    exit;
}

// Get list of staff members for the instructor dropdown
$staff = getStaff();
// Initialize errors array to store validation messages
$errors = [];

// Process form data when form is submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and prepare form data for processing
    $data = [
        'course_name' => trim($_POST['course_name']),      // Remove whitespace from course name
        'course_code' => trim($_POST['course_code']),      // Remove whitespace from course code
        'description' => trim($_POST['description']),      // Remove whitespace from description
        'credits' => (int)$_POST['credits'],               // Convert credits to integer
        'fee' => (float)$_POST['fee'],                     // Convert fee to float for decimal values
        'start_date' => $_POST['start_date'],              // Store start date as string
        'staff_id' => $_POST['staff_id'] ?: null           // Set staff_id to null if empty
    ];
    
    // Validate the course data using validation function from model
    $errors = validateCourse($data);
    
    // If no validation errors, proceed to update course
    if (empty($errors)) {
        $result = updateCourse($id, $data); // Attempt to update course in database
        
        if ($result['success']) {
            // Set success message in session for display after redirect
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = 'success'; // Green success alert
            
            // Redirect to course list page
            header("Location: list.php");
            exit; // Ensure no further code execution after redirect
        } else {
            // Add database error to errors array for display
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <!-- Include Bootstrap CSS for responsive styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Course Management</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="list.php">View Courses</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <!-- Card Header with Course Name -->
                    <div class="card-header bg-warning">
                        <h4 class="mb-0">Edit Course: <?php echo htmlspecialchars($course['CourseName']); ?></h4>
                    </div>
                    <div class="card-body">
                        <!-- Display validation errors if any exist -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <!-- Escape output to prevent XSS attacks -->
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Course Edit Form -->
                        <form method="POST">
                            <!-- First Row: Course Name and Code -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Course Name *</label>
                                    <input type="text" name="course_name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($course['CourseName']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Course Code *</label>
                                    <input type="text" name="course_code" class="form-control" required 
                                           value="<?php echo htmlspecialchars($course['CourseCode']); ?>">
                                </div>
                            </div>
                            
                            <!-- Second Row: Credits, Fee, and Start Date -->
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Credits *</label>
                                    <input type="number" name="credits" class="form-control" min="1" max="10" required 
                                           value="<?php echo $course['Credits']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fee ($) *</label>
                                    <input type="number" name="fee" class="form-control" min="0" step="0.01" required 
                                           value="<?php echo $course['Fee']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Start Date *</label>
                                    <input type="date" name="start_date" class="form-control" required 
                                           value="<?php echo $course['StartDate']; ?>">
                                </div>
                            </div>

                            <!-- Instructor Selection Dropdown -->
                            <div class="mb-3">
                                <label class="form-label">Instructor</label>
                                <select name="staff_id" class="form-control">
                                    <option value="">Select Instructor</option>
                                    <?php if (!empty($staff)): ?>
                                        <!-- Populate dropdown with staff members -->
                                        <?php foreach ($staff as $s): ?>
                                            <option value="<?php echo $s['StaffID']; ?>" 
                                                <?php echo ($course['StaffID'] == $s['StaffID']) ? 'selected' : ''; ?>>
                                                <!-- Display staff member's full name -->
                                                <?php echo htmlspecialchars($s['FirstName'] . ' ' . $s['LastName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No staff available</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <!-- Course Description Textarea -->
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($course['Description']); ?></textarea>
                            </div>
                            
                            <!-- Form Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Course</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>