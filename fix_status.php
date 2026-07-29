<?php
// Database connection script
require_once "backend/db.php";

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

echo "<h1>Fixing Attendance Status Database</h1>";
echo "<pre>";

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Step 1: Reset all 'Ended' attendance records that were not properly checked out
    $reset_query = "UPDATE attendance_tbl 
                    SET a_status = 'Present' 
                    WHERE a_status = 'Ended' 
                    AND time_out IS NULL";
    $stmt = $conn->prepare($reset_query);
    $stmt->execute();
    $reset_count = $stmt->rowCount();
    echo "Reset {$reset_count} records with missing checkout time from 'Ended' to 'Present'\n";
    
    // Step 2: Ensure all check-in records are 'Present'
    $present_query = "UPDATE attendance_tbl 
                      SET a_status = 'Present' 
                      WHERE status = 'check_in'";
    $present_stmt = $conn->prepare($present_query);
    $present_stmt->execute();
    $present_count = $present_stmt->rowCount();
    echo "Set {$present_count} check-in records to 'Present'\n";
    
    // Step 3: Ensure all checkout records are 'Ended'
    $ended_query = "UPDATE attendance_tbl 
                   SET a_status = 'Ended' 
                   WHERE status = 'check_out' 
                   AND time_out IS NOT NULL";
    $ended_stmt = $conn->prepare($ended_query);
    $ended_stmt->execute();
    $ended_count = $ended_stmt->rowCount();
    echo "Set {$ended_count} check-out records to 'Ended'\n";
    
    // Step 4: Set absent students (if applicable)
    $absent_query = "UPDATE attendance_tbl 
                    SET a_status = 'Absent' 
                    WHERE a_status NOT IN ('Present', 'Ended', 'Late')";
    $absent_stmt = $conn->prepare($absent_query);
    $absent_stmt->execute();
    $absent_count = $absent_stmt->rowCount();
    echo "Set {$absent_count} unclassified records to 'Absent'\n";
    
    // Commit transaction
    $conn->commit();
    echo "\nDatabase fix completed successfully!\n";
    
    // Show status distribution
    $status_query = "SELECT a_status, COUNT(*) as count FROM attendance_tbl GROUP BY a_status";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->execute();
    $statuses = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent attendance status distribution:\n";
    foreach ($statuses as $status) {
        echo "- {$status['a_status']}: {$status['count']} records\n";
    }
    
} catch (PDOException $e) {
    // Rollback on error
    $conn->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='index.php'>Return to main page</a></p>";
?>
