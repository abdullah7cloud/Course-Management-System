<?php
/**
 * Advanced Filter Demo Page
 * 
 * This page demonstrates advanced filtering, searching, and sorting capabilities
 * for course management with real-time database queries.
 */

// Start session for user authentication
session_start();

// Include necessary files for authentication, database, and security
include_once "../includes/auth.php";      // Authentication functions
include_once "../includes/db_connect.php"; // Database connection
include_once "../includes/security.php";   // Security utilities

// Ensure user is logged in before accessing this page
require_login();

// Initialize variables with default values
$courses = [];          // Array to store filtered course results
$search_term = '';      // Variable for search input
$filter_status = 'all'; // Default: show all statuses
$filter_credits = 'all';// Default: show all credit values
$sort_by = 'name';      // Default sort by course name
$results_count = 0;     // Counter for number of results found

// Process filter form when submitted via GET method
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Sanitize all input values to prevent XSS and SQL injection
    $search_term = sanitize_input($_GET['search'] ?? '');      // Search term from form
    $filter_status = sanitize_input($_GET['status'] ?? 'all');  // Status filter value
    $filter_credits = sanitize_input($_GET['credits'] ?? 'all');// Credits filter value
    $sort_by = sanitize_input($_GET['sort'] ?? 'name');         // Sort option
    
    try {
        // Build base SQL query with JOIN to get instructor information
        $query = "SELECT c.*, s.FirstName, s.LastName FROM course c 
                  LEFT JOIN staff s ON c.StaffID = s.StaffID 
                  WHERE 1=1";  // 1=1 allows easy addition of AND conditions
        
        $params = [];  // Array to store parameters for prepared statement
        
        // 🔍 TEXT SEARCH FILTER - Search across multiple fields
        if (!empty($search_term)) {
            $query .= " AND (c.CourseName LIKE ? OR c.CourseCode LIKE ? OR c.Description LIKE ?)";
            $search_param = "%$search_term%";  // Wildcard search parameter
            $params[] = $search_param;  // Add parameter for CourseName
            $params[] = $search_param;  // Add parameter for CourseCode
            $params[] = $search_param;  // Add parameter for Description
        }
        
        // 📊 STATUS FILTER - Filter by active/inactive status
        if ($filter_status === 'active') {
            $query .= " AND c.IsActive = 1";  // Only active courses
        } elseif ($filter_status === 'inactive') {
            $query .= " AND c.IsActive = 0";  // Only inactive courses
        }
        // Note: 'all' status includes both active and inactive (no filter applied)
        
        // 🎯 CREDITS FILTER - Filter by specific credit value
        if ($filter_credits !== 'all') {
            $query .= " AND c.Credits = ?";
            $params[] = (int)$filter_credits;  // Cast to integer for safety
        }
        
        // 📈 SORTING - Apply sorting based on user selection
        switch($sort_by) {
            case 'name':    // Sort alphabetically by course name
                $query .= " ORDER BY c.CourseName";
                break;
            case 'code':    // Sort by course code
                $query .= " ORDER BY c.CourseCode";
                break;
            case 'credits': // Sort by credits (highest first)
                $query .= " ORDER BY c.Credits DESC";
                break;
            case 'fee':     // Sort by fee (highest first)
                $query .= " ORDER BY c.Fee DESC";
                break;
            case 'date':    // Sort by start date (newest first)
                $query .= " ORDER BY c.StartDate DESC";
                break;
            default:        // Default to sorting by name
                $query .= " ORDER BY c.CourseName";
        }
        
        // Execute the prepared statement with parameters
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        // Fetch all results as associative array
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count number of results for display
        $results_count = count($courses);
        
    } catch (Exception $e) {
        // Log error for debugging (not shown to users)
        error_log("Filter demo error: " . $e->getMessage());
        
        // Set error message for display (in production, show generic error)
        $error = "Error applying filters: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Advanced Filter Demo - Course Management</title>
    <!-- External CSS libraries for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Custom CSS for filter demonstration page */
        
        /* Base body styling */
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        
        /* Styling for filter cards */
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        /* Header for filter sections */
        .filter-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        
        /* Styling for filter badges */
        .filter-badge {
            font-size: 0.8rem;
            cursor: pointer;  /* Makes badges clickable */
        }
        
        /* Highlight box for results */
        .results-highlight {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 8px;
        }
        
        /* Active filter styling */
        .filter-active {
            background-color: #e7f3ff !important;
            border-color: #0d6efd !important;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- Brand/Logo -->
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-filter me-2"></i>Filter Demonstration
            </a>
            
            <!-- Navigation Links -->
            <div class="navbar-nav ms-auto">
                <!-- Link to main course list -->
                <a class="nav-link" href="list.php">
                    <i class="fas fa-list me-1"></i>Course List
                </a>
                
                <!-- Link to dashboard -->
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="text-center mb-5">
            <h1 class="display-5 text-primary">
                <i class="fas fa-filter me-2"></i>Advanced Filter System
            </h1>
            <p class="lead text-muted">Demonstrating comprehensive filtering, searching, and sorting capabilities</p>
        </div>

        <!-- Filter Form Card -->
        <div class="filter-card">
            <!-- Filter Header -->
            <div class="filter-header">
                <h4 class="mb-0">
                    <i class="fas fa-sliders-h me-2"></i>Filter Controls
                </h4>
                <small>Combine multiple filters for precise results</small>
            </div>
            
            <!-- Filter Form Body -->
            <div class="card-body p-4">
                <!-- Filter Form -->
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <!-- Text Search Input -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-search me-1"></i>Text Search
                            </label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search courses by name, code, or description..."
                                   value="<?php echo htmlspecialchars($search_term); ?>">
                            <small class="text-muted">Searches: Course Name, Course Code, Description</small>
                        </div>
                        
                        <!-- Status Filter Dropdown -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-toggle-on me-1"></i>Status
                            </label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                                <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            </select>
                        </div>
                        
                        <!-- Credits Filter Dropdown -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-star me-1"></i>Credits
                            </label>
                            <select name="credits" class="form-select">
                                <option value="all" <?php echo $filter_credits === 'all' ? 'selected' : ''; ?>>All Credits</option>
                                <option value="1" <?php echo $filter_credits === '1' ? 'selected' : ''; ?>>1 Credit</option>
                                <option value="2" <?php echo $filter_credits === '2' ? 'selected' : ''; ?>>2 Credits</option>
                                <option value="3" <?php echo $filter_credits === '3' ? 'selected' : ''; ?>>3 Credits</option>
                                <option value="4" <?php echo $filter_credits === '4' ? 'selected' : ''; ?>>4 Credits</option>
                            </select>
                        </div>
                        
                        <!-- Sort By Dropdown -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-sort me-1"></i>Sort By
                            </label>
                            <select name="sort" class="form-select">
                                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="code" <?php echo $sort_by === 'code' ? 'selected' : ''; ?>>Course Code</option>
                                <option value="credits" <?php echo $sort_by === 'credits' ? 'selected' : ''; ?>>Credits (High-Low)</option>
                                <option value="fee" <?php echo $sort_by === 'fee' ? 'selected' : ''; ?>>Fee (High-Low)</option>
                                <option value="date" <?php echo $sort_by === 'date' ? 'selected' : ''; ?>>Start Date (Newest)</option>
                            </select>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <!-- Submit/Apply Filters Button -->
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Apply Filters
                                </button>
                                
                                <!-- Reset Filters Button -->
                                <a href="filter_demo.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-refresh me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Active Filters Display Section -->
                <?php if ($search_term || $filter_status !== 'all' || $filter_credits !== 'all'): ?>
                <div class="mt-4 p-3 results-highlight">
                    <h6 class="mb-2">
                        <i class="fas fa-info-circle me-2"></i>Active Filters:
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Display Search Filter Badge if active -->
                        <?php if (!empty($search_term)): ?>
                            <span class="badge bg-primary filter-badge">
                                <i class="fas fa-search me-1"></i>Search: "<?php echo htmlspecialchars($search_term); ?>"
                                <!-- Remove filter button -->
                                <a href="javascript:void(0)" onclick="removeFilter('search')" class="text-white ms-1">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Display Status Filter Badge if active -->
                        <?php if ($filter_status !== 'all'): ?>
                            <span class="badge bg-success filter-badge">
                                <i class="fas fa-toggle-on me-1"></i>Status: <?php echo ucfirst($filter_status); ?>
                                <!-- Remove filter button -->
                                <a href="javascript:void(0)" onclick="removeFilter('status')" class="text-white ms-1">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Display Credits Filter Badge if active -->
                        <?php if ($filter_credits !== 'all'): ?>
                            <span class="badge bg-warning filter-badge">
                                <i class="fas fa-star me-1"></i>Credits: <?php echo $filter_credits; ?>
                                <!-- Remove filter button -->
                                <a href="javascript:void(0)" onclick="removeFilter('credits')" class="text-white ms-1">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Display Sort Filter Badge if not default -->
                        <?php if ($sort_by !== 'name'): ?>
                            <span class="badge bg-info filter-badge">
                                <i class="fas fa-sort me-1"></i>Sort: <?php echo ucfirst($sort_by); ?>
                                <!-- Remove filter button -->
                                <a href="javascript:void(0)" onclick="removeFilter('sort')" class="text-white ms-1">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Results Display Card -->
        <div class="filter-card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Filter Results
                        <!-- Results Count Badge -->
                        <span class="badge bg-primary ms-2"><?php echo $results_count; ?> courses found</span>
                    </h5>
                    <div>
                        <small class="text-muted">
                            <i class="fas fa-database me-1"></i>
                            Real-time filtering with database queries
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($courses)): ?>
                    <!-- No Results Message -->
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No courses match your filters</h5>
                        <p class="text-muted">Try adjusting your search criteria or reset the filters.</p>
                        <!-- Reset Button -->
                        <a href="filter_demo.php" class="btn btn-primary">
                            <i class="fas fa-refresh me-1"></i>Reset All Filters
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Results Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loop through each course and display in table -->
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <!-- Course Code -->
                                    <td>
                                        <code><?php echo htmlspecialchars($course['CourseCode']); ?></code>
                                    </td>
                                    
                                    <!-- Course Name with Description -->
                                    <td>
                                        <strong><?php echo htmlspecialchars($course['CourseName']); ?></strong>
                                        <?php if ($course['Description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($course['Description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Credits -->
                                    <td>
                                        <span class="badge bg-info"><?php echo $course['Credits']; ?> Credits</span>
                                    </td>
                                    
                                    <!-- Course Fee -->
                                    <td>
                                        <strong>$<?php echo number_format($course['Fee'], 2); ?></strong>
                                    </td>
                                    
                                    <!-- Status Badge -->
                                    <td>
                                        <?php if ($course['IsActive']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Start Date (formatted) -->
                                    <td><?php echo date('M j, Y', strtotime($course['StartDate'])); ?></td>
                                    
                                    <!-- Instructor Name -->
                                    <td>
                                        <?php if ($course['FirstName']): ?>
                                            <?php echo htmlspecialchars($course['FirstName'] . ' ' . $course['LastName']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Features Demonstration Card -->
        <div class="filter-card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>Filter Features Demonstrated
                </h5>
            </div>
            <div class="card-body">
                <!-- Feature Icons Grid -->
                <div class="row text-center">
                    <!-- Text Search Feature -->
                    <div class="col-md-3 mb-3">
                        <div class="text-primary">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <h6>Text Search</h6>
                            <small class="text-muted">Real-time search across multiple fields</small>
                        </div>
                    </div>
                    
                    <!-- Status Filter Feature -->
                    <div class="col-md-3 mb-3">
                        <div class="text-success">
                            <i class="fas fa-toggle-on fa-2x mb-2"></i>
                            <h6>Status Filter</h6>
                            <small class="text-muted">Filter by active/inactive status</small>
                        </div>
                    </div>
                    
                    <!-- Attribute Filter Feature -->
                    <div class="col-md-3 mb-3">
                        <div class="text-warning">
                            <i class="fas fa-star fa-2x mb-2"></i>
                            <h6>Attribute Filter</h6>
                            <small class="text-muted">Filter by specific attributes (credits)</small>
                        </div>
                    </div>
                    
                    <!-- Sorting Feature -->
                    <div class="col-md-3 mb-3">
                        <div class="text-info">
                            <i class="fas fa-sort-amount-down fa-2x mb-2"></i>
                            <h6>Multi-field Sort</h6>
                            <small class="text-muted">Sort by various criteria</small>
                        </div>
                    </div>
                </div>
                
                <!-- Technical Implementation Details -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><i class="fas fa-code me-2"></i>Technical Implementation:</h6>
                    <ul class="mb-0">
                        <li><strong>Security:</strong> Input sanitization and PDO prepared statements</li>
                        <li><strong>Database:</strong> Dynamic SQL query building with parameter binding</li>
                        <li><strong>UX:</strong> Active filter display with individual removal</li>
                        <li><strong>Performance:</strong> Efficient database queries with proper indexing</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- External JavaScript libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript Functions -->
    <script>
        /**
         * Remove individual filter from the URL and refresh page
         * @param {string} filterType - The filter parameter to remove (search, status, credits, sort)
         */
        function removeFilter(filterType) {
            // Create URL object from current location
            const url = new URL(window.location.href);
            
            // Remove the specified filter parameter
            url.searchParams.delete(filterType);
            
            // Navigate to the new URL (refreshes page without the filter)
            window.location.href = url.toString();
        }

        /**
         * Auto-submit form when certain filter dropdowns change
         * Improves user experience by automatically applying filters
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Select all dropdowns that should auto-submit
            const autoSubmitElements = document.querySelectorAll('select[name="status"], select[name="credits"], select[name="sort"]');
            
            // Add change event listener to each dropdown
            autoSubmitElements.forEach(element => {
                element.addEventListener('change', function() {
                    // Submit the filter form when dropdown value changes
                    document.getElementById('filterForm').submit();
                });
            });
        });

        /**
         * Demonstration function to show filter features
         * Can be called to explain the system capabilities
         */
        function demonstrateFilterFeatures() {
            alert(`🔍 FILTER SYSTEM DEMONSTRATION:\n\n` +
                  `1. TEXT SEARCH: Search across course names, codes, descriptions\n` +
                  `2. STATUS FILTER: Filter by active/inactive courses\n` +
                  `3. ATTRIBUTE FILTER: Filter by specific credit values\n` +
                  `4. SORTING: Multiple sorting options available\n` +
                  `5. COMBINATION: All filters can be used together\n\n` +
                  `✅ SECURITY: All inputs sanitized, PDO prepared statements\n` +
                  `✅ UX: Active filter display with individual removal\n` +
                  `✅ PERFORMANCE: Efficient database queries`);
        }

        /**
         * Log information to console when page loads
         * Useful for debugging and demonstrating features
         */
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Filter Demonstration Page Loaded');
            console.log('🔧 Features: Search, Status Filter, Credits Filter, Sorting');
            console.log('🛡️ Security: Input sanitization, PDO prepared statements');
        });
    </script>
</body>
</html>