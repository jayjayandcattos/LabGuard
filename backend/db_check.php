<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$host = "localhost"; 
$dbname = "labguard"; 
$username = "root"; 
$password = "";

echo "<h1>LabGuard Database Diagnostic</h1>";

try {
    // Test connection without specifying database
    $conn_test = new PDO("mysql:host=$host", $username, $password);
    $conn_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Successfully connected to MySQL server.</p>";
    
    // Check if the database exists
    $stmt = $conn_test->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Database '$dbname' exists.</p>";
        
        // Connect to the specific database
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if login_logs table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'login_logs'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table 'login_logs' exists.</p>";
            
            // Display table structure
            $stmt = $conn->query("DESCRIBE login_logs");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>Table Structure:</h2>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                foreach ($column as $key => $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            
            // Check if table has data
            $stmt = $conn->query("SELECT COUNT(*) as count FROM login_logs");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Table contains " . $row['count'] . " records.</p>";
            
            if ($row['count'] > 0) {
                // Display sample data
                $stmt = $conn->query("SELECT * FROM login_logs LIMIT 5");
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h2>Sample Data:</h2>";
                echo "<table border='1'>";
                // Table header
                echo "<tr>";
                foreach (array_keys($logs[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                // Table data
                foreach ($logs as $log) {
                    echo "<tr>";
                    foreach ($log as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p>❌ Table 'login_logs' does not exist.</p>";
            
            // Create the login_logs table
            echo "<p>Creating login_logs table...</p>";
            $create_table_sql = "CREATE TABLE IF NOT EXISTS login_logs (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50) NOT NULL,
                user_role VARCHAR(20) NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(50) NOT NULL
            )";
            $conn->exec($create_table_sql);
            echo "<p>✅ Table 'login_logs' created successfully.</p>";
            
            // Insert sample data
            $sample_data = "INSERT INTO login_logs (user_id, user_role, user_name, ip_address) VALUES 
                ('admin1', 'admin', 'Admin User', '127.0.0.1'),
                ('faculty1', 'faculty', 'Faculty User', '127.0.0.1')";
            $conn->exec($sample_data);
            echo "<p>✅ Sample data inserted successfully.</p>";
        }
    } else {
        echo "<p>❌ Database '$dbname' does not exist. Please create the database first.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='login_logs.php'>Go to Login Logs page</a></p>";
?> 