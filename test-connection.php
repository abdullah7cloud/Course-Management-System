<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; }
        .box { background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th { background: #4CAF50; color: white; padding: 10px; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>";

echo "<h1>🔧 Database & System Tests - Course Management</h1>";

// Your database settings from test_database.php
$host = 'localhost';
$dbname = 'school_management';
$username = 'root';
$password = '';

echo "<div class='box'>
        <h2>📊 Database Information:</h2>
        <p><strong>Host:</strong> $host</p>
        <p><strong>Database:</strong> $dbname</p>
        <p><strong>Username:</strong> $username</p>
        <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
      </div>";

// Test 1: Database Connection
echo "<div class='box'>
        <h2>✅ Test 1: Database Connection</h2>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Database Connected Successfully!</p>";
    
    // Test 2: Check all tables
    echo "<h2>📋 Test 2: Database Tables</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p class='success'>✅ Found " . count($tables) . " tables</p>";
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Row Count</th></tr>";
        
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`")->fetch()['cnt'];
            echo "<tr><td>$table</td><td>$count rows</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No tables found in database</p>";
    }
    
    // Test 3: Check Course Table Structure
    echo "<h2>🏗️ Test 3: Course Table Structure</h2>";
    if (in_array('course', $tables)) {
        echo "<p class='success'>✅ Course table exists</p>";
        
        $columns = $pdo->query("DESCRIBE course")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>
                    <td>{$col['Field']}</td>
                    <td>{$col['Type']}</td>
                    <td>{$col['Null']}</td>
                    <td>{$col['Key']}</td>
                    <td>{$col['Default']}</td>
                  </tr>";
        }
        echo "</table>";
        
        // Show sample data
        echo "<h3>Sample Course Data:</h3>";
        $courses = $pdo->query("SELECT * FROM course LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        if (count($courses) > 0) {
            echo "<table>";
            // Header
            echo "<tr>";
            foreach (array_keys($courses[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            // Data
            foreach ($courses as $course) {
                echo "<tr>";
                foreach ($course as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ No data in course table</p>";
        }
    } else {
        echo "<p class='error'>❌ Course table does not exist</p>";
        echo "<p>Creating course table for testing...</p>";
        
        // Try to create course table for testing
        $sql = "CREATE TABLE IF NOT EXISTS course (
            CourseID INT AUTO_INCREMENT PRIMARY KEY,
            CourseCode VARCHAR(20) UNIQUE NOT NULL,
            CourseName VARCHAR(100) NOT NULL,
            Credits INT NOT NULL CHECK (Credits BETWEEN 1 AND 6),
            Fee DECIMAL(10,2) DEFAULT 0.00,
            IsActive BOOLEAN DEFAULT TRUE,
            StartDate DATE,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($pdo->exec($sql) !== false) {
            echo "<p class='success'>✅ Course table created for testing</p>";
            
            // Add test data
            $testData = [
                ['CS101', 'Introduction to Programming', 3, 1500.00],
                ['MATH101', 'Calculus I', 4, 1200.00],
                ['ENG101', 'English Composition', 3, 1000.00]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO course (CourseCode, CourseName, Credits, Fee) VALUES (?, ?, ?, ?)");
            foreach ($testData as $data) {
                $stmt->execute($data);
            }
            echo "<p class='success'>✅ Added test course data</p>";
        }
    }
    
    // Test 4: Check User Table
    echo "<h2>👥 Test 4: User Table</h2>";
    if (in_array('user', $tables)) {
        $users = $pdo->query("SELECT COUNT(*) as count FROM user")->fetch()['count'];
        echo "<p class='success'>✅ User table exists with $users users</p>";
    }
    
    $pdo = null;
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Possible solutions:</strong></p>";
    echo "<ul>
            <li>Check if MySQL is running in XAMPP</li>
            <li>Check database name: school_management</li>
            <li>Try without password (root with empty password)</li>
          </ul>";
}

echo "</div>";

echo "<div class='box'>
        <h2>🔗 Quick Navigation:</h2>
        <p><a href='test-form.html'>→ Test Course Form</a></p>
        <p><a href='test-sql.php'>→ Test SQL Queries</a></p>
        <p><a href='../index.php'>→ Back to Main System</a></p>
      </div>";

echo "</body></html>";
?>