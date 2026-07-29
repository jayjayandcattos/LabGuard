<?php
session_start();
require_once "db.php";

// Ensure only admins can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Improve debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../debug.log');

// Create login_logs table if it doesn't exist
try {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS login_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        user_role VARCHAR(20) NOT NULL,
        user_name VARCHAR(100) NOT NULL,
        login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(50) NOT NULL
    )";
    $conn->exec($create_table_sql);
    
    // Fetch login logs for admin and faculty, ordered by most recent first
    $query = "SELECT user_name, user_role, login_time FROM login_logs 
              WHERE user_role IN ('admin', 'faculty') 
              ORDER BY login_time DESC 
              LIMIT 100";  // Limit to last 100 records for performance
    
    error_log("Executing query: " . $query);
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($logs) . " login logs");
    
    // If no logs found, add a message
    if (count($logs) == 0) {
        $no_logs_message = "No login records found. Please log in as admin or faculty to see records here.";
        
        // Check if table is empty
        $count_check = $conn->query("SELECT COUNT(*) as count FROM login_logs");
        $count_result = $count_check->fetch(PDO::FETCH_ASSOC);
        if ($count_result['count'] > 0) {
            $no_logs_message .= " (Note: There are " . $count_result['count'] . " total records in the table, but none for admin or faculty roles)";
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in login_logs.php: " . $e->getMessage());
    $error_message = "Database error occurred. Please contact the administrator. Error: " . $e->getMessage();
    $logs = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Logs - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
    
    <style>
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .bg-primary {
            background-color: #007bff !important;
            color: white;
        }
        .bg-success {
            background-color: #28a745 !important;
            color: white;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
    </style>
</head>

<body>
    <?php include '../sections/nav4.php' ?>
    <?php include '../sections/admin_nav.php' ?>

    <div id="main-container">
        <h2>Recent Login Activity</h2>
        
        <div class="card mb-4" style="background-color: rgba(255, 255, 255, 0); border: none;">
            <div class="card-header">
                <h4>Admin and Faculty Login Logs</h4>
                <p><small>Showing login history for administrators and faculty members</small></p>
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>
                
                <?php if (isset($no_logs_message)): ?>
                    <div class="alert alert-info"><?= $no_logs_message ?></div>
                <?php endif; ?>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Login Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['user_name']); ?></td>
                                    <td>
                                        <span class="badge <?= $log['user_role'] === 'admin' ? 'bg-primary' : 'bg-success'; ?>">
                                            <?= ucfirst(htmlspecialchars($log['user_role'])); ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y h:i:s A', strtotime($log['login_time'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No login records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
            </div>
        </div>
    </div>
</body>

</html> 