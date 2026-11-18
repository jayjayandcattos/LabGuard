<?php
// Connect to the database
require_once "db.php";

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 1. First check if the column exists
    $sql = "SHOW COLUMNS FROM attendance_tbl LIKE 'status'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $statusColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statusColumn) {
        // Modify the status enum to include no_schedule
        $sql = "ALTER TABLE attendance_tbl MODIFY COLUMN status ENUM('check_in', 'check_out', 'ended', 'no_schedule') NOT NULL DEFAULT 'ended'";
        $conn->exec($sql);
        echo "Status column updated successfully<br>";
    }
    
    // 2. Check if a_status column exists
    $sql = "SHOW COLUMNS FROM attendance_tbl LIKE 'a_status'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $aStatusColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($aStatusColumn) {
        // Modify the a_status enum to include No Schedule
        $sql = "ALTER TABLE attendance_tbl MODIFY COLUMN a_status ENUM('Present', 'Absent', 'Late', 'Ended', 'No Schedule') NOT NULL";
        $conn->exec($sql);
        echo "a_status column updated successfully<br>";
    }
    
    echo "Database updated successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 