<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>SQL Test Queries</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .query-box { background: #e8f4f8; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
        .result-box { background: #f1f8e9; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th { background: #2196F3; color: white; padding: 10px; }
        td { padding: 8px; border: 1px solid #ddd; }
        pre { background: #2d2d2d; color: #fff; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>📊 SQL Query Tests - School Management System</h1>";

// Database connection
$host = 'localhost';
$dbname = 'school_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green;'>✅ Connected to database: $dbname</p>";
    
    // TEST 1: Select all courses
    echo "<div class='query-box'>
            <h3>Test 1: SELECT All Courses</h3>
            <pre>SELECT * FROM course LIMIT 5</pre>
          </div>";
    
    $result = $pdo->query("SELECT * FROM course LIMIT 5");
    echo "<div class='result-box'>";
    if ($result->rowCount() > 0) {
        echo "<table>";
        // Get column names
        $firstRow = $result->fetch(PDO::FETCH_ASSOC);
        echo "<tr>";
        foreach (array_keys($firstRow) as $col) {
            echo "<th>$col</th>";
        }
        echo "</tr>";
        
        // Output first row
        echo "<tr>";
        foreach ($firstRow as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
        
        // Output remaining rows
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No courses found in database</p>";
    }
    echo "</div>";
    
    // TEST 2: Test INSERT operation
    echo "<div class='query-box'>
            <h3>Test 2: INSERT New Course</h3>
            <pre>INSERT INTO course (CourseCode, CourseName, Credits, Fee) 
VALUES ('TEST' . RAND(), 'Test Course', 3, 999.99)</pre>
          </div>";
    
    $testCode = 'TEST' . rand(100, 999);
    $sql = "INSERT INTO course (CourseCode, CourseName, Credits, Fee) 
            VALUES ('$testCode', 'Test Course " . date('H:i:s') . "', 3, 999.99)";
    
    if ($pdo->exec($sql)) {
        $lastId = $pdo->lastInsertId();
        echo "<div class='result-box'>
                <p style='color:green;'>✅ Course inserted successfully!</p>
                <p>Course ID: $lastId</p>
                <p>Course Code: $testCode</p>
              </div>";
    }
    
    // TEST 3: Test validation - try duplicate course code
    echo "<div class='query-box'>
            <h3>Test 3: Test Unique Constraint (Should Fail)</h3>
            <pre>INSERT INTO course (CourseCode, CourseName, Credits, Fee) 
VALUES ('CS101', 'Duplicate Course', 3, 1000.00)</pre>
          </div>";
    
    try {
        $sql = "INSERT INTO course (CourseCode, CourseName, Credits, Fee) 
                VALUES ('CS101', 'Duplicate Course', 3, 1000.00)";
        $pdo->exec($sql);
        echo "<div class='result-box'><p style='color:orange;'>⚠️ No unique constraint found</p></div>";
    } catch (Exception $e) {
        echo "<div class='result-box'><p style='color:green;'>✅ Correctly rejected duplicate: " . $e->getMessage() . "</p></div>";
    }
    
    // TEST 4: Test data type validation
    echo "<div class='query-box'>
            <h3>Test 4: Test Invalid Data Type (Should Fail)</h3>
            <pre>INSERT INTO course (CourseCode, CourseName, Credits, Fee) 
VALUES ('BAD1', 'Bad Course', 'three', 'free')</pre>
          </div>";
    
    try {
        $sql = "INSERT INTO course (CourseCode, CourseName, Credits, Fee) 
                VALUES ('BAD1', 'Bad Course', 'three', 'free')";
        $pdo->exec($sql);
        echo "<div class='result-box'><p style='color:red;'>❌ Should have rejected invalid data types</p></div>";
    } catch (Exception $e) {
        echo "<div class='result-box'><p style='color:green;'>✅ Correctly rejected invalid data: " . $e->getMessage() . "</p></div>";
    }
    
    // TEST 5: Count statistics
    echo "<div class='query-box'>
            <h3>Test 5: Database Statistics</h3>
            <pre>SELECT 
    (SELECT COUNT(*) FROM course) as total_courses,
    (SELECT COUNT(*) FROM user) as total_users,
    (SELECT COUNT(*) FROM course WHERE IsActive = 1) as active_courses</pre>
          </div>";
    
    $stats = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM course) as total_courses,
        (SELECT COUNT(*) FROM user) as total_users,
        (SELECT COUNT(*) FROM course WHERE IsActive = 1) as active_courses")->fetch();
    
    echo "<div class='result-box'>
            <p>📊 Database Statistics:</p>
            <ul>
                <li>Total Courses: " . ($stats['total_courses'] ?? 0) . "</li>
                <li>Total Users: " . ($stats['total_users'] ?? 0) . "</li>
                <li>Active Courses: " . ($stats['active_courses'] ?? 0) . "</li>
            </ul>
          </div>";
    
    $pdo = null;
    
} catch (Exception $e) {
    echo "<div style='background:#ffebee; padding:15px; color:#c62828;'>
            <h3>❌ Connection Failed</h3>
            <p>Error: " . $e->getMessage() . "</p>
          </div>";
}

echo "<hr>
      <h3>🎯 Test Results Summary:</h3>
      <p>These tests verify:</p>
      <ul>
        <li>✅ Database connection works</li>
        <li>✅ SELECT queries work</li>
        <li>✅ INSERT operations work</li>
        <li>✅ Data validation works (unique constraints)</li>
        <li>✅ Data types are enforced</li>
      </ul>
      
      <p><a href='test-form.html'>→ Go to Form Testing</a></p>
      <p><a href='test-connection.php'>→ Back to Connection Test</a></p>";

echo "</body></html>";
?>