<?php
session_start();
include_once "../includes/auth.php";
include_once "../includes/db_connect.php"; 
include_once "../includes/security.php";
include_once "../includes/validation.php";
require_login();

// Generate CSRF token
$csrf_token = generate_csrf_token();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "error|Invalid form submission";
        log_security_event("CSRF token validation failed");
    }
    // Check rate limiting
    elseif (!check_rate_limit('student_registration', 3, 60)) {
        $message = "error|Too many registration attempts. Please wait 1 minute.";
    }
    // Proceed with validation
    else {
        try {
            // Sanitize and validate inputs
            $student_name = sanitize_input(trim($_POST['student_name'] ?? ''));
            $course_name = sanitize_input(trim($_POST['course_name'] ?? ''));
            $email = !empty($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
            $phone = sanitize_input(trim($_POST['phone'] ?? ''));
            
            // Server-side validation
            $validation_errors = validate_student_registration([
                'student_name' => $student_name,
                'course_name' => $course_name, 
                'email' => $email,
                'phone' => $phone
            ]);
            
            if (!empty($validation_errors)) {
                $message = "error|" . implode("<br>", $validation_errors);
            } else {
                // Check if table exists
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'student_registrations'")->fetch();
                if (!$tableCheck) {
                    $message = "error|System configuration error. Please contact administrator.";
                    log_security_event("Missing student_registrations table");
                } else {
                    // Insert with prepared statement
                    $stmt = $pdo->prepare("INSERT INTO student_registrations (student_name, course_name, email, phone, status) VALUES (?, ?, ?, ?, 'approved')");
                    $result = $stmt->execute([$student_name, $course_name, $email, $phone]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        $message = "success|Student registered successfully!";
                        log_security_event("Student registered: $student_name");
                    } else {
                        $message = "error|Failed to register student. Please try again.";
                    }
                }
            }
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $message = "error|Registration failed due to system error. Please try again.";
            log_security_event("Database error during student registration");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Student - Course Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; border-radius: 15px 15px 0 0 !important; 
        }
        .form-label { font-weight: 500; }
        .required::after { content: " *"; color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Course Management</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Dashboard</a>
                <span class="navbar-text me-3"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-user-plus"></i> Register New Student</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <?php 
                            $msgParts = explode('|', $message, 2);
                            $msgType = $msgParts[0] ?? '';
                            $msgText = $msgParts[1] ?? '';
                            ?>
                            <div class="alert alert-<?php echo $msgType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                                <strong><?php echo $msgType === 'success' ? 'Success!' : 'Error!'; ?></strong> 
                                <?php echo $msgText; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="registrationForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Student Name</label>
                                    <input type="text" class="form-control" name="student_name" required 
                                           maxlength="100" pattern="[a-zA-Z\s\.\-]{2,100}"
                                           title="2-100 letters, spaces, dots, or hyphens"
                                           value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>">
                                    <div class="form-text">Full name (2-100 characters, letters and spaces only)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Course Name</label>
                                    <input type="text" class="form-control" name="course_name" required 
                                           maxlength="100"
                                           value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>">
                                    <div class="form-text">Name of the course student is registering for</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           placeholder="student@example.com"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    <div class="form-text">Valid email address (optional)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           placeholder="+1 234 567 8900"
                                           pattern="\+?[\d\s\-\(\)]{10,}"
                                           title="International phone number format"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    <div class="form-text">International format: +1 234 567 8900 (optional)</div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="list.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-list"></i> View All Students
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Register Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const nameInput = document.querySelector('[name="student_name"]');
            const phoneInput = document.querySelector('[name="phone"]');
            const emailInput = document.querySelector('[name="email"]');
            
            // Name validation
            if (!/^[a-zA-Z\s\.\-]{2,100}$/.test(nameInput.value.trim())) {
                e.preventDefault();
                alert('Student name can only contain letters, spaces, dots, and hyphens (2-100 characters)');
                nameInput.focus();
                return false;
            }
            
            // Phone validation (if provided)
            if (phoneInput.value.trim() && !/^\+?[\d\s\-\(\)]{10,}$/.test(phoneInput.value.trim())) {
                e.preventDefault();
                alert('Please enter a valid phone number in international format');
                phoneInput.focus();
                return false;
            }
            
            // Email validation (if provided)
            if (emailInput.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
                e.preventDefault();
                alert('Please enter a valid email address');
                emailInput.focus();
                return false;
            }
        });
    </script>
</body>
</html>