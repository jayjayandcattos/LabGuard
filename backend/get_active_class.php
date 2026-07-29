<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

try {
    date_default_timezone_set('Asia/Manila');
    $current_time = date('H:i:s');
    $current_day = date('l');
    $current_date = date('Y-m-d');
    
    // First check if there's an active professor checked in for an ongoing class
    $active_prof_query = "SELECT 
        a.attendance_id, a.prof_id, a.schedule_id, a.time_in, a.status, a.a_status,
        p.firstname, p.lastname,
        s.schedule_time, s.schedule_end_time, s.subject_id, s.room_id,
        r.room_number, r.room_name,
        sub.subject_name, sub.subject_code
    FROM attendance_tbl a
    JOIN prof_tbl p ON a.prof_id = p.prof_user_id
    JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
    JOIN room_tbl r ON s.room_id = r.room_id
    JOIN subject_tbl sub ON s.subject_id = sub.subject_id
    WHERE DATE(a.time_in) = CURRENT_DATE
    AND a.status = 'check_in'
    AND a.time_out IS NULL
    AND (a.a_status = 'Present' OR a.a_status = 'Late')
    AND s.schedule_time <= :current_time
    AND (s.schedule_end_time IS NULL OR s.schedule_end_time = '00:00:00' OR s.schedule_end_time > :current_time)
    ORDER BY a.time_in DESC
    LIMIT 1";
    
    $active_prof_stmt = $conn->prepare($active_prof_query);
    $active_prof_stmt->bindParam(':current_time', $current_time);
    $active_prof_stmt->execute();
    $active_class = $active_prof_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($active_class) {
        // Format the time for display
        $start_time = date('h:i A', strtotime($active_class['schedule_time']));
        $end_time = !empty($active_class['schedule_end_time']) && $active_class['schedule_end_time'] != '00:00:00'
            ? date('h:i A', strtotime($active_class['schedule_end_time']))
            : date('h:i A', strtotime('+3 hours', strtotime($active_class['schedule_time']))); // Default 3 hours if not set
        
        echo json_encode([
            'status' => 'success',
            'active' => true,
            'data' => [
                'subject_name' => $active_class['subject_name'],
                'subject_code' => $active_class['subject_code'],
                'professor' => $active_class['lastname'] . ', ' . $active_class['firstname'],
                'room' => 'Room ' . $active_class['room_number'] . ' (' . $active_class['room_name'] . ')',
                'time' => $start_time . ' - ' . $end_time
            ]
        ]);
        exit;
    }
    
    // Check for professors who have tapped in early (up to 30 minutes before schedule time)
    $early_prof_query = "SELECT 
        a.attendance_id, a.prof_id, a.schedule_id, a.time_in, a.status, a.a_status,
        p.firstname, p.lastname,
        s.schedule_time, s.schedule_end_time, s.subject_id, s.room_id,
        r.room_number, r.room_name,
        sub.subject_name, sub.subject_code,
        TIMESTAMPDIFF(MINUTE, :current_time, s.schedule_time) as minutes_until_start
    FROM attendance_tbl a
    JOIN prof_tbl p ON a.prof_id = p.prof_user_id
    JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
    JOIN room_tbl r ON s.room_id = r.room_id
    JOIN subject_tbl sub ON s.subject_id = sub.subject_id
    WHERE DATE(a.time_in) = CURRENT_DATE
    AND a.status = 'check_in'
    AND a.time_out IS NULL
    AND (a.a_status = 'Present' OR a.a_status = 'Late')
    AND :current_time < s.schedule_time
    AND TIMESTAMPDIFF(MINUTE, :current_time, s.schedule_time) <= 30
    ORDER BY minutes_until_start ASC
    LIMIT 1";
    
    $early_prof_stmt = $conn->prepare($early_prof_query);
    $early_prof_stmt->bindParam(':current_time', $current_time);
    $early_prof_stmt->execute();
    $early_class = $early_prof_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($early_class) {
        // Format the time for display
        $start_time = date('h:i A', strtotime($early_class['schedule_time']));
        $end_time = !empty($early_class['schedule_end_time']) && $early_class['schedule_end_time'] != '00:00:00'
            ? date('h:i A', strtotime($early_class['schedule_end_time']))
            : date('h:i A', strtotime('+3 hours', strtotime($early_class['schedule_time']))); // Default 3 hours if not set
        
        // Calculate minutes until start
        $minutes_until = max(0, $early_class['minutes_until_start']);
        $early_tag = $minutes_until > 0 ? " (Starts in $minutes_until min)" : "";
        
        echo json_encode([
            'status' => 'success',
            'active' => true,
            'data' => [
                'subject_name' => $early_class['subject_name'],
                'subject_code' => $early_class['subject_code'],
                'professor' => $early_class['lastname'] . ', ' . $early_class['firstname'] . $early_tag,
                'room' => 'Room ' . $early_class['room_number'] . ' (' . $early_class['room_name'] . ')',
                'time' => $start_time . ' - ' . $end_time
            ]
        ]);
        exit;
    }
    
    // Check for professors with no_schedule status who are also active
    $no_schedule_query = "SELECT 
        a.attendance_id, a.prof_id, a.schedule_id, a.time_in, a.status, a.a_status,
        p.firstname, p.lastname,
        s.schedule_time, s.schedule_end_time, s.subject_id, s.room_id,
        r.room_number, r.room_name,
        sub.subject_name, sub.subject_code
    FROM attendance_tbl a
    JOIN prof_tbl p ON a.prof_id = p.prof_user_id
    JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
    JOIN room_tbl r ON s.room_id = r.room_id
    JOIN subject_tbl sub ON s.subject_id = sub.subject_id
    WHERE DATE(a.time_in) = CURRENT_DATE
    AND a.status = 'no_schedule'
    AND a.time_out IS NULL
    ORDER BY a.time_in DESC
    LIMIT 1";
    
    $no_schedule_stmt = $conn->prepare($no_schedule_query);
    $no_schedule_stmt->execute();
    $no_schedule_class = $no_schedule_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($no_schedule_class) {
        // For no_schedule cases, we'll return a special status rather than showing in the active class display
        echo json_encode([
            'status' => 'success',
            'active' => false,
            'message' => 'Professor present but not in scheduled class',
            'data' => [
                'subject_name' => $no_schedule_class['subject_name'],
                'subject_code' => $no_schedule_class['subject_code'],
                'professor' => $no_schedule_class['lastname'] . ', ' . $no_schedule_class['firstname'] . ' (Unscheduled)',
                'room' => 'Room ' . $no_schedule_class['room_number'] . ' (' . $no_schedule_class['room_name'] . ')',
                'time' => 'No scheduled time'
            ]
        ]);
        exit;
    }
    
    // Check if the professor has already checked out for today's schedule
    $checked_out_query = "SELECT 
        a.schedule_id 
    FROM attendance_tbl a
    WHERE DATE(a.time_in) = CURRENT_DATE
    AND a.prof_id IS NOT NULL 
    AND a.status = 'check_out'
    ORDER BY a.time_in DESC
    LIMIT 1";
    
    $checked_out_stmt = $conn->prepare($checked_out_query);
    $checked_out_stmt->execute();
    $checked_out = $checked_out_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If professor has checked out for this schedule, don't show the class as active
    if ($checked_out) {
        echo json_encode([
            'status' => 'success',
            'active' => false,
            'message' => 'Class has ended'
        ]);
        exit;
    }
    
    // No active class - don't show scheduled classes unless a professor is present
    echo json_encode([
        'status' => 'success',
        'active' => false,
        'message' => 'No active class - waiting for professor'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 