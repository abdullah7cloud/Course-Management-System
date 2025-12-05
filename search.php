<?php
// Start session to manage user authentication state and access session variables
session_start();

// Check if user is logged in, redirect to login page if not authenticated
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit; // Stop script execution to prevent unauthorized access
}

// Include database connection file for database operations
include_once "../includes/db_connect.php";

// Initialize variables for search functionality
$search_results = []; // Array to store search results
$search_term = '';    // Variable to store the search query

// Process search form when submitted via GET method
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    $search_term = trim($_GET['search']); // Remove whitespace from search term
    
    // Only execute search if search term is not empty
    if (!empty($search_term)) {
        // SQL query to search courses by name, code, or description
        // Only active courses (IsActive = 1) are included in search results
        $query = "SELECT c.*, s.FirstName, s.LastName 
                  FROM Course c 
                  LEFT JOIN Staff s ON c.StaffID = s.StaffID 
                  WHERE (c.CourseName LIKE ? OR c.CourseCode LIKE ? OR c.Description LIKE ?)
                  AND c.IsActive = 1
                  ORDER BY c.CourseName"; // Order results alphabetically by course name
        
        // Prepare SQL statement to prevent SQL injection
        $stmt = $pdo->prepare($query);
        
        // Add wildcards to search term for partial matching
        $search_param = '%' . $search_term . '%';
        
        // Execute query with search parameters (repeated for each LIKE condition)
        $stmt->execute([$search_param, $search_param, $search_param]);
        
        // Fetch all results as associative array
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Courses</title>
    <!-- Include Bootstrap CSS for responsive styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa; /* Light gray background for better readability */
            font-family: 'Segoe UI', sans-serif; /* Modern font stack */
        }
        .search-highlight {
            background-color: yellow; /* Highlight color for search term matches */
            font-weight: bold; /* Make matched text stand out */
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Course Management System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="list.php">Course List</a>
                <a class="nav-link" href="create.php">Add Course</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4">
        <!-- Page Header with Back Button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Search Courses</h1>
            <a href="list.php" class="btn btn-secondary">Back to List</a>
        </div>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <!-- Search input field with current search term preserved -->
                        <input type="text" name="search" class="form-control form-control-lg" 
                               placeholder="Search by course name, code, or description..." 
                               value="<?php echo htmlspecialchars($search_term); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Search Results Section -->
        <?php if (!empty($search_term)): ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        Search Results for "<?php echo htmlspecialchars($search_term); ?>"
                        <!-- Results count badge -->
                        <span class="badge bg-light text-dark ms-2"><?php echo count($search_results); ?> courses found</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($search_results)): ?>
                        <!-- No results message -->
                        <div class="alert alert-warning text-center">
                            <h5>No courses found</h5>
                            <p class="mb-0">Try different search terms or check the spelling.</p>
                        </div>
                    <?php else: ?>
                        <!-- Search Results Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Course Name</th>
                                        <th>Course Code</th>
                                        <th>Credits</th>
                                        <th>Fee</th>
                                        <th>Start Date</th>
                                        <th>Instructor</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Loop through each search result and display in table -->
                                    <?php foreach ($search_results as $course): ?>
                                    <tr>
                                        <td><?php echo $course['CourseID']; ?></td>
                                        <td>
                                            <strong>
                                                <?php 
                                                // Highlight search term in course name
                                                $courseName = htmlspecialchars($course['CourseName']);
                                                if (!empty($search_term)) {
                                                    $courseName = preg_replace("/(" . preg_quote($search_term) . ")/i", "<span class='search-highlight'>$1</span>", $courseName);
                                                }
                                                echo $courseName;
                                                ?>
                                            </strong>
                                            <?php if ($course['Description']): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php 
                                                    // Highlight search term in description
                                                    $description = htmlspecialchars($course['Description']);
                                                    if (!empty($search_term)) {
                                                        $description = preg_replace("/(" . preg_quote($search_term) . ")/i", "<span class='search-highlight'>$1</span>", $description);
                                                    }
                                                    echo $description;
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code>
                                                <?php 
                                                // Highlight search term in course code
                                                $courseCode = htmlspecialchars($course['CourseCode']);
                                                if (!empty($search_term)) {
                                                    $courseCode = preg_replace("/(" . preg_quote($search_term) . ")/i", "<span class='search-highlight'>$1</span>", $courseCode);
                                                }
                                                echo $courseCode;
                                                ?>
                                            </code>
                                        </td>
                                        <td>
                                            <!-- Display credits with badge styling -->
                                            <span class="badge bg-info"><?php echo $course['Credits']; ?> Credits</span>
                                        </td>
                                        <td><strong>$<?php echo number_format($course['Fee'], 2); ?></strong></td>
                                        <td><?php echo date('M j, Y', strtotime($course['StartDate'])); ?></td>
                                        <td>
                                            <!-- Display instructor name or placeholder -->
                                            <?php if ($course['FirstName']): ?>
                                                <?php echo htmlspecialchars($course['FirstName'] . ' ' . $course['LastName']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Action buttons for each course -->
                                            <div class="btn-group">
                                                <a href="edit.php?id=<?php echo $course['CourseID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="delete.php?id=<?php echo $course['CourseID']; ?>" class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Delete this course?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Tips Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Search Tips</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Search by course name (e.g., "Programming", "Calculus")</li>
                    <li>Search by course code (e.g., "CS101", "MATH")</li>
                    <li>Search by description keywords</li>
                    <li>Search is case-insensitive</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>