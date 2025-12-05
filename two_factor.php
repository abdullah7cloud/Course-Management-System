<?php
/**
 * TWO-FACTOR AUTHENTICATION SYSTEM
 * 
 * This class implements Time-based One-Time Password (TOTP) authentication
 * following RFC 6238 standards. It provides methods for generating secrets,
 * verifying codes, and handling backup authentication methods.
 * 
 * Features:
 * - Google Authenticator compatible
 * - QR code generation for easy setup
 * - Email backup codes
 * - Multi-window verification for clock skew tolerance
 * 
 * @package Security
 * @author Course Management System
 */

class TwoFactorAuth {
    
    /**
     * Generate a random secret key for 2FA
     * 
     * Creates a 32-character Base32 encoded secret key that's compatible
     * with Google Authenticator and other TOTP apps.
     * 
     * @return string 32-character Base32 encoded secret
     */
    public static function generateSecret() {
        // Base32 alphabet (RFC 4648) - excludes easily confused characters
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        // Generate 32 random characters from the Base32 alphabet
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[rand(0, 31)];
        }
        
        return $secret;
    }
    
    /**
     * Generate QR code URL for Google Authenticator
     * 
     * Creates an otpauth:// URL that can be converted to a QR code.
     * Google Authenticator and other apps can scan this to automatically
     * set up the 2FA account.
     * 
     * @param string $username User's username or email
     * @param string $secret The 2FA secret key
     * @param string $issuer Name of the service/application
     * @return string otpauth:// URL for QR code generation
     */
    public static function getQRCodeUrl($username, $secret, $issuer = 'Course Management System') {
        // Format: otpauth://totp/ISSUER:USERNAME?secret=SECRET&issuer=ISSUER
        $url = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),  // URL-encode issuer name
            rawurlencode($username), // URL-encode username
            $secret,                 // Secret key (already Base32)
            rawurlencode($issuer)    // URL-encode issuer again for parameter
        );
        return $url;
    }
    
    /**
     * Verify a TOTP code entered by the user
     * 
     * Checks if the provided code matches what's expected for the current time.
     * Includes tolerance for clock skew by checking ±1 time window (30 seconds each).
     * Uses hash_equals() for timing-attack-safe string comparison.
     * 
     * @param string $secret The user's 2FA secret key
     * @param string $code The 6-digit code entered by user
     * @return bool True if code is valid, false otherwise
     */
    public static function verifyCode($secret, $code) {
        // Get current time slice (each slice is 30 seconds)
        $timeSlice = floor(time() / 30);
        
        // Check current time and ±1 time window for clock skew tolerance
        // This allows codes to be valid for 90 seconds total (current ±30 seconds)
        for ($i = -1; $i <= 1; $i++) {
            // Calculate expected code for this time slice
            $calculatedCode = self::getCode($secret, $timeSlice + $i);
            
            // Compare codes using timing-attack-safe comparison
            if (hash_equals($calculatedCode, $code)) {
                return true; // Code matches!
            }
        }
        
        // No match found in any time window
        return false;
    }
    
    /**
     * Generate TOTP code for a specific time slice
     * 
     * Implements RFC 6238 TOTP algorithm:
     * 1. Base32 decode the secret
     * 2. Pack time into binary
     * 3. Generate HMAC-SHA1 hash
     * 4. Dynamic truncation
     * 5. Generate 6-digit code
     * 
     * @param string $secret Base32 encoded secret key
     * @param int $timeSlice Time slice number
     * @return string 6-digit TOTP code
     */
    private static function getCode($secret, $timeSlice) {
        // Step 1: Decode Base32 secret to binary
        $secretKey = self::base32Decode($secret);
        
        // Step 2: Pack time into 64-bit binary string
        // Using N* format for 32-bit unsigned long (big endian)
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        
        // Step 3: Generate HMAC-SHA1 hash
        $hm = hash_hmac('sha1', $time, $secretKey, true);
        
        // Step 4: Dynamic truncation (RFC 6238 section 5.3)
        // Last 4 bits of hash determine offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        
        // Step 5: Extract 4 bytes starting at offset
        $hashpart = substr($hm, $offset, 4);
        
        // Step 6: Unpack to 32-bit integer
        $value = unpack('N', $hashpart);
        $value = $value[1];
        
        // Step 7: Remove most significant bit (for compatibility)
        $value = $value & 0x7FFFFFFF;
        
        // Step 8: Generate 6-digit code (modulo 10^6)
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Decode Base32 string to binary
     * 
     * Handles Base32 decoding without external libraries.
     * Follows RFC 4648 Base32 encoding standard.
     * 
     * @param string $secret Base32 encoded string
     * @return string|bool Binary string or false on error
     */
    private static function base32Decode($secret) {
        if (empty($secret)) return '';
        
        // Base32 alphabet (RFC 4648)
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        // Validate padding
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0]; // Valid padding lengths
        if (!in_array($paddingCharCount, $allowedValues)) return false;
        
        // Verify padding is in correct positions
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) return false;
        }
        
        // Remove padding characters
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = "";
        
        // Process in chunks of 8 characters (5 bits each = 40 bits)
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = "";
            
            // Validate character is in Base32 alphabet
            if (!in_array($secret[$i], $base32chars)) return false;
            
            // Convert each character to 5-bit binary string
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            
            // Split 40-bit string into 5 groups of 8 bits
            $eightBits = str_split($x, 8);
            
            // Convert each 8-bit group back to character
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= ( ($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48 ) ? $y:"";
            }
        }
        
        return $binaryString;
    }
    
    /**
     * Send verification code via email (backup authentication method)
     * 
     * Used when users don't have access to their authenticator app.
     * Sends HTML email with a time-limited verification code.
     * 
     * @param string $email User's email address
     * @param string $code 6-digit verification code
     * @return bool True if mail was accepted for delivery
     */
    public static function sendEmailCode($email, $code) {
        // Email subject
        $subject = "Your Verification Code - Course Management System";
        
        // HTML email template with inline CSS
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .code { font-size: 24px; font-weight: bold; color: #667eea; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Two-Factor Authentication</h2>
                <p>Your verification code is:</p>
                <div class='code'>$code</div>
                <p>This code will expire in 10 minutes.</p>
                <p><small>If you didn't request this code, please ignore this email.</small></p>
            </div>
        </body>
        </html>
        ";
        
        // Email headers for HTML content
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@coursemanagement.com" . "\r\n";
        
        // Send email using PHP's mail() function
        // Note: In production, consider using a library like PHPMailer
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Generate backup codes for emergency access
     * 
     * Creates one-time use codes that users can save and use if they
     * lose access to their primary 2FA method. Each code is 10 characters
     * and should be stored securely by the user.
     * 
     * @param int $count Number of backup codes to generate (default: 5)
     * @return array List of backup codes
     */
    public static function generateBackupCodes($count = 5) {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Generate 5 random bytes (40 bits), convert to hex (10 characters)
            // Example: bin2hex(random_bytes(5)) -> "a1b2c3d4e5"
            $codes[] = strtoupper(bin2hex(random_bytes(5)));
        }
        
        return $codes;
    }
}
?>