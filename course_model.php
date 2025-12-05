<?php
// Include database connection file to establish PDO connection
include_once "db_connect.php";

// COURSE MANAGEMENT FUNCTIONS - FIXED VERSION
// Uses correct table name 'course' instead of 'courses'

/**
 * Get all active courses with staff information
 * @return array - Array of course records with staff details or empty array on error
 */
function getAllCourses() {
    global $pdo;
    try {
        // FIXED: Changed table name from 'courses' to 'course'
        $query = "SELECT c.*, s.FirstName, s.LastName 
                  FROM course c 
                  LEFT JOIN staff s ON c.StaffID = s.StaffID 
                  WHERE c.IsActive = 1
                  ORDER BY c.CourseName";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting courses: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a single course by ID with staff information
 * @param int $id - Course ID to search for
 * @return mixed - Course record as associative array or null if not found/error
 */
function getCourseById($id) {
    global $pdo;
    try {
        // FIXED: Changed table name from 'courses' to 'course'
        $query = "SELECT c.*, s.FirstName, s.LastName 
                  FROM course c 
                  LEFT JOIN staff s ON c.StaffID = s.StaffID 
                  WHERE c.CourseID = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting course: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate course data before database operations
 * @param array $data - Course data to validate
 * @return array - Array of error messages, empty if validation passes
 */
function validateCourse($data) {
    $errors = [];
    
    if (empty(trim($data['course_name']))) {
        $errors[] = "Course name is required";
    }
    
    if (empty(trim($data['course_code']))) {
        $errors[] = "Course code is required";
    }
    
    if (!isset($data['credits']) || $data['credits'] < 1 || $data['credits'] > 10) {
        $errors[] = "Credits must be between 1 and 10";
    }
    
    if (!isset($data['fee']) || $data['fee'] < 0) {
        $errors[] = "Fee must be a positive number";
    }
    
    if (empty($data['start_date'])) {
        $errors[] = "Start date is required";
    }
    
    return $errors;
}

/**
 * Create a new course in the database
 * @param array $data - Course data to insert
 * @return array - Result array with success status and message
 */
function createCourse($data) {
    global $pdo;
    try {
        // FIXED: Changed table name from 'courses' to 'course'
        $query = "INSERT INTO course (CourseName, CourseCode, Description, Credits, Fee, StartDate, StaffID) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['course_name'],
            $data['course_code'],
            $data['description'],
            $data['credits'],
            $data['fee'],
            $data['start_date'],
            $data['staff_id'] ?: null
        ]);
        
        return ['success' => true, 'message' => 'Course created successfully!'];
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'Course code already exists!'];
        }
        return ['success' => false, 'message' => 'Error creating course: ' . $e->getMessage()];
    }
}

/**
 * Update an existing course in the database
 * @param int $id - Course ID to update
 * @param array $data - Updated course data
 * @return array - Result array with success status and message
 */
function updateCourse($id, $data) {
    global $pdo;
    try {
        // FIXED: Changed table name from 'courses' to 'course'
        $query = "UPDATE course SET 
                  CourseName = ?, CourseCode = ?, Description = ?, Credits = ?, 
                  Fee = ?, StartDate = ?, StaffID = ? 
                  WHERE CourseID = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['course_name'],
            $data['course_code'],
            $data['description'],
            $data['credits'],
            $data['fee'],
            $data['start_date'],
            $data['staff_id'] ?: null,
            $id
        ]);
        
        return ['success' => true, 'message' => 'Course updated successfully!'];
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'Course code already exists!'];
        }
        return ['success' => false, 'message' => 'Error updating course: ' . $e->getMessage()];
    }
}

/**
 * Delete a course (soft delete - sets IsActive to 0)
 * @param int $id - Course ID to delete
 * @return array - Result array with success status and message
 */
function deleteCourse($id) {
    global $pdo;
    try {
        // FIXED: Changed table name from 'courses' to 'course'
        $query = "UPDATE course SET IsActive = 0 WHERE CourseID = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        
        return ['success' => true, 'message' => 'Course deleted successfully!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error deleting course: ' . $e->getMessage()];
    }
}

/**
 * Search courses by name, code, or description
 * @param string $search_term - Term to search for
 * @return array - Array of matching courses or empty array on error
 */
function searchCourses($search_term) {
    global $pdo;
    try {
        // FIXED: Changed table name from 'courses' to 'course'
        $query = "SELECT c.*, s.FirstName, s.LastName 
                  FROM course c 
                  LEFT JOIN staff s ON c.StaffID = s.StaffID 
                  WHERE (c.CourseName LIKE ? OR c.CourseCode LIKE ? OR c.Description LIKE ?)
                  AND c.IsActive = 1
                  ORDER BY c.CourseName";
        $stmt = $pdo->prepare($query);
        $search_pattern = "%$search_term%";
        $stmt->execute([$search_pattern, $search_pattern, $search_pattern]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error searching courses: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all active staff members for dropdown selection
 * @return array - Array of staff records or empty array on error
 */
function getStaff() {
    global $pdo;
    try {
        $query = "SELECT StaffID, FirstName, LastName FROM staff WHERE IsActive = 1 ORDER BY FirstName";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting staff: " . $e->getMessage());
        return [];
    }
}
?>