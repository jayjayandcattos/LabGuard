<?php
require_once "db.php";

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

try {
    // Check if the room_id column already exists
    $checkColumnQuery = "SHOW COLUMNS FROM attendance_tbl LIKE 'room_id'";
    $checkColumnStmt = $conn->prepare($checkColumnQuery);
    $checkColumnStmt->execute();
    $columnExists = $checkColumnStmt->fetch(PDO::FETCH_ASSOC);

    if (!$columnExists) {
        // Add the room_id column to the attendance_tbl table
        $alterTableQuery = "ALTER TABLE `attendance_tbl` 
                           ADD COLUMN `room_id` int(11) DEFAULT NULL AFTER `schedule_id`,
                           ADD CONSTRAINT `attendance_tbl_room_fk` 
                           FOREIGN KEY (`room_id`) REFERENCES `room_tbl` (`room_id`) ON DELETE SET NULL";
        
        $conn->exec($alterTableQuery);
        echo "Successfully added room_id column to attendance_tbl.<br>";
        
        // Update existing records to populate room_id from schedule_tbl
        $updateExistingQuery = "UPDATE attendance_tbl a
                               JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                               SET a.room_id = s.room_id
                               WHERE a.room_id IS NULL";
        
        $updateResult = $conn->exec($updateExistingQuery);
        echo "Updated $updateResult existing attendance records with room_id.<br>";
    } else {
        echo "The room_id column already exists in attendance_tbl.<br>";
    }
    
    echo "Database update completed successfully.";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
    error_log("Database Error in db_update.php: " . $e->getMessage());
}
?> 