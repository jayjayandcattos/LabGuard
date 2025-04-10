<?php
session_start();
require_once "db.php";

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Debug settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

// Test database connection and update structure
try {
    $test = $conn->query("SELECT 1");
    error_log("Database connection successful");
    
    // First check if columns exist before modifying
    $check_columns = $conn->query("SHOW COLUMNS FROM attendance_tbl");
    $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
    
    // Add prof_id if it doesn't exist
    if (!in_array('prof_id', $columns)) {
        $conn->exec("ALTER TABLE attendance_tbl ADD COLUMN prof_id INT NULL AFTER student_id");
        error_log("Added prof_id column");
    }
    
    // Update status enum
    $conn->exec("ALTER TABLE attendance_tbl MODIFY COLUMN status ENUM('Present', 'Absent', 'Late', 'check_in', 'check_out') NOT NULL DEFAULT 'Absent'");
    error_log("Updated status column");
    
    // Make student_id nullable
    $conn->exec("ALTER TABLE attendance_tbl MODIFY COLUMN student_id INT NULL");
    error_log("Modified student_id to allow NULL");
    
    // Check if foreign key exists and add if it doesn't
    $check_fk = $conn->query("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                             WHERE TABLE_NAME = 'attendance_tbl' 
                             AND COLUMN_NAME = 'prof_id' 
                             AND CONSTRAINT_NAME != 'PRIMARY'");
    if ($check_fk->fetchColumn() == 0) {
        $conn->exec("ALTER TABLE attendance_tbl ADD FOREIGN KEY (prof_id) REFERENCES prof_tbl(prof_user_id) ON DELETE CASCADE");
        error_log("Added foreign key for prof_id");
    }
    
} catch (Exception $e) {
    error_log("Database structure update failed: " . $e->getMessage());
}

// Handle RFID scan
if (isset($_POST['rfid_tag'])) {
    try {
        $rfid_tag = trim($_POST['rfid_tag']);
        error_log("Received RFID tag: " . $rfid_tag);
        
        // First check student_tbl
        $query = "SELECT student_user_id as id, lastname, firstname, photo, 'student' as role, section_id 
                 FROM student_tbl WHERE rfid_tag = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$rfid_tag]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Student query result: " . print_r($user, true));

        // If not found in student_tbl, check prof_tbl
        if (!$user) {
            $query = "SELECT prof_user_id as id, lastname, firstname, photo, 'professor' as role 
                     FROM prof_tbl WHERE rfid_tag = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$rfid_tag]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Professor query result: " . print_r($user, true));
        }

        header('Content-Type: application/json');
        
        if ($user) {
            $current_time = date('Y-m-d H:i:s');
            $formatted_time = date('h:i:s A', strtotime($current_time));

            if ($user['role'] === 'student') {
                // Check if there's an active professor session for the student's section
                $active_prof_query = "SELECT a.*, s.section_id 
                                    FROM attendance_tbl a 
                                    JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                                    WHERE s.section_id = ? 
                                    AND a.prof_id IS NOT NULL 
                                    AND DATE(a.time_in) = CURDATE()
                                    AND a.status = 'check_in'                                   
                                    AND NOT EXISTS (
                                        SELECT 1 FROM attendance_tbl 
                                        WHERE prof_id = a.prof_id 
                                        AND schedule_id = a.schedule_id
                                        AND status = 'check_out' 
                                        AND time_out > a.time_out
                                    )";
                $prof_stmt = $conn->prepare($active_prof_query);
                $prof_stmt->execute([$user['section_id']]);
                $active_prof = $prof_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$active_prof) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Please wait for your professor to check in first.',
                        'time' => $formatted_time
                    ]);
                    exit();
                }
            }

            // Check for existing tap today
            $check_tap_query = "SELECT * FROM attendance_tbl 
                              WHERE rfid_tag = ? 
                              AND DATE(time_in) = CURDATE()
                              ORDER BY time_in DESC LIMIT 1";
            $check_tap_stmt = $conn->prepare($check_tap_query);
            $check_tap_stmt->execute([$rfid_tag]);
            $existing_tap = $check_tap_stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Existing tap check result: " . print_r($existing_tap, true));
            
            // Determine next action based on last tap
            $next_action = (!$existing_tap || $existing_tap['status'] === 'check_out') ? 'check_in' : 'check_out';
            error_log("Next action determined: " . $next_action);
            
            if ($user['role'] === 'student') {
                if ($next_action === 'check_in') {
                    // First get the student's section
                    $student_section_query = "SELECT st.section_id, s.section_name 
                                           FROM student_tbl st 
                                           JOIN section_tbl s ON st.section_id = s.section_id 
                                           WHERE st.student_user_id = ?";
                    $section_stmt = $conn->prepare($student_section_query);
                    $section_stmt->execute([$user['id']]);
                    $student_section = $section_stmt->fetch(PDO::FETCH_ASSOC);

                    // Get current schedule with more detailed information
                    $schedule_query = "SELECT s.schedule_id, s.subject_id, s.schedule_time, s.time_out,
                                            s.section_id, sec.section_name,
                                            sub.subject_code, sub.subject_name
                                     FROM schedule_tbl s
                                     JOIN subject_tbl sub ON s.subject_id = sub.subject_id
                                     JOIN section_tbl sec ON s.section_id = sec.section_id
                                     WHERE s.schedule_day = ? 
                                     AND TIME(?) BETWEEN s.schedule_time AND s.time_out";
                    $schedule_stmt = $conn->prepare($schedule_query);
                    $current_day = date('l');
                    $schedule_stmt->execute([$current_day, $current_time]);
                    $available_schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Check if there are any active schedules
                    if ($available_schedules) {
                        // Find schedule for student's section
                        $schedule = null;
                        foreach ($available_schedules as $sch) {
                            if ($sch['section_id'] == $student_section['section_id']) {
                                $schedule = $sch;
                                break;
                            }
                        }

                        if ($schedule) {
                            // Insert new attendance record (check-in)
                            $insert = "INSERT INTO attendance_tbl 
                                      (student_id, prof_id, subject_id, schedule_id, rfid_tag, time_in, status, time_out) 
                                      VALUES (?, NULL, ?, ?, ?, ?, 'check_in', ?)";
                            $stmt = $conn->prepare($insert);
                            $stmt->execute([
                                $user['id'],
                                $schedule['subject_id'],
                                $schedule['schedule_id'],
                                $rfid_tag,
                                $current_time
                            ]);
                            $message = "Check-in recorded for {$schedule['subject_code']} - {$schedule['subject_name']}";
                            $status = 'check_in';
                        } else {
                            // There are active schedules but not for this student's section
                            $active_sections = array_map(function($sch) {
                                return $sch['section_name'];
                            }, $available_schedules);
                            $active_sections = implode(', ', $active_sections);
                            
                            error_log("Wrong section for student ID: {$user['id']} (Section: {$student_section['section_name']}, Active sections: {$active_sections})");
                            $message = "You are not enrolled in the current active class. Active class is for section(s): {$active_sections}";
                            $status = 'error';
                        }
                    } else {
                        error_log("No schedule found for student ID: {$user['id']} on {$current_day} at {$current_time}");
                        $message = "No active schedule found for {$user['lastname']}, {$user['firstname']} at " . date('h:i A');
                        $status = 'error';
                    }
                } else {
                    // For check-out, use the same schedule as check-in
                    $schedule_query = "SELECT s.schedule_id, s.subject_id, sub.subject_code, sub.subject_name
                                     FROM schedule_tbl s
                                     JOIN subject_tbl sub ON s.subject_id = sub.subject_id
                                     WHERE s.schedule_id = ?";
                    $schedule_stmt = $conn->prepare($schedule_query);
                    $schedule_stmt->execute([$existing_tap['schedule_id']]);
                    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

                    // Allow check-out regardless of schedule
                    $insert = "INSERT INTO attendance_tbl 
                             (student_id, prof_id, subject_id, schedule_id, rfid_tag, time_in, status, check_out) 
                             VALUES (?, NULL, ?, ?, ?, ?, 'check_out', ?)";
                    $stmt = $conn->prepare($insert);
                    $stmt->execute([
                        $user['id'],
                        $existing_tap['subject_id'],
                        $existing_tap['schedule_id'],
                        $rfid_tag,
                        $current_time
                    ]);
                    $message = "Check-out recorded for {$user['lastname']}, {$user['firstname']} at " . date('h:i A');
                    $status = 'check_out';
                }
            } else {
                // For professors, similar logic
                if ($next_action === 'check_in') {
                    // Get current schedule for check-in
                    $schedule_query = "SELECT s.schedule_id, s.subject_id, s.schedule_time, s.time_out,
                                            sub.subject_code, sub.subject_name
                                     FROM schedule_tbl s
                                     JOIN subject_tbl sub ON s.subject_id = sub.subject_id
                                     WHERE s.prof_user_id = ? 
                                     AND s.schedule_day = ? 
                                     AND TIME(?) BETWEEN s.schedule_time AND s.time_out";
                    $schedule_stmt = $conn->prepare($schedule_query);
                    $current_day = date('l');
                    $schedule_stmt->execute([$user['id'], $current_day, $current_time]);
                    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($schedule) {
                        // Insert new record for professor (check-in)
                        $insert = "INSERT INTO attendance_tbl 
                                  (student_id, prof_id, subject_id, schedule_id, rfid_tag, time_in, status, time_out) 
                                  VALUES (NULL, ?, ?, ?, ?, ?, 'check_in', ?)";
                        $stmt = $conn->prepare($insert);
                        $stmt->execute([
                            $user['id'],
                            $schedule['subject_id'],
                            $schedule['schedule_id'],
                            $rfid_tag,
                            $current_time
                        ]);
                        $message = "Professor check-in recorded for {$schedule['subject_code']} - {$schedule['subject_name']}";
                        $status = 'check_in';
                    } else {
                        error_log("No schedule found for professor ID: {$user['id']} on {$current_day} at {$current_time}");
                        $message = "No active schedule found for Prof. {$user['lastname']}, {$user['firstname']} at " . date('h:i A');
                        $status = 'error';
                    }
                } else {
                    // For professors checking out
                    // First get the schedule info from the previous check-in
                    $schedule_query = "SELECT s.schedule_id, s.subject_id, s.section_id, sub.subject_code, sub.subject_name
                                     FROM schedule_tbl s
                                     JOIN subject_tbl sub ON s.subject_id = sub.subject_id
                                     WHERE s.schedule_id = ?";
                    $schedule_stmt = $conn->prepare($schedule_query);
                    $schedule_stmt->execute([$existing_tap['schedule_id']]);
                    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($schedule) {
                        try {
                            // Start transaction
                            $conn->beginTransaction();
                            error_log("Transaction started for professor check-out");

                            // First check out the professor
                            $insert = "INSERT INTO attendance_tbl 
                                     (student_id, prof_id, subject_id, schedule_id, rfid_tag, time_in, status, time_out) 
                                     VALUES (NULL, ?, ?, ?, ?, ?, 'check_out', ?)";
                            $stmt = $conn->prepare($insert);
                            $stmt->execute([
                                $user['id'],
                                $schedule['subject_id'],
                                $schedule['schedule_id'],
                                $rfid_tag,
                                $current_time
                            ]);
                            error_log("Professor check-out record inserted");

                            // Get all students in this section
                            $section_students_query = "SELECT s.student_user_id, s.lastname, s.firstname, s.photo, s.rfid_tag
                                                     FROM student_tbl s
                                                     WHERE s.section_id = ?";
                            $section_stmt = $conn->prepare($section_students_query);
                            $section_stmt->execute([$schedule['section_id']]);
                            $section_students = $section_stmt->fetchAll(PDO::FETCH_ASSOC);
                            error_log("Found " . count($section_students) . " students in section");

                            // Check out each student
                            foreach ($section_students as $student) {
                                // Check if student was already checked out
                                $check_existing = "SELECT * FROM attendance_tbl 
                                                 WHERE student_id = ? 
                                                 AND schedule_id = ? 
                                                 AND DATE(time_out) = CURDATE()
                                                 AND status = 'check_out'";
                                $check_stmt = $conn->prepare($check_existing);
                                $check_stmt->execute([$student['student_user_id'], $schedule['schedule_id']]);
                                $already_checked_out = $check_stmt->fetch();

                                if (!$already_checked_out) {
                                    $insert = "INSERT INTO attendance_tbl 
                                             (student_id, prof_id, subject_id, schedule_id, rfid_tag, time_in, status, time_out) 
                                             VALUES (?, NULL, ?, ?, ?, ?, 'check_out', ?)";
                                    $stmt = $conn->prepare($insert);
                                    $stmt->execute([
                                        $student['student_user_id'],
                                        $schedule['subject_id'],
                                        $schedule['schedule_id'],
                                        $student['rfid_tag'],
                                        $current_time
                                    ]);
                                    error_log("Checked out student ID: " . $student['student_user_id']);
                                }
                            }

                            // Commit transaction
                            $conn->commit();
                            error_log("Transaction committed successfully");

                            $student_count = count($section_students);
                            $message = "Professor check-out recorded for {$schedule['subject_code']} - {$schedule['subject_name']}. " . 
                                     "Checked out all {$student_count} student(s) in section.";
                            $status = 'check_out';

                            // Include all section students in the response
                            $checked_out_students = array_map(function($student) use ($current_time) {
                                return [
                                    'user' => [
                                        'id' => $student['student_user_id'],
                                        'lastname' => $student['lastname'],
                                        'firstname' => $student['firstname'],
                                        'photo' => $student['photo'],
                                        'role' => 'student'
                                    ],
                                    'status' => 'check_out',
                                    'time' => date('h:i:s A', strtotime($current_time)),
                                    'message' => "Automatically checked out by professor"
                                ];
                            }, $section_students);

                        } catch (Exception $e) {
                            // Rollback transaction on error
                            if ($conn->inTransaction()) {
                                $conn->rollBack();
                                error_log("Transaction rolled back due to error");
                            }
                            error_log("Error during mass check-out: " . $e->getMessage());
                            throw $e;
                        }
                    } else {
                        $message = "Professor check-out recorded for Prof. {$user['lastname']}, {$user['firstname']} at " . date('h:i A');
                        $status = 'check_out';
                    }
                }
            }

            // Always set a status and message
            if (!isset($status)) {
                $status = 'error';
                $message = "Unable to determine attendance status for {$user['lastname']}, {$user['firstname']}";
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'user' => $user,
                'status' => $status,
                'time' => $formatted_time,
                'checked_out_students' => $checked_out_students ?? []
            ]);
        } else {
            $current_time = date('Y-m-d H:i:s');
            $formatted_time = date('h:i:s A', strtotime($current_time));
            echo json_encode([
                'success' => false,
                'message' => 'Scanned tag: ' . $rfid_tag . '. Please make sure this card is registered.',
                'time' => $formatted_time
            ]);
        }
        exit();
    } catch (Exception $e) {
        error_log("Error processing RFID: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error processing request: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Attendance System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-container {
            display: flex;
            gap: 30px;
            padding: 20px;
            max-width: 1200px;
            margin: 50px auto;
        }
        .scanner-container {
            flex: 1;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .tap-history-container {
            flex: 1;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-height: 800px;
            overflow-y: auto;
        }
        .tap-history-section {
            margin-bottom: 30px;
        }
        .tap-history-section h3 {
            color: #444;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .tap-history-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .result-container {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 1.2em;
            opacity: 0.5;
            padding: 0 5px;
        }
        .close-btn:hover {
            opacity: 1;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .login-btn {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        .student-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
            display: block;
        }
        .clock {
            font-size: 2em;
            text-align: center;
            margin-bottom: 20px;
        }
        .tap-history-table th, 
        .tap-history-table td {
            padding: 8px;
            text-align: left;
        }
        .tap-history-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .tap-history-table img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .table-secondary {
            background-color: #f8f9fa;
        }
        .table-secondary td {
            color: #6c757d;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 0.85em;
        }
        .tap-history-table td {
            vertical-align: middle;
        }
        .bg-secondary.text-white {
            background-color: #6c757d !important;
            color: white !important;
        }
        .table-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
        }
        .table-secondary td {
            color: #6c757d;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 0.85em;
        }
        .tap-history-table td {
            vertical-align: middle;
        }
        .badge {
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="main-container">
        <div class="scanner-container text-center">
            <h2 class="mb-4">RFID Attendance Scanner</h2>
            
            <div class="clock" id="clock">00:00:00</div>

            <div class="form-group">
                <input type="text" id="rfid_input" class="form-control form-control-lg" 
                       placeholder="Scan RFID Card" autofocus>
            </div>

            <div id="result" class="result-container">
                <button class="close-btn" onclick="closeResult()">&times;</button>
                <img id="student_photo" class="student-photo" style="display: none;">
                <h4 id="student_name"></h4>
                <p id="student_id"></p>
                <p id="message"></p>
            </div>
        </div>

        <div class="tap-history-container">
            <h2 class="mb-4">Recent Taps</h2>
            
            <div class="tap-history-section">
                <h3>Professors</h3>
                <table class="tap-history-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="professor-tap-history">
                        <!-- Professor tap history will be populated here -->
                    </tbody>
                </table>
            </div>

            <div class="tap-history-section">
                <h3>Students</h3>
                <table class="tap-history-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="student-tap-history">
                        <!-- Student tap history will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Simple clock update using system time
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('clock').textContent = timeString;
        }
        
        setInterval(updateClock, 1000);
        updateClock();

        // Handle RFID input
        const rfidInput = document.getElementById('rfid_input');
        const resultDiv = document.getElementById('result');
        const studentPhoto = document.getElementById('student_photo');
        const studentName = document.getElementById('student_name');
        const studentId = document.getElementById('student_id');
        const message = document.getElementById('message');
        const professorTapHistory = document.getElementById('professor-tap-history');
        const studentTapHistory = document.getElementById('student-tap-history');

        // Function to add tap to history
        function addTapToHistory(data) {
            const row = document.createElement('tr');
            let statusText = data.status === 'check_in' ? 'Check In' : 'Check Out';
            let statusClass = data.status === 'check_in' ? 'bg-success' : 'bg-warning';
            let rowClass = '';
            
            // Handle error cases and wrong section
            if (data.status === 'error') {
                statusClass = 'bg-secondary text-white';
                rowClass = 'table-secondary';
                
                if (data.message.includes('not enrolled in the current active class')) {
                    statusText = 'Wrong Section';
                } else if (data.message.includes('No active schedule')) {
                    statusText = 'No Schedule';
                } else {
                    statusText = 'Error';
                }
            }
            
            row.className = rowClass;
            row.innerHTML = `
                <td><img src="uploads/${data.user?.photo || 'default.jpg'}" alt="User photo"></td>
                <td>
                    ${data.user?.lastname || 'Unknown'}, ${data.user?.firstname || ''}
                    ${data.status === 'error' ? `<br><small class="text-muted">${data.message}</small>` : ''}
                </td>
                <td>${data.status === 'check_in' ? data.time : '-'}</td>
                <td>${data.status === 'check_out' ? data.time : '-'}</td>
                <td><span class="badge ${statusClass}" style="font-size: 0.85em; padding: 0.5em 0.7em;">${statusText}</span></td>
            `;

            // Add data-id attribute to identify the row
            row.setAttribute('data-id', data.user?.id);
            
            // Add to appropriate history table based on role
            if (data.user?.role === 'professor') {
                // Check if professor already exists in history
                const existingRow = professorTapHistory.querySelector(`tr[data-id="${data.user.id}"]`);
                if (existingRow) {
                    // Update existing row while preserving the other time
                    const existingCheckIn = existingRow.querySelector('td:nth-child(3)').textContent;
                    const existingCheckOut = existingRow.querySelector('td:nth-child(4)').textContent;
                    
                    const newRow = document.createElement('tr');
                    newRow.className = row.className;
                    newRow.setAttribute('data-id', data.user.id);
                    newRow.innerHTML = `
                        <td><img src="uploads/${data.user.photo || 'default.jpg'}" alt="User photo"></td>
                        <td>${data.user.lastname}, ${data.user.firstname}</td>
                        <td>${data.status === 'check_in' ? data.time : existingCheckIn}</td>
                        <td>${data.status === 'check_out' ? data.time : existingCheckOut}</td>
                        <td><span class="badge ${statusClass}" style="font-size: 0.85em; padding: 0.5em 0.7em;">${statusText}</span></td>
                    `;
                    existingRow.replaceWith(newRow);
                } else {
                    // Add new row
                    professorTapHistory.insertBefore(row, professorTapHistory.firstChild);
                    if (professorTapHistory.children.length > 5) {
                        professorTapHistory.removeChild(professorTapHistory.lastChild);
                    }
                }

                // If this is a professor check-out and there are checked out students
                if (data.status === 'check_out' && data.checked_out_students && data.checked_out_students.length > 0) {
                    // Update each student's status in the history
                    data.checked_out_students.forEach(studentData => {
                        const studentRow = document.createElement('tr');
                        studentRow.setAttribute('data-id', studentData.user.id);
                        studentRow.innerHTML = `
                            <td><img src="uploads/${studentData.user.photo || 'default.jpg'}" alt="User photo"></td>
                            <td>${studentData.user.lastname}, ${studentData.user.firstname}</td>
                            <td>-</td>
                            <td>${studentData.time}</td>
                            <td><span class="badge bg-warning" style="font-size: 0.85em; padding: 0.5em 0.7em;">Check Out</span></td>
                        `;
                        
                        // Check if student already exists in history
                        const existingStudentRow = studentTapHistory.querySelector(`tr[data-id="${studentData.user.id}"]`);
                        if (existingStudentRow) {
                            // Update existing row while preserving the check-in time
                            const existingCheckIn = existingStudentRow.querySelector('td:nth-child(3)').textContent;
                            studentRow.querySelector('td:nth-child(3)').textContent = existingCheckIn;
                            existingStudentRow.replaceWith(studentRow);
                        } else {
                            // Add new row at the top
                            studentTapHistory.insertBefore(studentRow, studentTapHistory.firstChild);
                            if (studentTapHistory.children.length > 5) {
                                studentTapHistory.removeChild(studentTapHistory.lastChild);
                            }
                        }
                    });
                }
            } else if (data.user?.role === 'student') {
                // Similar logic for students
                const existingRow = studentTapHistory.querySelector(`tr[data-id="${data.user.id}"]`);
                if (existingRow) {
                    // Update existing row while preserving the other time
                    const existingCheckIn = existingRow.querySelector('td:nth-child(3)').textContent;
                    const existingCheckOut = existingRow.querySelector('td:nth-child(4)').textContent;
                    
                    const newRow = document.createElement('tr');
                    newRow.className = row.className;
                    newRow.setAttribute('data-id', data.user.id);
                    newRow.innerHTML = `
                        <td><img src="uploads/${data.user.photo || 'default.jpg'}" alt="User photo"></td>
                        <td>${data.user.lastname}, ${data.user.firstname}</td>
                        <td>${data.status === 'check_in' ? data.time : existingCheckIn}</td>
                        <td>${data.status === 'check_out' ? data.time : existingCheckOut}</td>
                        <td><span class="badge ${statusClass}" style="font-size: 0.85em; padding: 0.5em 0.7em;">${statusText}</span></td>
                    `;
                    existingRow.replaceWith(newRow);
                } else {
                    // Add new row
                    studentTapHistory.insertBefore(row, studentTapHistory.firstChild);
                    if (studentTapHistory.children.length > 5) {
                        studentTapHistory.removeChild(studentTapHistory.lastChild);
                    }
                }
            }
        }

        function closeResult() {
            document.getElementById('result').style.display = 'none';
        }

        rfidInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const rfid_tag = this.value;
                
                fetch('main_dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'rfid_tag=' + encodeURIComponent(rfid_tag)
                })
                .then(response => response.json())
                .then(data => {
                    resultDiv.style.display = 'block';
                    
                    if (data.success) {
                        resultDiv.className = 'result-container success';
                        studentPhoto.style.display = 'block';
                        studentPhoto.src = 'uploads/' + data.user.photo;
                        studentName.textContent = data.user.lastname + ', ' + data.user.firstname;
                        studentId.textContent = data.user.role + ' ID: ' + data.user.id;
                        
                        // Show entry/exit status and time
                        const statusText = data.status === 'check_in' ? 'Check In' : 'Check Out';
                        message.textContent = `${data.message} (${statusText} at ${data.time})`;
                    } else {
                        resultDiv.className = 'result-container error';
                        studentPhoto.style.display = 'none';
                        studentName.textContent = '';
                        studentId.textContent = '';
                        message.textContent = data.message;
                    }

                    // Add to tap history
                    addTapToHistory(data);

                    // Clear input but don't auto-hide the result
                    this.value = '';
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'result-container error';
                    message.textContent = 'Error processing request';
                });
            }
        });

        // Auto-focus input
        document.addEventListener('click', function() {
            rfidInput.focus();
        });
    </script>
</body>
</html> 