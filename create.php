<?php
// Start session to check if user is logged in
session_start();
if (!isset($_SESSION['logged_in'])) {
    // Redirect to login page if user is not authenticated
    header("Location: ../login.php");
    exit; // Stop script execution after redirect
}

// Include database connection and security functions
include_once "../includes/db_connect.php";
include_once "../includes/security.php";

// Initialize error messages array and success message variable
$errors = [];
$success_message = '';

// Get active staff members for instructor dropdown
$staff = [];
try {
    // Query database for active staff members
    $stmt = $pdo->query("SELECT StaffID, FirstName, LastName FROM staff WHERE IsActive = 1 ORDER BY FirstName");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If staff table doesn't exist, continue without staff dropdown
    $staff = [];
}

// Check if form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data using security function
    $course_name = sanitize_input(trim($_POST['course_name'] ?? ''));
    $course_code = sanitize_input(trim($_POST['course_code'] ?? ''));
    $description = sanitize_input(trim($_POST['description'] ?? ''));
    $credits = (int)($_POST['credits'] ?? 0); // Convert to integer
    $fee = (float)($_POST['fee'] ?? 0); // Convert to float
    $start_date = $_POST['start_date'] ?? '';
    $staff_id = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null; // Convert to integer if provided

    // COMPREHENSIVE VALIDATION - Check each field for errors
    
    // Validate course name (required, length between 3-100 characters)
    if (empty($course_name)) {
        $errors[] = "Course name is required";
    } elseif (strlen($course_name) < 3 || strlen($course_name) > 100) {
        $errors[] = "Course name must be between 3 and 100 characters";
    }
    
    // Validate course code (required, alphanumeric, 2-20 characters)
    if (empty($course_code)) {
        $errors[] = "Course code is required";
    } elseif (!preg_match('/^[A-Za-z0-9]{2,20}$/', $course_code)) {
        $errors[] = "Course code must be 2-20 letters and numbers only (no spaces or special characters)";
    }
    
    // Validate credits (must be between 1-10)
    if ($credits < 1 || $credits > 10) {
        $errors[] = "Credits must be between 1 and 10";
    }
    
    // Validate fee (must be positive and reasonable amount)
    if ($fee <= 0) {
        $errors[] = "Fee must be greater than 0";
    } elseif ($fee > 10000) {
        $errors[] = "Fee cannot exceed $10,000";
    }
    
    // Validate start date (required and cannot be in the past)
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    } else {
        $min_date = date('Y-m-d'); // Today's date
        if ($start_date < $min_date) {
            $errors[] = "Start date cannot be in the past";
        }
    }

    // If no validation errors, proceed to create course in database
    if (empty($errors)) {
        try {
            // Check if course code already exists in database
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM course WHERE CourseCode = ?");
            $check_stmt->execute([$course_code]);
            $code_exists = $check_stmt->fetch()['count'];

            if ($code_exists > 0) {
                $errors[] = "Course code '$course_code' already exists. Please use a different code.";
            } else {
                // Insert new course record into database
                $insert_stmt = $pdo->prepare("
                    INSERT INTO course 
                    (CourseName, CourseCode, Description, Credits, Fee, StartDate, StaffID) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                // Execute prepared statement with sanitized data
                $result = $insert_stmt->execute([
                    $course_name,
                    $course_code,
                    $description,
                    $credits,
                    $fee,
                    $start_date,
                    $staff_id
                ]);

                if ($result) {
                    // Get the auto-generated course ID
                    $course_id = $pdo->lastInsertId();
                    // Create success message with course details
                    $success_message = "✅ Course created successfully!<br><strong>Course:</strong> $course_name<br><strong>Code:</strong> $course_code<br><strong>ID:</strong> $course_id<br><strong>Fee:</strong> $" . number_format($fee, 2);
                    // Clear form data for potential new entry
                    $_POST = [];
                } else {
                    $errors[] = "Failed to create course. Please try again.";
                }
            }
        } catch (PDOException $e) {
            // Handle database errors
            $error_message = $e->getMessage();
            
            // Check for duplicate entry error specifically
            if (strpos($error_message, 'Duplicate entry') !== false) {
                $errors[] = "Course code '$course_code' already exists. Please use a different code.";
            } else {
                $errors[] = "Database error: " . $error_message;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course - Course Management</title>
    <!-- Include Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom CSS styles for enhanced appearance */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
            text-align: center;
        }
        
        .form-body {
            padding: 40px;
        }
        
        /* Additional CSS styles remain the same... */
    </style>
</head>
<body>
    <!-- Navigation bar -->
    <nav class="navbar">
        <div class="container">
            <!-- Back to courses list link -->
            <a href="list.php" class="text-white text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i> Back to Courses
            </a>
            <!-- Display current username -->
            <div class="navbar-text text-white">
                <i class="fas fa-user-circle me-2"></i><?php echo $_SESSION['username']; ?>
            </div>
        </div>
    </nav>

    <!-- Main content container -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container">
                    <!-- Header section -->
                    <div class="form-header">
                        <i class="fas fa-plus-circle fa-3x mb-3"></i>
                        <h2 class="mb-2">Create New Course</h2>
                        <p class="mb-0">Fill in the course details below</p>
                    </div>
                    
                    <!-- Form body section -->
                    <div class="form-body">
                        <!-- Success Message Display -->
                        <?php if ($success_message): ?>
                            <div class="alert alert-success success-alert">
                                <div class="d-flex">
                                    <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                                    <div>
                                        <h5 class="alert-heading">Success!</h5>
                                        <?php echo $success_message; ?>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <!-- Action buttons after successful creation -->
                                    <a href="list.php" class="btn btn-sm btn-outline-success me-2">
                                        <i class="fas fa-list"></i> View All Courses
                                    </a>
                                    <a href="create.php" class="btn btn-sm btn-success">
                                        <i class="fas fa-plus"></i> Add Another Course
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Error Messages Display -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Please fix the following errors:
                                </h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Course Creation Form -->
                        <form method="POST" id="courseForm" novalidate>
                            <!-- Course Name & Code Row -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label required">
                                        <i class="fas fa-book text-primary me-2"></i>Course Name
                                    </label>
                                    <input type="text" 
                                           name="course_name" 
                                           class="form-control" 
                                           required 
                                           minlength="3"
                                           maxlength="100"
                                           placeholder="e.g., Web Development Fundamentals"
                                           value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>">
                                    <div class="validation-feedback text-muted">
                                        Must be 3-100 characters
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">
                                        <i class="fas fa-code text-primary me-2"></i>Course Code
                                    </label>
                                    <input type="text" 
                                           name="course_code" 
                                           class="form-control" 
                                           required 
                                           pattern="[A-Za-z0-9]{2,20}"
                                           placeholder="e.g., WEB101"
                                           value="<?php echo htmlspecialchars($_POST['course_code'] ?? ''); ?>"
                                           id="courseCodeInput">
                                    <div class="validation-feedback text-muted">
                                        2-20 letters/numbers only, no spaces
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Credits & Fee Row -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label required">
                                        <i class="fas fa-star text-warning me-2"></i>Credits
                                    </label>
                                    <select name="credits" class="form-control" required>
                                        <option value="">Select Credits</option>
                                        <!-- Generate credit options from 1 to 10 -->
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>" 
                                                <?php echo ($_POST['credits'] ?? '') == $i ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> Credit<?php echo $i > 1 ? 's' : ''; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="validation-feedback text-muted">
                                        Select credit hours (1-10)
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">
                                        <i class="fas fa-dollar-sign text-success me-2"></i>Course Fee
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" 
                                               name="fee" 
                                               class="form-control" 
                                               required 
                                               min="0.01" 
                                               max="10000"
                                               step="0.01"
                                               placeholder="0.00"
                                               value="<?php echo isset($_POST['fee']) ? $_POST['fee'] : ''; ?>">
                                    </div>
                                    <div class="validation-feedback text-muted">
                                        Must be between $0.01 and $10,000
                                    </div>
                                </div>
                            </div>

                            <!-- Start Date & Instructor Row -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label required">
                                        <i class="fas fa-calendar-alt text-info me-2"></i>Start Date
                                    </label>
                                    <input type="date" 
                                           name="start_date" 
                                           class="form-control" 
                                           required 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo $_POST['start_date'] ?? ''; ?>">
                                    <div class="validation-feedback text-muted">
                                        Cannot be in the past
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-chalkboard-teacher text-secondary me-2"></i>Instructor
                                    </label>
                                    <select name="staff_id" class="form-control">
                                        <option value="">Select Instructor (Optional)</option>
                                        <!-- Populate staff dropdown from database -->
                                        <?php foreach ($staff as $s): ?>
                                            <option value="<?php echo $s['StaffID']; ?>"
                                                <?php echo ($_POST['staff_id'] ?? '') == $s['StaffID'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s['FirstName'] . ' ' . $s['LastName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="validation-feedback text-muted">
                                        Optional - assign course instructor
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Course Description -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-file-alt text-secondary me-2"></i>Course Description
                                </label>
                                <textarea name="description" 
                                          class="form-control" 
                                          rows="4" 
                                          maxlength="500"
                                          placeholder="Describe the course content, objectives, and learning outcomes..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <div class="validation-feedback text-muted">
                                    Optional - maximum 500 characters
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <!-- Cancel button to go back to list -->
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                <div>
                                    <!-- Reset form button -->
                                    <button type="reset" class="btn btn-outline-secondary me-2" id="resetBtn">
                                        <i class="fas fa-redo me-2"></i>Reset Form
                                    </button>
                                    <!-- Submit form button -->
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Course
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript for enhanced form validation and user experience
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('courseForm');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            // Add real-time validation on field blur
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                // Re-validate on input if field was previously invalid
                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        validateField(this);
                    }
                });
            });
            
            // Custom reset handler with enhanced functionality
            document.getElementById('resetBtn').addEventListener('click', function(e) {
                e.preventDefault();
                form.reset();
                
                // Clear all validation states
                inputs.forEach(input => {
                    input.classList.remove('is-valid', 'is-invalid');
                });
                
                // Set default date to 7 days from now for better UX
                const dateInput = document.querySelector('[name="start_date"]');
                const nextWeek = new Date();
                nextWeek.setDate(nextWeek.getDate() + 7);
                dateInput.value = nextWeek.toISOString().split('T')[0];
                
                // Focus on first field for convenience
                document.querySelector('[name="course_name"]').focus();
            });
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate all fields before submission
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error for better UX
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                }
            });
            
            // Field validation function
            function validateField(field) {
                // Skip validation for optional fields that are empty
                if (!field.hasAttribute('required') && !field.value.trim()) {
                    field.classList.remove('is-valid', 'is-invalid');
                    return true;
                }
                
                // Check HTML5 validity
                if (field.checkValidity()) {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                    return true;
                } else {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                    return false;
                }
            }
            
            // Auto-focus first field when page loads
            document.querySelector('[name="course_name"]').focus();
        });
    </script>
</body>
</html>