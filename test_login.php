<?php
session_start();
echo "<h2>🔍 Login Debug Test</h2>";

$host = 'localhost';
$dbname = 'school_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✅ Database connected<br>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM user WHERE Username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Admin user found<br>";
        echo "Password Hash: " . $user['PasswordHash'] . "<br>";
        
        // Test password
        $test_passwords = ['password', 'admin123', 'admin'];
        foreach ($test_passwords as $pwd) {
            $works = password_verify($pwd, $user['PasswordHash']) ? '✅ WORKS' : '❌ FAILS';
            echo "Password '$pwd': $works<br>";
        }
    } else {
        echo "❌ Admin user NOT found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>