<?php
// test_database.php - Check if database connection works
$host = 'localhost';
$dbname = 'school_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✅ Database Connected Successfully!<br><br>";
    
    // Check users table
    $users = $pdo->query("SELECT Username, PasswordHash FROM user LIMIT 5")->fetchAll();
    
    echo "📋 Users in database:<br>";
    foreach($users as $user) {
        echo "- Username: <strong>{$user['Username']}</strong><br>";
        echo "  Password Hash: {$user['PasswordHash']}<br><br>";
    }
    
    echo "<a href='login.php' class='btn btn-primary'>Go to Login</a>";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
?>