<?php
session_start();

// Simple check - if not logged in, redirect to login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
        }
        
        .navbar {
            background-color: #343a40;
        }
        
        .main-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .function-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: block;
            border-top: 4px solid #007bff;
        }
        
        .function-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .welcome-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-graduation-cap me-2"></i>
                Course Management System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo $_SESSION['username']; ?>
                </span>
                <a class="nav-item nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-container my-5">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="h2 mb-2">Course Management System</h1>
            <p class="text-muted">Select a function to manage courses</p>
        </div>

        <!-- Course Management Functions -->
        <div class="row g-4">
            <!-- Add Course -->
            <div class="col-md-6">
                <a href="courses/create.php" class="function-card">
                    <div class="card-icon text-primary">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h4>Add New Course</h4>
                    <p class="text-muted mb-0">Create a new course in the catalog</p>
                </a>
            </div>

            <!-- View Courses -->
            <div class="col-md-6">
                <a href="courses/list.php" class="function-card">
                    <div class="card-icon text-success">
                        <i class="fas fa-list"></i>
                    </div>
                    <h4>View All Courses</h4>
                    <p class="text-muted mb-0">Browse and manage all courses</p>
                </a>
            </div>

            <!-- Search Courses -->
            <div class="col-md-6">
                <a href="courses/search.php" class="function-card">
                    <div class="card-icon text-info">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>Search Courses</h4>
                    <p class="text-muted mb-0">Find courses with advanced search</p>
                </a>
            </div>
            
            <!-- Filter Courses -->
            <div class="col-md-6">
                <a href="courses/filter_demo.php" class="function-card">
                    <div class="card-icon text-warning">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h4>Filter Courses</h4>
                    <p class="text-muted mb-0">Filter courses by various criteria</p>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>