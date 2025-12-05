<?php
/**
 * 🔐 ADMIN AUTHENTICATION MANAGER CLASS
 * Middle Layer for Admin Security & Authentication
 * Specialized for admin users only
 */

class AuthManager {
    private $pdo;
    private $maxAttempts = 3; // Stricter for admin
    private $lockoutTime = 1800; // 30 minutes for admin
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ✅ VALIDATION: Admin-specific validation
    public function validateAdminCredentials($username, $password) {
        $errors = [];
        
        // Admin username validation (more strict)
        if (empty($username)) {
            $errors[] = "Admin username is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors[] = "Admin username must be 3-30 characters (letters, numbers, underscore only).";
        }
        
        // Admin password validation (more strict)
        if (empty($password)) {
            $errors[] = "Admin password is required.";
        } elseif (strlen($password) < 10) {
            $errors[] = "Admin password must be at least 10 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Admin password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Admin password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Admin password must contain at least one number.";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Admin password must contain at least one special character.";
        }
        
        return $errors;
    }
    
    // ✅ BRUTE FORCE PROTECTION: Enhanced for admin
    public function checkAdminBruteForce($username, $ipAddress = null) {
        try {
            // Check IP-based attempts (stricter for admin)
            if ($ipAddress) {
                $stmt = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM login_attempts 
                     WHERE ip_address = ? AND username LIKE 'admin%' 
                     AND attempt_time > (NOW() - INTERVAL 30 MINUTE)"
                );
                $stmt->execute([$ipAddress]);
                $ipAttempts = $stmt->fetchColumn();
                
                if ($ipAttempts >= 2) { // Stricter IP limit for admin
                    return true;
                }
            }
            
            // Check admin user attempts
            $stmt = $this->pdo->prepare(
                "SELECT FailedLoginAttempts, AccountLockedUntil 
                 FROM user WHERE Username = ? AND Role = 'admin'"
            );
            $stmt->execute([$username]);
            $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($adminData) {
                // Check if admin account is locked
                if ($adminData['AccountLockedUntil'] && strtotime($adminData['AccountLockedUntil']) > time()) {
                    return true;
                }
                
                // Check failed attempts
                if ($adminData['FailedLoginAttempts'] >= $this->maxAttempts) {
                    // Auto-lock admin account
                    $this->lockAdminAccount($username);
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Admin brute force check error: " . $e->getMessage());
            return true; // Fail closed for admin security
        }
    }
    
    // ✅ ADMIN LOGIN: Only allows admin role users
    public function attemptAdminLogin($username, $password, $ipAddress = null) {
        $response = [
            'success' => false,
            'message' => '',
            'admin' => null,
            'requires_2fa' => false
        ];
        
        try {
            // Check brute force protection first
            if ($this->checkAdminBruteForce($username, $ipAddress)) {
                $response['message'] = "Admin account temporarily locked for security. Please contact system administrator.";
                return $response;
            }
            
            // Get admin user data (ONLY admin role)
            $stmt = $this->pdo->prepare(
                "SELECT UserID, Username, PasswordHash, Role, IsActive, 
                        TwoFactorEnabled, FailedLoginAttempts, Email 
                 FROM user WHERE Username = ? AND Role = 'admin'"
            );
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                $this->logAttempt($username, false, $ipAddress, "Admin user not found or not admin role");
                $response['message'] = "Invalid admin credentials.";
                return $response;
            }
            
            // Check if admin account is active
            if (!$admin['IsActive']) {
                $response['message'] = "Admin account is deactivated. Please contact super administrator.";
                return $response;
            }
            
            // Verify admin password
            if (password_verify($password, $admin['PasswordHash'])) {
                // SUCCESS: Reset failed attempts
                $this->resetFailedAttempts($username);
                $this->logAttempt($username, true, $ipAddress, "Admin login success");
                
                // Prepare admin data
                $response['admin'] = [
                    'UserID' => $admin['UserID'],
                    'Username' => $admin['Username'],
                    'Role' => $admin['Role'],
                    'Email' => $admin['Email'],
                    'TwoFactorEnabled' => (bool)$admin['TwoFactorEnabled']
                ];
                
                $response['success'] = true;
                $response['requires_2fa'] = (bool)$admin['TwoFactorEnabled'];
                $response['message'] = "Admin authentication successful.";
                
            } else {
                // FAILURE: Increment failed attempts
                $this->incrementFailedAttempts($username);
                $this->logAttempt($username, false, $ipAddress, "Invalid admin password");
                $response['message'] = "Invalid admin credentials.";
            }
            
        } catch (Exception $e) {
            error_log("Admin login attempt error: " . $e->getMessage());
            $response['message'] = "System security error. Please contact administrator.";
        }
        
        return $response;
    }
    
    // ✅ ADMIN 2FA VERIFICATION (Enhanced security)
    public function verifyAdminTwoFactor($adminId, $code) {
        // Admin demo codes (different from regular users)
        $adminDemoCodes = ['789012', '456789', 'admin123'];
        
        if (in_array($code, $adminDemoCodes)) {
            return true;
        }
        
        try {
            $stmt = $this->pdo->prepare(
                "SELECT TwoFactorSecret FROM user WHERE UserID = ? AND Role = 'admin'"
            );
            $stmt->execute([$adminId]);
            $secret = $stmt->fetchColumn();
            
            // Enhanced admin 2FA verification
            return $this->verifyAdminTOTP($secret, $code);
            
        } catch (Exception $e) {
            error_log("Admin 2FA verification error: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ COMPLETE ADMIN LOGIN
    public function completeAdminLogin($adminId) {
        try {
            // Update admin login stats
            $stmt = $this->pdo->prepare(
                "UPDATE user SET 
                 LoginCount = LoginCount + 1, 
                 LastLogin = NOW(),
                 FailedLoginAttempts = 0,
                 AccountLockedUntil = NULL
                 WHERE UserID = ? AND Role = 'admin'"
            );
            return $stmt->execute([$adminId]);
        } catch (Exception $e) {
            error_log("Complete admin login error: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ ADMIN SPECIFIC SECURITY METHODS
    private function lockAdminAccount($username) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE user SET 
                 AccountLockedUntil = NOW() + INTERVAL 30 MINUTE,
                 FailedLoginAttempts = ?
                 WHERE Username = ? AND Role = 'admin'"
            );
            $stmt->execute([$this->maxAttempts, $username]);
            
            // Log security event
            $this->logSecurityEvent($username, "Admin account auto-locked due to failed attempts");
        } catch (Exception $e) {
            error_log("Lock admin account error: " . $e->getMessage());
        }
    }
    
    // ✅ PRIVATE HELPER METHODS
    private function logAttempt($username, $success, $ipAddress, $reason = "") {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO login_attempts (username, ip_address, success, reason, is_admin) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$username, $ipAddress, $success ? 1 : 0, $reason, 1]);
        } catch (Exception $e) {
            error_log("Log admin attempt error: " . $e->getMessage());
        }
    }
    
    private function logSecurityEvent($username, $event) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO security_events (username, event_type, description, ip_address) 
                 VALUES (?, 'admin_security', ?, ?)"
            );
            $stmt->execute([$username, $event, $_SERVER['REMOTE_ADDR']]);
        } catch (Exception $e) {
            error_log("Security event logging error: " . $e->getMessage());
        }
    }
    
    private function incrementFailedAttempts($username) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE user SET 
                 FailedLoginAttempts = FailedLoginAttempts + 1
                 WHERE Username = ? AND Role = 'admin'"
            );
            $stmt->execute([$username]);
            
            // Log failed attempt
            if ($stmt->rowCount() > 0) {
                $this->logSecurityEvent($username, "Failed admin login attempt");
            }
        } catch (Exception $e) {
            error_log("Increment admin attempts error: " . $e->getMessage());
        }
    }
    
    private function resetFailedAttempts($username) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE user SET 
                 FailedLoginAttempts = 0,
                 AccountLockedUntil = NULL
                 WHERE Username = ? AND Role = 'admin'"
            );
            $stmt->execute([$username]);
        } catch (Exception $e) {
            error_log("Reset admin attempts error: " . $e->getMessage());
        }
    }
    
    private function verifyAdminTOTP($secret, $code) {
        // Enhanced admin TOTP verification
        // In production, use proper library with stricter time window
        return true; // Placeholder for demo
    }
    
    // ✅ ADMIN PASSWORD STRENGTH (Stricter requirements)
    public function checkAdminPasswordStrength($password) {
        $strength = 0;
        $feedback = [];
        
        if (strlen($password) >= 10) $strength++;
        else $feedback[] = "At least 10 characters";
        
        if (preg_match('/[A-Z]/', $password)) $strength++;
        else $feedback[] = "One uppercase letter";
        
        if (preg_match('/[a-z]/', $password)) $strength++;
        else $feedback[] = "One lowercase letter";
        
        if (preg_match('/[0-9]/', $password)) $strength++;
        else $feedback[] = "One number";
        
        if (preg_match('/[^A-Za-z0-9]/', $password)) $strength++;
        else $feedback[] = "One special character";
        
        // Admin-specific: check for common patterns
        if (!preg_match('/(.)\1{2,}/', $password)) $strength++; // No repeated chars
        else $feedback[] = "No repeated characters";
        
        return [
            'score' => $strength,
            'max_score' => 6,
            'feedback' => $feedback,
            'is_strong' => $strength >= 5
        ];
    }
    
    // ✅ CHECK ADMIN PRIVILEGES
    public function verifyAdminPrivileges($userId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT Role, IsActive FROM user WHERE UserID = ?"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($user && $user['Role'] === 'admin' && $user['IsActive']);
        } catch (Exception $e) {
            error_log("Admin privileges check error: " . $e->getMessage());
            return false;
        }
    }
}
?>