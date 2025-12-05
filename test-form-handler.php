<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Form Test Results</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .result { padding: 20px; margin: 10px 0; border-radius: 5px; }
        .pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        h2 { color: #333; }
        .back-btn { 
            display: inline-block; background: #6c757d; color: white; 
            padding: 10px 20px; text-decoration: none; border-radius: 5px; 
            margin-top: 20px;
        }
        .back-btn:hover { background: #5a6268; }
        .test-number { background: #6c757d; color: white; padding: 5px 10px; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<h2>📊 Form Test Results</h2>";
echo "<div class='result info'>Test conducted at: " . date('Y-m-d H:i:s') . "</div>";

// Get form data
$course_code = $_POST['course_code'] ?? '';
$course_name = $_POST['course_name'] ?? '';
$credits = $_POST['credits'] ?? '';
$fee = $_POST['fee'] ?? '';

echo "<div class='result info'>
        <h3>📋 Submitted Data:</h3>
        1. Course Code: <strong>" . htmlspecialchars($course_code) . "</strong><br>
        2. Course Name: <strong>" . htmlspecialchars($course_name) . "</strong><br>
        3. Credits: <strong>" . htmlspecialchars($credits) . "</strong><br>
        4. Fee: <strong>" . htmlspecialchars($fee) . "</strong>
      </div>";

// Validation tests
echo "<h3>🔍 Validation Tests:</h3>";

$test_count = 0;
$pass_count = 0;

// Test 1: Check if Course Code is empty
$test_count++;
echo "<div class='result " . (empty($course_code) ? "fail" : "pass") . "'>
        <span class='test-number'>Test $test_count</span>: Course Code Required<br>";
if (empty($course_code)) {
    echo "❌ Course Code is empty (should not be empty)";
} else {
    echo "✅ Course Code has value";
    $pass_count++;
}
echo "</div>";

// Test 2: Check Course Code length (max 20 chars)
$test_count++;
echo "<div class='result " . (strlen($course_code) > 20 ? "fail" : "pass") . "'>
        <span class='test-number'>Test $test_count</span>: Course Code Length (max 20 chars)<br>";
if (strlen($course_code) > 20) {
    echo "❌ Course Code is " . strlen($course_code) . " characters (max 20)";
} else {
    echo "✅ Course Code length OK: " . strlen($course_code) . " characters";
    $pass_count++;
}
echo "</div>";

// Test 3: Check credits is numeric
$test_count++;
if (empty($credits)) {
    echo "<div class='result fail'>
            <span class='test-number'>Test $test_count</span>: Credits is Required<br>
            ❌ Credits field is empty
          </div>";
} else {
    echo "<div class='result " . (is_numeric($credits) ? "pass" : "fail") . "'>
            <span class='test-number'>Test $test_count</span>: Credits Must Be Number<br>";
    if (!is_numeric($credits)) {
        echo "❌ Credits should be a number (you entered: '$credits')";
    } else {
        echo "✅ Credits is a valid number: $credits";
        $pass_count++;
    }
    echo "</div>";
}

// Test 4: Check credits range (1-6)
$test_count++;
if (is_numeric($credits)) {
    echo "<div class='result " . ($credits >= 1 && $credits <= 6 ? "pass" : "fail") . "'>
            <span class='test-number'>Test $test_count</span>: Credits Range (1-6)<br>";
    if ($credits < 1) {
        echo "❌ Credits ($credits) is less than minimum (1)";
    } elseif ($credits > 6) {
        echo "❌ Credits ($credits) exceeds maximum (6)";
    } else {
        echo "✅ Credits ($credits) is within valid range (1-6)";
        $pass_count++;
    }
    echo "</div>";
}

// Test 5: Check fee is numeric AND positive
$test_count++;
if (empty($fee)) {
    echo "<div class='result fail'>
            <span class='test-number'>Test $test_count</span>: Fee Validation<br>
            ❌ Fee field is empty
          </div>";
} else {
    // Check if it's a number
    if (!is_numeric($fee)) {
        echo "<div class='result fail'>
                <span class='test-number'>Test $test_count</span>: Fee Must Be Number<br>
                ❌ Fee should be a number (you entered: '$fee')
              </div>";
    } 
    // Check if negative
    else if ($fee < 0) {
        echo "<div class='result fail'>
                <span class='test-number'>Test $test_count</span>: Fee Must Be Positive<br>
                ❌ Fee cannot be negative (you entered: £" . number_format($fee, 2) . ")
              </div>";
    }
    // Valid
    else {
        echo "<div class='result pass'>
                <span class='test-number'>Test $test_count</span>: Fee Validation<br>
                ✅ Fee is valid: £" . number_format($fee, 2) . "
              </div>";
        $pass_count++;
    }
}

// Summary
echo "<div class='result " . ($pass_count == $test_count ? "pass" : "info") . "'>
        <h3>📈 Test Summary:</h3>
        Total Tests: $test_count<br>
        Passed: $pass_count<br>
        Failed: " . ($test_count - $pass_count) . "<br>
        Success Rate: " . round(($pass_count/$test_count)*100, 2) . "%
      </div>";

echo "<br><a class='back-btn' href='test-form.html'>← Go Back to Form</a>";

echo "</body></html>";
?>