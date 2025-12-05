<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../login.php");
    exit;
}

echo "<h1>Test Page - SQL System</h1>";
echo "<p>If you can see this, the file is working.</p>";

// Test database connection
include_once "../includes/db_connect.php";
echo "<p>Database connection: ✅ SUCCESS</p>";

// Test if we can get tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables found: " . count($tables) . "</p>";
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>Error getting tables: " . $e->getMessage() . "</p>";
}
?>
<a href="sql_inquiry.php">Go to SQL Inquiry</a>