<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once "db.php";

echo "<h1>Login Logs Debug Page</h1>";

try {
    // Check if login_logs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'login_logs'");
    if ($table_check->rowCount() == 0) {
        echo "<p>Table 'login_logs' does not exist. Creating it now...</p>";
        
        // Create login_logs table
        $create_table_sql = "CREATE TABLE IF NOT EXISTS login_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            user_role VARCHAR(20) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(50) NOT NULL
        )";
        $conn->exec($create_table_sql);
        echo "<p>Table created successfully.</p>";
    } else {
        echo "<p>Table 'login_logs' exists.</p>";
    }
    
    // Check if table has data
    $count_query = "SELECT COUNT(*) as count FROM login_logs";
    $count_stmt = $conn->query($count_query);
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Found {$count_result['count']} records in login_logs table.</p>";
    
    // Display all records in the table
    $query = "SELECT * FROM login_logs ORDER BY login_time DESC";
    $stmt = $conn->query($query);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($logs) > 0) {
        echo "<h2>Login Records:</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        foreach (array_keys($logs[0]) as $header) {
            echo "<th style='padding: 8px; text-align: left;'>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($logs as $log) {
            echo "<tr>";
            foreach ($log as $value) {
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No records found in the login_logs table.</p>";
        
        // Add test data
        echo "<p>Adding test data...</p>";
        
        $test_data = "INSERT INTO login_logs (user_id, user_role, user_name, ip_address) VALUES 
            ('admin_test', 'admin', 'Admin Test User', '127.0.0.1'),
            ('faculty_test', 'faculty', 'Faculty Test User', '127.0.0.1')";
        $conn->exec($test_data);
        
        echo "<p>Test data added. <a href='debug_login_logs.php'>Refresh</a> to see the results.</p>";
    }
    
    // Add a form to manually add a login record for testing
    echo "<h2>Add Test Login Record</h2>";
    echo "<form method='post' action='debug_login_logs.php'>";
    echo "<input type='hidden' name='add_test_record' value='1'>";
    echo "<table>";
    echo "<tr><td>User ID:</td><td><input type='text' name='user_id' value='test_user_" . time() . "' required></td></tr>";
    echo "<tr><td>Role:</td><td><select name='user_role' required><option value='admin'>Admin</option><option value='faculty'>Faculty</option></select></td></tr>";
    echo "<tr><td>Name:</td><td><input type='text' name='user_name' value='Test User' required></td></tr>";
    echo "<tr><td><input type='submit' value='Add Test Record'></td></tr>";
    echo "</table>";
    echo "</form>";
    
    // Process the form submission
    if (isset($_POST['add_test_record'])) {
        $user_id = $_POST['user_id'];
        $user_role = $_POST['user_role'];
        $user_name = $_POST['user_name'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $insert_query = "INSERT INTO login_logs (user_id, user_role, user_name, ip_address) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->execute([$user_id, $user_role, $user_name, $ip_address]);
        
        echo "<p>Test record added. <a href='debug_login_logs.php'>Refresh</a> to see the results.</p>";
    }
    
    // Check if login.php is correctly capturing login attempts
    echo "<h2>Login.php Integration Check</h2>";
    echo "<p>Checking if login.php is correctly logging login attempts...</p>";
    
    // Look at the login.php code
    $login_php_path = __DIR__ . "/login.php";
    if (file_exists($login_php_path)) {
        $login_php_content = file_get_contents($login_php_path);
        if (strpos($login_php_content, "INSERT INTO login_logs") !== false) {
            echo "<p style='color: green;'>✓ login.php appears to be logging login attempts.</p>";
        } else {
            echo "<p style='color: red;'>✗ login.php does not seem to be logging login attempts.</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Cannot find login.php to check.</p>";
    }
    
    echo "<h2>Navigation</h2>";
    echo "<p><a href='login_logs.php'>Go to Login Logs Page</a></p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?> 