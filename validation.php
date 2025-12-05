<?php
/**
 * VALIDATION.PHP
 * Comprehensive validation functions for all data types
 */

/**
 * Validate course data
 */
function validate_course_data($data) {
    $errors = [];
    
    // Course name validation
    if (empty(trim($data['course_name'] ?? ''))) {
        $errors[] = "Course name is required";
    } elseif (strlen(trim($data['course_name'])) > 100) {
        $errors[] = "Course name must be 100 characters or less";
    }
    
    // Course code validation
    if (empty(trim($data['course_code'] ?? ''))) {
        $errors[] = "Course code is required";
    } elseif (!preg_match('/^[A-Za-z0-9]{2,20}$/', $data['course_code'])) {
        $errors[] = "Course code must be 2-20 alphanumeric characters";
    }
    
    // Credits validation
    if (!isset($data['credits']) || $data['credits'] < 1 || $data['credits'] > 10) {
        $errors[] = "Credits must be between 1 and 10";
    }
    
    // Fee validation
    if (!isset($data['fee']) || $data['fee'] < 0 || $data['fee'] > 10000) {
        $errors[] = "Fee must be between $0 and $10,000";
    }
    
    // Date validation
    if (empty($data['start_date'])) {
        $errors[] = "Start date is required";
    } elseif (strtotime($data['start_date']) < strtotime('today')) {
        $errors[] = "Start date cannot be in the past";
    }
    
    return $errors;
}

/**
 * Validate student registration data
 */
function validate_student_registration($data) {
    $errors = [];
    
    // Name validation
    if (empty(trim($data['student_name'] ?? ''))) {
        $errors[] = "Student name is required";
    } elseif (strlen(trim($data['student_name'])) > 100) {
        $errors[] = "Student name must be 100 characters or less";
    } elseif (!preg_match('/^[a-zA-Z\s\.\-]{2,100}$/', $data['student_name'])) {
        $errors[] = "Student name can only contain letters, spaces, dots, and hyphens";
    }
    
    // Course validation
    if (empty(trim($data['course_name'] ?? ''))) {
        $errors[] = "Course name is required";
    }
    
    // Email validation
    if (!empty($data['email']) && !validate_email($data['email'])) {
        $errors[] = "Invalid email format";
    }
    
    // Phone validation
    if (!empty($data['phone']) && !validate_phone($data['phone'])) {
        $errors[] = "Invalid phone number format";
    }
    
    return $errors;
}

/**
 * Validate staff data
 */
function validate_staff_data($data) {
    $errors = [];
    
    // First name validation
    if (empty(trim($data['first_name'] ?? ''))) {
        $errors[] = "First name is required";
    }
    
    // Last name validation  
    if (empty(trim($data['last_name'] ?? ''))) {
        $errors[] = "Last name is required";
    }
    
    // Email validation
    if (!empty($data['email']) && !validate_email($data['email'])) {
        $errors[] = "Invalid email format";
    }
    
    // Salary validation
    if (isset($data['salary']) && ($data['salary'] < 0 || $data['salary'] > 200000)) {
        $errors[] = "Salary must be between $0 and $200,000";
    }
    
    return $errors;
}
?>