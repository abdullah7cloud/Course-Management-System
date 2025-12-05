<?php
session_start();
include_once "../includes/db_connect.php";

echo "<h1>Database Checker</h1>";

try {
    // Check connection
    echo "<h3>✅ Database Connected Successfully</h3>";
    
    // Show all databases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h4>Available Databases:</h4>";
    foreach($databases as $db) {
        echo "- $db<br>";
    }
    
    // Show current database
    $stmt = $pdo->query("SELECT DATABASE()");
    $current_db = $stmt->fetchColumn();
    echo "<h4>Current Database: $current_db</h4>";
    
    // Show all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<h3 style='color: red'>❌ NO TABLES FOUND in database: $current_db</h3>";
    } else {
        echo "<h4>Tables in $current_db:</h4>";
        foreach($tables as $table) {
            echo "<strong>$table</strong><br>";
            
            // Show table structure
            $stmt2 = $pdo->query("DESCRIBE $table");
            $columns = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<small>";
            foreach($columns as $col) {
                echo "&nbsp;&nbsp;- {$col['Field']} ({$col['Type']})<br>";
            }
            echo "</small><br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red'>❌ Database Error: " . $e->getMessage() . "</h3>";
}
?>