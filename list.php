<?php
session_start();
include_once "../includes/auth.php";
include_once "../includes/db_connect.php";
include_once "../includes/security.php";
require_login();

try {
    // Use prepared statements for security
    $original_stmt = $pdo->prepare("SELECT StudentID, FirstName, LastName, Email FROM student WHERE IsActive = 1");
    $original_stmt->execute();
    $original_students = $original_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $registered_stmt = $pdo->prepare("SELECT id, student_name, email, course_name, status FROM student_registrations WHERE status = 'approved'");
    $registered_stmt->execute();
    $registered_students = $registered_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $all_students = [];
    
    // Add original students with validation
    foreach($original_students as $student) {
        $all_students[] = [
            'id' => (int)$student['StudentID'],
            'name' => sanitize_input($student['FirstName'] . ' ' . $student['LastName']),
            'email' => sanitize_input($student['Email']),
            'course' => 'Not specified',
            'type' => 'original',
            'status' => 'Active'
        ];
    }
    
    // Add registered students with validation
    foreach($registered_students as $student) {
        $all_students[] = [
            'id' => (int)$student['id'],
            'name' => sanitize_input($student['student_name']),
            'email' => sanitize_input($student['email']),
            'course' => sanitize_input($student['course_name']),
            'type' => 'registered',
            'status' => ucfirst(sanitize_input($student['status']))
        ];
    }
    
} catch (Exception $e) {
    error_log("Students list error: " . $e->getMessage());
    $all_students = [];
    $error = "Unable to load students. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students - Course Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .student-count {
            font-size: 1.1rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap"></i> Course Management
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-users me-2"></i>Student Management</h2>
                <p class="text-muted mb-0">Manage all student records and registrations</p>
            </div>
            <div class="text-end">
                <div class="student-count badge bg-primary fs-6 mb-2">
                    <?php echo count($all_students); ?> Total Students
                </div>
                <div>
                    <a href="register.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Register New Student
                    </a>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-container p-4">
            <?php if (empty($all_students)): ?>
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h5>No Students Found</h5>
                    <p class="mb-3">There are no students registered in the system yet.</p>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register First Student
                    </a>
                </div>
            <?php else: ?>
                <!-- Student Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Original Students</h6>
                                <h4><?php echo count(array_filter($all_students, fn($s) => $s['type'] === 'original')); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Registered Students</h6>
                                <h4><?php echo count(array_filter($all_students, fn($s) => $s['type'] === 'registered')); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Active Status</h6>
                                <h4><?php echo count(array_filter($all_students, fn($s) => $s['status'] === 'Active')); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white text-center">
                            <div class="card-body py-3">
                                <h6 class="card-title">Approved Status</h6>
                                <h4><?php echo count(array_filter($all_students, fn($s) => $s['status'] === 'Approved')); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_students as $student): ?>
                            <tr>
                                <td><strong>#<?php echo $student['id']; ?></strong></td>
                                <td><?php echo $student['name']; ?></td>
                                <td>
                                    <?php if (!empty($student['email'])): ?>
                                        <a href="mailto:<?php echo $student['email']; ?>">
                                            <?php echo $student['email']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $student['course']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($student['status']) {
                                            'Active' => 'success',
                                            'Approved' => 'primary',
                                            default => 'secondary'
                                        }; 
                                    ?>">
                                        <i class="fas fa-<?php 
                                            echo match($student['status']) {
                                                'Active' => 'check-circle',
                                                'Approved' => 'user-check',
                                                default => 'clock'
                                            }; 
                                        ?> me-1"></i>
                                        <?php echo $student['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $student['type'] === 'original' ? 'info' : 'warning'; ?>">
                                        <i class="fas fa-<?php echo $student['type'] === 'original' ? 'database' : 'user-plus'; ?> me-1"></i>
                                        <?php echo $student['type'] === 'original' ? 'Original' : 'New Registered'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                onclick="viewStudent(<?php echo $student['id']; ?>, '<?php echo $student['type']; ?>')"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($student['type'] === 'registered'): ?>
                                            <button class="btn btn-outline-warning" 
                                                    onclick="editStudent(<?php echo $student['id']; ?>)"
                                                    title="Edit Student">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="mt-3 text-muted">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <?php echo count($all_students); ?> student(s) • 
                        <?php echo count(array_filter($all_students, fn($s) => $s['type'] === 'original')); ?> original • 
                        <?php echo count(array_filter($all_students, fn($s) => $s['type'] === 'registered')); ?> registered
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewStudent(id, type) {
            alert(`Viewing student #${id} (${type} type)\n\nThis would show detailed student information in a complete implementation.`);
        }

        function editStudent(id) {
            alert(`Editing registered student #${id}\n\nThis would open an edit form for the student in a complete implementation.`);
        }

        // Search functionality (basic implementation)
        function searchStudents() {
            const searchTerm = prompt('Enter student name or email to search:');
            if (searchTerm) {
                alert(`Searching for: "${searchTerm}"\n\nThis would filter the student list in a complete implementation.`);
            }
        }
    </script>
</body>
</html>