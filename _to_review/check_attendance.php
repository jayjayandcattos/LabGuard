<?php
// Connect to the database
require_once "db.php";

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get the latest 20 attendance records
    $sql = "SELECT a.*, 
            CASE 
                WHEN a.prof_id IS NOT NULL THEN CONCAT(p.lastname, ', ', p.firstname) 
                WHEN a.student_id IS NOT NULL THEN CONCAT(s.lastname, ', ', s.firstname)
                ELSE 'Unknown' 
            END as full_name,
            CASE 
                WHEN a.prof_id IS NOT NULL THEN 'Professor' 
                ELSE 'Student' 
            END as user_role
            FROM attendance_tbl a
            LEFT JOIN prof_tbl p ON a.prof_id = p.prof_user_id
            LEFT JOIN student_tbl s ON a.student_id = s.student_user_id
            ORDER BY a.time_in DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check database enum values
    $statusSql = "SHOW COLUMNS FROM attendance_tbl LIKE 'status'";
    $statusStmt = $conn->prepare($statusSql);
    $statusStmt->execute();
    $statusInfo = $statusStmt->fetch(PDO::FETCH_ASSOC);
    
    $aStatusSql = "SHOW COLUMNS FROM attendance_tbl LIKE 'a_status'";
    $aStatusStmt = $conn->prepare($aStatusSql);
    $aStatusStmt->execute();
    $aStatusInfo = $aStatusStmt->fetch(PDO::FETCH_ASSOC);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Attendance Records</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            th { background-color: #4CAF50; color: white; }
            .no-schedule { background-color: #ffa500; color: white; }
            .present { background-color: #4ade80; color: white; }
            .ended { background-color: #6c757d; color: white; }
        </style>
    </head>
    <body>
        <h1>Attendance Records</h1>
        
        <h2>Database Structure</h2>
        <p><strong>status enum:</strong> <?php echo $statusInfo['Type']; ?></p>
        <p><strong>a_status enum:</strong> <?php echo $aStatusInfo['Type']; ?></p>
        
        <h2>Recent Records</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>A Status</th>
                <th>RFID Tag</th>
            </tr>
            <?php foreach ($records as $record): ?>
            <tr>
                <td><?php echo $record['attendance_id']; ?></td>
                <td><?php echo $record['full_name']; ?></td>
                <td><?php echo $record['user_role']; ?></td>
                <td><?php echo $record['time_in']; ?></td>
                <td><?php echo $record['time_out']; ?></td>
                <td><?php echo $record['status']; ?></td>
                <td class="<?php 
                    if ($record['a_status'] == 'No Schedule') echo 'no-schedule';
                    elseif ($record['a_status'] == 'Present') echo 'present';
                    elseif ($record['a_status'] == 'Ended') echo 'ended';
                ?>"><?php echo $record['a_status']; ?></td>
                <td><?php echo $record['rfid_tag']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </body>
    </html>
    <?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 