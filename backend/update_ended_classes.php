<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    date_default_timezone_set('Asia/Manila');
    $current_time = date('H:i:s');
    
    // Log current time for debugging
    error_log("Running update_ended_classes.php at current time: " . $current_time);
    
    // Find professors with active check-ins whose classes have ended
    $find_ended_query = "SELECT 
                            a.attendance_id, a.prof_id, a.schedule_id,
                            p.firstname, p.lastname,
                            s.schedule_time, s.schedule_end_time
                        FROM attendance_tbl a
                        JOIN prof_tbl p ON a.prof_id = p.prof_user_id
                        JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                        WHERE DATE(a.time_in) = CURDATE()
                        AND a.status = 'check_in'
                        AND a.time_out IS NULL
                        AND s.schedule_end_time IS NOT NULL
                        AND s.schedule_end_time != '00:00:00'
                        AND s.schedule_end_time < ?";
    
    $find_stmt = $conn->prepare($find_ended_query);
    $find_stmt->execute([$current_time]);
    $ended_classes = $find_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($ended_classes) . " professors with ended classes");
    
    if (count($ended_classes) > 0) {
        // Update all professors with ended classes to "No Schedule" status
        $update_ended_classes = "UPDATE attendance_tbl a
                                JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                                SET a.status = 'no_schedule', a.a_status = 'No Schedule'
                                WHERE a.prof_id IS NOT NULL
                                AND a.status = 'check_in'
                                AND a.time_out IS NULL
                                AND DATE(a.time_in) = CURDATE()
                                AND s.schedule_end_time IS NOT NULL
                                AND s.schedule_end_time != '00:00:00'
                                AND s.schedule_end_time < ?";
        $ended_stmt = $conn->prepare($update_ended_classes);
        $ended_stmt->execute([$current_time]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Updated ' . count($ended_classes) . ' professors with ended classes',
            'updated_professors' => $ended_classes
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'No ended classes found that need updates'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 