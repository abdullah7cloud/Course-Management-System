<?php
/**
 * ADVANCED VALIDATION SYSTEM
 * Comprehensive validation for security and data integrity
 */

class AdvancedValidator {
    
    /**
     * Validate email with multiple checks
     */
    public static function validateEmail($email) {
        if (empty($email)) {
            return "Email is required";
        }
        
        // Basic email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format";
        }
        
        // Check email length
        if (strlen($email) > 255) {
            return "Email is too long";
        }
        
        // Check for disposable emails
        if (self::isDisposableEmail($email)) {
            return "Disposable email addresses are not allowed";
        }
        
        return true;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        if (empty($password)) {
            return "Password is required";
        }
        
        if (strlen($password) < 8) {
            return "Password must be at least 8 characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return "Password must contain at least one number";
        }
        
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            return "Password must contain at least one special character";
        }
        
        return true;
    }
    
    /**
     * Validate course data with advanced rules
     */
    public static function validateCourse($data) {
        $errors = [];
        
        // Course name validation
        $nameResult = self::validateCourseName($data['course_name'] ?? '');
        if ($nameResult !== true) $errors[] = $nameResult;
        
        // Course code validation
        $codeResult = self::validateCourseCode($data['course_code'] ?? '');
        if ($codeResult !== true) $errors[] = $codeResult;
        
        // Credits validation
        if (!isset($data['credits']) || $data['credits'] < 1 || $data['credits'] > 10) {
            $errors[] = "Credits must be between 1 and 10";
        }
        
        // Fee validation
        $feeResult = self::validateFee($data['fee'] ?? 0);
        if ($feeResult !== true) $errors[] = $feeResult;
        
        // Date validation
        $dateResult = self::validateDate($data['start_date'] ?? '');
        if ($dateResult !== true) $errors[] = $dateResult;
        
        return $errors;
    }
    
    /**
     * Validate course name
     */
    private static function validateCourseName($name) {
        if (empty(trim($name))) {
            return "Course name is required";
        }
        
        if (strlen(trim($name)) > 100) {
            return "Course name must be 100 characters or less";
        }
        
        if (!preg_match('/^[a-zA-Z0-9\s\-\.\&\(\)]+$/', $name)) {
            return "Course name can only contain letters, numbers, spaces, hyphens, dots, ampersands, and parentheses";
        }
        
        // Check for offensive words
        if (self::containsOffensiveLanguage($name)) {
            return "Course name contains inappropriate language";
        }
        
        return true;
    }
    
    /**
     * Validate course code
     */
    private static function validateCourseCode($code) {
        if (empty(trim($code))) {
            return "Course code is required";
        }
        
        if (!preg_match('/^[A-Za-z0-9]{2,20}$/', $code)) {
            return "Course code must be 2-20 alphanumeric characters (no spaces or special characters)";
        }
        
        return true;
    }
    
    /**
     * Validate fee
     */
    private static function validateFee($fee) {
        if (!is_numeric($fee)) {
            return "Fee must be a valid number";
        }
        
        if ($fee < 0) {
            return "Fee must be a positive number";
        }
        
        if ($fee > 10000) {
            return "Fee cannot exceed $10,000";
        }
        
        return true;
    }
    
    /**
     * Validate date
     */
    private static function validateDate($date) {
        if (empty($date)) {
            return "Start date is required";
        }
        
        // Check if date is valid
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            return "Invalid date format";
        }
        
        // Check if date is in the past
        if (strtotime($date) < strtotime('today')) {
            return "Start date cannot be in the past";
        }
        
        // Check if date is too far in the future (5 years max)
        if (strtotime($date) > strtotime('+5 years')) {
            return "Start date cannot be more than 5 years in the future";
        }
        
        return true;
    }
    
    /**
     * Check for disposable email domains
     */
    private static function isDisposableEmail($email) {
        $disposableDomains = [
            'tempmail.com', 'guerrillamail.com', 'mailinator.com', 
            '10minutemail.com', 'throwawaymail.com', 'fakeinbox.com',
            'yopmail.com', 'trashmail.com', 'temp-mail.org'
        ];
        
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        return in_array($domain, $disposableDomains);
    }
    
    /**
     * Basic offensive language filter
     */
    private static function containsOffensiveLanguage($text) {
        $offensiveWords = [
            'badword1', 'offensive', 'inappropriate' // Add actual offensive words
        ];
        
        $text = strtolower($text);
        foreach ($offensiveWords as $word) {
            if (strpos($text, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize HTML content
     */
    public static function sanitizeHTML($html) {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
    
    /**
     * Validate file upload
     */
    public static function validateFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf']) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "File upload error";
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            return "File size must be less than 5MB";
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowedTypes)) {
            return "Invalid file type. Allowed: " . implode(', ', $allowedTypes);
        }
        
        return true;
    }
}
?>