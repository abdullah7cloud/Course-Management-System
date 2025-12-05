<?php
/**
 * STAFF_MODEL.PHP
 * 
 * Staff Management Data Functions
 * 
 * Purpose:
 * - Provides database operations for staff-related functionality
 * - Implements CRUD (Create, Read, Update, Delete) operations for staff records
 * - Handles database errors gracefully and provides consistent return formats
 * - Uses prepared statements to prevent SQL injection attacks
 * 
 * Security Features:
 * - All functions use PDO prepared statements for security
 * - Error logging for debugging without exposing sensitive information
 * - Function existence checks to prevent redeclaration errors
 */

// Include database connection to access the PDO instance
include_once "db_connect.php";

/**
 * Check if function doesn't exist before declaring it
 * This prevents "Cannot redeclare function" errors when files are included multiple times
 * This is especially important in large applications with multiple includes
 */

/**
 * Get active staff members for dropdown selection
 * 
 * Purpose: Retrieves basic staff information for use in dropdown menus
 * Typically used in forms where users need to select a staff member
 * 
 * @return array - Array of active staff members with ID and names, or empty array on error
 * 
 * Return Format:
 * [
 *     ['StaffID' => 1, 'FirstName' => 'John', 'LastName' => 'Doe'],
 *     ['StaffID' => 2, 'FirstName' => 'Jane', 'LastName' => 'Smith']
 * ]
 */
if (!function_exists('getStaff')) {
    function getStaff() {
        global $pdo; // Use the global database connection
        
        try {
            // SQL query to get active staff members with basic information
            // Only selects essential fields for dropdown display
            // Ordered by first name for easy user selection
            $query = "SELECT StaffID, FirstName, LastName FROM Staff WHERE IsActive = 1 ORDER BY FirstName";
            
            // Prepare the SQL statement to prevent SQL injection
            $stmt = $pdo->prepare($query);
            
            // Execute the query - no parameters needed for this simple select
            $stmt->execute();
            
            // Return all results as associative array for easy access
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Log the error for debugging purposes
            // error_log() writes to server error log without exposing details to users
            error_log("Error getting staff: " . $e->getMessage());
            
            // Return empty array to prevent fatal errors in calling code
            return [];
        }
    }
}

/**
 * Get detailed staff information by ID
 * 
 * Purpose: Retrieves complete staff record for editing, viewing, or processing
 * Used when detailed staff information is needed for a specific staff member
 * 
 * @param int $id - The StaffID to search for
 * @return array|null - Complete staff record as associative array, or null if not found/error
 * 
 * Return Format (if found):
 * [
 *     'StaffID' => 1,
 *     'FirstName' => 'John',
 *     'LastName' => 'Doe',
 *     'Email' => 'john.doe@school.edu',
 *     'Department' => 'Computer Science',
 *     // ... all other staff fields
 * ]
 */
if (!function_exists('getStaffById')) {
    function getStaffById($id) {
        global $pdo;
        
        try {
            // SQL query to get all fields for a specific staff member
            // Uses parameterized query for security (the ? placeholder)
            $query = "SELECT * FROM Staff WHERE StaffID = ?";
            
            $stmt = $pdo->prepare($query);
            
            // Execute with the staff ID parameter - PDO handles proper escaping
            $stmt->execute([$id]);
            
            // Return single record (fetch() returns one row, fetchAll() returns all rows)
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Log error with context for better debugging
            error_log("Error getting staff by ID $id: " . $e->getMessage());
            
            // Return null to indicate no staff member found or error occurred
            return null;
        }
    }
}

/**
 * Get all active staff members with complete information
 * 
 * Purpose: Retrieves all active staff records for listing, reporting, or administration
 * Used in staff management pages where all staff information is displayed
 * 
 * @return array - Array of all active staff records, or empty array on error
 * 
 * Return Format:
 * [
 *     [
 *         'StaffID' => 1,
 *         'FirstName' => 'John',
 *         'LastName' => 'Doe',
 *         'Email' => 'john.doe@school.edu',
 *         // ... all other fields
 *     ],
 *     // ... more staff records
 * ]
 */
if (!function_exists('getAllStaff')) {
    function getAllStaff() {
        global $pdo;
        
        try {
            // SQL query to get all active staff members with all fields
            // Ordered by first name then last name for consistent sorting
            $query = "SELECT * FROM Staff WHERE IsActive = 1 ORDER BY FirstName, LastName";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting all staff: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * USAGE EXAMPLES:
 * 
 * // Example 1: Populating a staff dropdown
 * $staffMembers = getStaff();
 * foreach ($staffMembers as $staff) {
 *     echo "<option value='{$staff['StaffID']}'>";
 *     echo "{$staff['FirstName']} {$staff['LastName']}";
 *     echo "</option>";
 * }
 * 
 * // Example 2: Displaying staff details
 * $staff = getStaffById(1);
 * if ($staff) {
 *     echo "Name: {$staff['FirstName']} {$staff['LastName']}";
 *     echo "Email: {$staff['Email']}";
 * } else {
 *     echo "Staff member not found";
 * }
 * 
 * // Example 3: Staff listing page
 * $allStaff = getAllStaff();
 * foreach ($allStaff as $staff) {
 *     // Display staff information in a table
 * }
 */

/**
 * SECURITY NOTES:
 * 
 * - All functions use prepared statements which automatically prevent SQL injection
 * - The global $pdo connection is established in db_connect.php with proper error handling
 * - Error messages are logged but not displayed to users, preventing information leakage
 * - Functions return safe default values (empty arrays or null) instead of throwing fatal errors
 */

?>