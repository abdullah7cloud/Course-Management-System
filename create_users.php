<?php
// create_users.php - Run this to create users
echo "<h2>👥 Creating Default Users</h2>";

$host = 'localhost';
$dbname = 'course_management_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Create users table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS User (
        UserID INT AUTO_INCREMENT PRIMARY KEY,
        Username VARCHAR(50) UNIQUE NOT NULL,
        PasswordHash VARCHAR(255) NOT NULL,
        Role VARCHAR(20),
        IsActive BOOLEAN DEFAULT 1,
        CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create users with simple passwords
    $users = [
        ['admin', 'password', 'Admin'],
        ['teacher', 'password', 'Staff'],
        ['student', 'password', 'Student'],
        ['abdullah920', 'password', 'Admin']
    ];
    
    foreach ($users as $user) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO User (Username, PasswordHash, Role) VALUES (?, ?, ?)");
        $stmt->execute([$user[0], password_hash($user[1], PASSWORD_DEFAULT), $user[2]]);
        echo "<p>✅ Created user: <strong>{$user[0]}</strong> with password: <strong>{$user[1]}</strong></p>";
    }
    
    echo "<h3 style='color: green;'>🎉 Users created successfully!</h3>";
    echo "<p>You can now login with:</p>";
    echo "<ul>";
    echo "<li>Username: <strong>admin</strong> | Password: <strong>password</strong></li>";
    echo "<li>Username: <strong>teacher</strong> | Password: <strong>password</strong></li>";
    echo "<li>Username: <strong>student</strong> | Password: <strong>password</strong></li>";
    echo "<li>Username: <strong>abdullah920</strong> | Password: <strong>password</strong></li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Trying to create database first...</p>";
    
    // Try creating database
    try {
        $temp_pdo = new PDO("mysql:host=$host", $username, $password);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
        echo "<p>✅ Database created! <a href='create_users.php'>Refresh this page</a></p>";
    } catch (Exception $e2) {
        echo "<p>❌ Could not create database: " . $e2->getMessage() . "</p>";
    }
}
?>