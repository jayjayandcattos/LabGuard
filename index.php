<?php
session_start();
require_once "backend/db.php";

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

if (isset($_POST['rfid_tag'])) {
    try {
        $rfid_tag = trim($_POST['rfid_tag']);
        error_log("Received RFID tag: " . $rfid_tag);
        
        $query = "SELECT student_user_id as id, lastname, firstname, photo, 'student' as role, section_id 
                 FROM student_tbl WHERE rfid_tag = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$rfid_tag]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Student query result: " . print_r($user, true));
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
            
                $active_prof_query = "SELECT a.*, s.section_id 
                                    FROM attendance_tbl a 
                                    JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                                    WHERE s.section_id = ? 
                                    AND a.prof_id IS NOT NULL 
                                    AND DATE(a.timestamp) = CURDATE()
                                    AND a.status = 'check_in'
                                    AND NOT EXISTS (
                                        SELECT 1 FROM attendance_tbl 
                                        WHERE prof_id = a.prof_id 
                                        AND schedule_id = a.schedule_id
                                        AND status = 'check_out' 
                                        AND timestamp > a.timestamp
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
            $check_tap_query = "SELECT * FROM attendance_tbl 
                              WHERE rfid_tag = ? 
                              AND DATE(timestamp) = CURDATE()
                              ORDER BY timestamp DESC LIMIT 1";
            $check_tap_stmt = $conn->prepare($check_tap_query);
            $check_tap_stmt->execute([$rfid_tag]);
            $existing_tap = $check_tap_stmt->fetch(PDO::FETCH_ASSOC);
            

            $next_action = (!$existing_tap || $existing_tap['status'] === 'check_out') ? 'check_in' : 'check_out';
            
            if ($user['role'] === 'student') {
                if ($next_action === 'check_in') {
            
                    $schedule_query = "SELECT s.schedule_id, s.subject_id, s.schedule_time, s.time_out,
                                            s.section_id, sec.section_name,
                                            sub.subject_code, sub.subject_name
                                     FROM schedule_tbl s
                                     JOIN subject_tbl sub ON s.subject_id = sub.subject_id
                                     JOIN section_tbl sec ON s.section_id = sec.section_id
                                     WHERE s.section_id = ? 
                                     AND s.schedule_day = ? 
                                     AND TIME(?) BETWEEN s.schedule_time AND s.time_out";
                    $schedule_stmt = $conn->prepare($schedule_query);
                    $current_day = date('l');
                    $schedule_stmt->execute([$user['section_id'], $current_day, $current_time]);
                    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($schedule) {
            
                        $insert = "INSERT INTO attendance_tbl 
                                  (student_id, prof_id, subject_id, schedule_id, rfid_tag, status, timestamp) 
                                  VALUES (?, NULL, ?, ?, ?, 'check_in', ?)";
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
                        $message = "No active schedule found for {$user['lastname']}, {$user['firstname']} at " . date('h:i A');
                        $status = 'error';
                    }
                } else {
      
                    $insert = "INSERT INTO attendance_tbl 
                             (student_id, prof_id, subject_id, schedule_id, rfid_tag, status, timestamp) 
                             VALUES (?, NULL, ?, ?, ?, 'check_out', ?)";
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
    
                if ($next_action === 'check_in') {
          
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
                        $insert = "INSERT INTO attendance_tbl 
                                  (student_id, prof_id, subject_id, schedule_id, rfid_tag, status, timestamp) 
                                  VALUES (NULL, ?, ?, ?, ?, 'check_in', ?)";
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
                        $message = "No active schedule found for Prof. {$user['lastname']}, {$user['firstname']} at " . date('h:i A');
                        $status = 'error';
                    }
                } else {
    
                    $insert = "INSERT INTO attendance_tbl 
                             (student_id, prof_id, subject_id, schedule_id, rfid_tag, status, timestamp) 
                             VALUES (NULL, ?, ?, ?, ?, 'check_out', ?)";
                    $stmt = $conn->prepare($insert);
                    $stmt->execute([
                        $user['id'],
                        $existing_tap['subject_id'],
                        $existing_tap['schedule_id'],
                        $rfid_tag,
                        $current_time
                    ]);

                  
                    $get_students_query = "SELECT s.student_user_id, s.lastname, s.firstname, s.photo, s.rfid_tag, sec.section_id
                                         FROM schedule_tbl sch
                                         JOIN section_tbl sec ON sch.section_id = sec.section_id
                                         JOIN student_tbl s ON s.section_id = sec.section_id
                                         WHERE sch.schedule_id = ?";
                    $students_stmt = $conn->prepare($get_students_query);
                    $students_stmt->execute([$existing_tap['schedule_id']]);
                    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

                    $checked_out_students = [];
                    foreach ($students as $student) {
                      
                        $insert_student = "INSERT INTO attendance_tbl 
                                         (student_id, prof_id, subject_id, schedule_id, rfid_tag, status, timestamp) 
                                         VALUES (?, NULL, ?, ?, ?, 'check_out', ?)";
                        $stmt = $conn->prepare($insert_student);
                        $stmt->execute([
                            $student['student_user_id'],
                            $existing_tap['subject_id'],
                            $existing_tap['schedule_id'],
                            $student['rfid_tag'],
                            $current_time
                        ]);

                        $checked_out_students[] = [
                            'id' => $student['student_user_id'],
                            'lastname' => $student['lastname'],
                            'firstname' => $student['firstname'],
                            'photo' => $student['photo'],
                            'role' => 'student'
                        ];
                    }

                    $message = "Professor check-out recorded for Prof. {$user['lastname']}, {$user['firstname']} at " . date('h:i A');
                    $status = 'check_out';
                }
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
            echo json_encode([
                'success' => false,
                'message' => 'Scanned tag: ' . $rfid_tag . '. Please make sure this card is registered.',
                'time' => date('h:i:s A')
            ]);
        }
        exit();
    } catch (Exception $e) {
        error_log("Error processing RFID: " . $e->getMessage());
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
  <title>LabGuard</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="js/time.js" defer></script>
  <script src="js/loadingtransition.js" defer></script>
  <script src="js/description.js" defer></script>
  <script src="js/tap.js" defer></script>
  <link href="css/tailwind.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
  <link rel="icon" href="assets/IDtap.svg" type="image/x-icon">
</head>


<body>

  <?php include 'sections/nav.php'; ?>

  <div class="content-container">
    <h2>WELCOME TO LABGUARD</h2>
    <div class="white-line"></div>
    <div id="description">
      STUDENTS CAN ONLY LOG THEIR ATTENDANCE WHEN YOUR PROFESSOR IS PRESENT.
    </div>
    <div class="white-line"></div>
  </div>
  <div class="wrapper">
    <div class="scan-container">
      <img src="assets/IDtap.svg" alt="Scan ID" class="scan-image">
      <h2>PLEASE SCAN YOUR ID.</h2>
    </div>
    <div class="right-rectangle">
      <h2>RECENT TAPS</h2>
      <div class="recent-taps-content">
        <div class="section">
          <div class="table">
            <h1>PROFESSOR</h1>
            <div class="table-header">
              <span>PHOTO</span>
              <span>NAME</span>
              <span>CHECK IN</span>
              <span>CHECK OUT</span>
              <span>STATUS</span>
            </div>
            <div class="table-row">
            </div>
          </div>
        </div>
        <div class="section">
          <div class="table">
          <h1>STUDENTS</h1>
            <div class="table-header">
            <span>PHOTO</span>
              <span>NAME</span>
              <span>CHECK IN</span>
              <span>CHECK OUT</span>
              <span>STATUS</span>
            </div>
            <div class="table-row">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>

</html>