<?php
/**
 * COURSE MANAGEMENT SYSTEM - LOGIN PAGE WITH 2FA
 */

session_start();

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// =============================================
// SECURITY FUNCTIONS
// =============================================

// CSRF Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input Validation
function validate_input($username, $password) {
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    return $errors;
}

function validate_2fa_code($code) {
    $errors = [];
    
    if (empty($code)) {
        $errors[] = "2FA code is required";
    } elseif (!preg_match('/^[0-9]{6}$/', $code)) {
        $errors[] = "2FA code must be exactly 6 digits";
    }
    
    return $errors;
}

// Sanitization
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// =============================================
// SIMPLIFIED TWO-FACTOR AUTHENTICATION SYSTEM
// =============================================

class TwoFactorAuth {
    
    public function verifyTOTP($username, $code) {
        // For demo purposes, accept only 123456 as valid code for admin
        if ($username === 'admin' && $code === '123456') {
            return true;
        }
        
        // For other users or invalid codes
        return false;
    }
}

// =============================================
// AUTHENTICATION BUSINESS LOGIC
// =============================================

class AuthenticationSystem {
    private $valid_users;
    private $twofa;
    
    public function __construct() {
        $this->valid_users = [
            'admin' => [
                'password' => 'admin123',
                'role' => 'admin',
                'email' => 'admin@cms.com',
                'requires_2fa' => true,
            ],
            'teacher' => [
                'password' => 'admin123',
                'role' => 'teacher',
                'email' => 'teacher@cms.com',
                'requires_2fa' => false
            ],
            'student' => [
                'password' => 'admin123',
                'role' => 'student',
                'email' => 'student@cms.com',
                'requires_2fa' => false
            ]
        ];
        
        $this->twofa = new TwoFactorAuth();
    }
    
    public function authenticate($username, $password) {
        $result = [
            'success' => false,
            'message' => '',
            'user' => null,
            'requires_2fa' => false,
            'auth_token' => null
        ];
        
        $validationErrors = validate_input($username, $password);
        if (!empty($validationErrors)) {
            $result['message'] = implode(". ", $validationErrors);
            return $result;
        }
        
        if (!isset($this->valid_users[$username])) {
            $result['message'] = "Invalid username or password";
            return $result;
        }
        
        $user = $this->valid_users[$username];
        
        if ($password !== $user['password']) {
            $result['message'] = "Invalid username or password";
            return $result;
        }
        
        // Check if 2FA is required
        if ($user['requires_2fa']) {
            $result['requires_2fa'] = true;
            $result['auth_token'] = bin2hex(random_bytes(32));
            $_SESSION['pending_auth'] = [
                'username' => $username,
                'auth_token' => $result['auth_token'],
                'expires' => time() + 300 // 5 minutes
            ];
        } else {
            // Direct login for non-2FA users
            $result['success'] = true;
            $result['user'] = [
                'id' => uniqid(),
                'username' => $username,
                'role' => $user['role'],
                'email' => $user['email']
            ];
            $result['message'] = "Login successful";
        }
        
        return $result;
    }
    
    public function verifyTwoFactor($auth_token, $code) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Check if pending authentication exists
        if (!isset($_SESSION['pending_auth']) || 
            $_SESSION['pending_auth']['auth_token'] !== $auth_token ||
            $_SESSION['pending_auth']['expires'] < time()) {
            $result['message'] = "Authentication session expired or invalid";
            return $result;
        }
        
        $username = $_SESSION['pending_auth']['username'];
        
        $validationErrors = validate_2fa_code($code);
        if (!empty($validationErrors)) {
            $result['message'] = implode(". ", $validationErrors);
            return $result;
        }
        
        // Verify 2FA code
        if ($this->twofa->verifyTOTP($username, $code)) {
            $result['success'] = true;
            $user = $this->valid_users[$username];
            $result['user'] = [
                'id' => uniqid(),
                'username' => $username,
                'role' => $user['role'],
                'email' => $user['email']
            ];
            unset($_SESSION['pending_auth']);
        } else {
            $result['message'] = "Invalid 2FA code. Please try again.";
        }
        
        return $result;
    }
}

// =============================================
// LOGIN PROCESSING
// =============================================

$error_msg = '';
$username = '';
$show_2fa_form = false;
$auth_token = '';
$auth_system = new AuthenticationSystem();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Security validation failed. Please refresh the page.";
    } else {
        // Handle 2FA verification
        if (isset($_POST['verify_2fa'])) {
            $auth_token = sanitize_input($_POST['auth_token']);
            $code = sanitize_input($_POST['code']);
            
            $verify_result = $auth_system->verifyTwoFactor($auth_token, $code);
            
            if ($verify_result['success']) {
                // 2FA successful - set session
                $_SESSION['user_id'] = $verify_result['user']['id'];
                $_SESSION['username'] = $verify_result['user']['username'];
                $_SESSION['role'] = $verify_result['user']['role'];
                $_SESSION['email'] = $verify_result['user']['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['twofa_verified'] = true;
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error_msg = $verify_result['message'];
                $show_2fa_form = true;
                $auth_token = $auth_token; // Keep the same auth token
            }
        } 
        // Handle initial login
        else {
            $username = sanitize_input($_POST['username']);
            $password = $_POST['password'];
            
            $auth_result = $auth_system->authenticate($username, $password);
            
            if ($auth_result['success']) {
                // Direct login for non-2FA users
                $_SESSION['user_id'] = $auth_result['user']['id'];
                $_SESSION['username'] = $auth_result['user']['username'];
                $_SESSION['role'] = $auth_result['user']['role'];
                $_SESSION['email'] = $auth_result['user']['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } elseif ($auth_result['requires_2fa']) {
                // Show 2FA form
                $show_2fa_form = true;
                $auth_token = $auth_result['auth_token'];
            } else {
                $error_msg = $auth_result['message'];
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Course Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: #2c3e50;
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .login-body {
            padding: 25px;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2c3e50;
        }
        
        .btn {
            width: 100%;
            padding: 10px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #1a252f;
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .code-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .code-input {
            width: 40px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .code-input:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 5px rgba(44, 62, 80, 0.3);
            outline: none;
        }
        
        .code-input.filled {
            border-color: #28a745;
            background-color: #f8fff9;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>Course Management System</h1>
        <p>Secure Login</p>
    </div>
    
    <div class="login-body">
        <?php if($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if($show_2fa_form): ?>
            <!-- 2FA Verification Form -->
            <div class="alert alert-info">
                <strong>Two-Factor Authentication Required</strong><br>
                Enter the 6-digit code from your authenticator app.
            </div>
            
            <form method="POST" action="login.php" id="twofaForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="verify_2fa" value="1">
                <input type="hidden" name="auth_token" value="<?php echo $auth_token; ?>">
                <input type="hidden" name="code" id="hiddenCode">
                
                <div class="form-group">
                    <label for="code">Enter 6-digit verification code</label>
                    <div class="code-inputs">
                        <input type="text" class="code-input" maxlength="1" data-index="1">
                        <input type="text" class="code-input" maxlength="1" data-index="2">
                        <input type="text" class="code-input" maxlength="1" data-index="3">
                        <input type="text" class="code-input" maxlength="1" data-index="4">
                        <input type="text" class="code-input" maxlength="1" data-index="5">
                        <input type="text" class="code-input" maxlength="1" data-index="6">
                    </div>
                </div>
                
                <button type="submit" class="btn" id="verifyBtn" disabled>Verify Code</button>
                <button type="button" class="btn btn-secondary" onclick="goBack()">
                    ← Back to Login
                </button>
            </form>
            
        <?php else: ?>
            <!-- Regular Login Form -->
            <form method="POST" action="login.php" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($username); ?>" 
                           required autofocus
                           placeholder="Enter username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           required
                           placeholder="Enter password">
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// 2FA Code Input Handling
function moveToNext(input) {
    const value = input.value;
    const index = parseInt(input.dataset.index);
    
    // Only allow numbers
    if (value && !/^\d$/.test(value)) {
        input.value = '';
        return;
    }
    
    if (value) {
        input.classList.add('filled');
        
        // Move to next input
        if (index < 6) {
            const nextInput = document.querySelector(`.code-input[data-index="${index + 1}"]`);
            if (nextInput) {
                nextInput.focus();
            }
        }
    } else {
        input.classList.remove('filled');
    }
    
    // Update hidden input and check if all fields are filled
    updateHiddenCode();
    checkCodeCompletion();
}

// Handle backspace and delete key
function handleBackspace(input, e) {
    if (e.key === 'Backspace' || e.key === 'Delete') {
        const index = parseInt(input.dataset.index);
        
        // If current input is empty and backspace is pressed, go to previous input
        if (!input.value && index > 1) {
            const prevInput = document.querySelector(`.code-input[data-index="${index - 1}"]`);
            if (prevInput) {
                prevInput.focus();
                prevInput.value = ''; // Clear the previous input
                prevInput.classList.remove('filled');
            }
        } else if (input.value) {
            // If current input has value, clear it
            input.value = '';
            input.classList.remove('filled');
        }
        
        updateHiddenCode();
        checkCodeCompletion();
        e.preventDefault(); // Prevent default backspace behavior
    }
}

// Handle arrow keys for navigation
function handleArrowKeys(input, e) {
    const index = parseInt(input.dataset.index);
    
    if (e.key === 'ArrowLeft' && index > 1) {
        const prevInput = document.querySelector(`.code-input[data-index="${index - 1}"]`);
        if (prevInput) prevInput.focus();
        e.preventDefault();
    }
    
    if (e.key === 'ArrowRight' && index < 6) {
        const nextInput = document.querySelector(`.code-input[data-index="${index + 1}"]`);
        if (nextInput) nextInput.focus();
        e.preventDefault();
    }
}

function updateHiddenCode() {
    const inputs = document.querySelectorAll('.code-input');
    let code = '';
    inputs.forEach(input => {
        code += input.value;
    });
    document.getElementById('hiddenCode').value = code;
}

function checkCodeCompletion() {
    const inputs = document.querySelectorAll('.code-input');
    let allFilled = true;
    inputs.forEach(input => {
        if (!input.value) {
            allFilled = false;
        }
    });
    
    const verifyBtn = document.getElementById('verifyBtn');
    if (verifyBtn) {
        verifyBtn.disabled = !allFilled;
    }
    
    // Auto-submit when all 6 digits are entered
    if (allFilled) {
        setTimeout(() => {
            document.getElementById('twofaForm').submit();
        }, 500);
    }
}

// Initialize event listeners for code inputs
document.addEventListener('DOMContentLoaded', function() {
    const codeInputs = document.querySelectorAll('.code-input');
    
    codeInputs.forEach(input => {
        // Add input event for moving to next field
        input.addEventListener('input', function() {
            moveToNext(this);
        });
        
        // Add keydown event for backspace and arrow keys
        input.addEventListener('keydown', function(e) {
            handleBackspace(this, e);
            handleArrowKeys(this, e);
        });
        
        // Add paste event to handle pasting code
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text');
            if (/^\d{6}$/.test(pastedData)) {
                // Fill all inputs with pasted code
                const digits = pastedData.split('');
                codeInputs.forEach((input, index) => {
                    if (digits[index]) {
                        input.value = digits[index];
                        input.classList.add('filled');
                    }
                });
                updateHiddenCode();
                checkCodeCompletion();
                
                // Focus the last input
                if (codeInputs[5]) {
                    codeInputs[5].focus();
                }
            }
        });
    });
    
    // Focus first code input when 2FA form loads
    const firstCodeInput = document.querySelector('.code-input[data-index="1"]');
    if (firstCodeInput) {
        firstCodeInput.focus();
    }
});

// Go back to login form
function goBack() {
    window.location.href = 'login.php';
}

// Form enhancement
const currentForm = document.getElementById('loginForm') || document.getElementById('twofaForm');
if (currentForm) {
    currentForm.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        if (btn && !btn.disabled) {
            btn.innerHTML = this.id === 'twofaForm' ? 'Verifying...' : 'Logging in...';
            btn.disabled = true;
        }
    });
}
</script>

</body>
</html>