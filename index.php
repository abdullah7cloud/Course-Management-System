<?php
/**
 * INDEX.PHP - LANDING PAGE VERSION
 */

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .hero-section {
            color: white;
            text-align: center;
            padding: 100px 20px;
        }
        .btn-get-started {
            background: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-get-started:hover {
            background: white;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <h1 class="display-4 mb-4">Course Management System</h1>
            <p class="lead mb-4">Manage students, courses, staff, and enrollments efficiently</p>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <a href="dashboard.php" class="btn-get-started">Go to Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn-get-started">Login to Get Started</a>
            <?php endif; ?>
            
            <div class="mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>👨‍🏫 Staff</h5>
                                <p class="small">Manage faculty members</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>🎓 Students</h5>
                                <p class="small">Student records & profiles</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>📚 Courses</h5>
                                <p class="small">Course catalog management</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>📝 Enrollments</h5>
                                <p class="small">Student course registrations</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>