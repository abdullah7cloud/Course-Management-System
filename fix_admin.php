<?php
require_once 'includes/db_connect.php';

// 1. The raw password you want to use
$username = 'admin';
$raw_password = 'password'; 

// 2. Hash it securely using PHP's native function
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

try {
    // 3. Delete old admin if exists to avoid duplicates
    $pdo->prepare("DELETE FROM user WHERE Username = ?")->execute([$username]);

    // 4. Insert fresh admin user
    $stmt = $pdo->prepare("INSERT INTO user (Username, PasswordHash, Role, IsActive) VALUES (?, ?, 'admin', 1)");
    
    if ($stmt->execute([$username, $hashed_password])) {
        echo "<h1>✅ Success!</h1>";
        echo "<p>User <strong>$username</strong> has been reset.</p>";
        echo "<p>New Password: <strong>$raw_password</strong></p>";
        echo "<p>Hash generated: $hashed_password</p>";
        echo "<br><a href='login.php'>Go to Login Page</a>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>