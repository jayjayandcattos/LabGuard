<?php
session_start();
require_once "backend/db.php";

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

// Update both status and a_status enums
$alterTableSQL = "ALTER TABLE attendance_tbl 
                 MODIFY COLUMN status enum('check_in','check_out','ended','no_schedule') NOT NULL DEFAULT 'ended',
                 MODIFY COLUMN a_status enum('Present','Absent','Late','Ended','No Schedule') NOT NULL";
try {
    $conn->exec($alterTableSQL);
} catch (PDOException $e) {
    error_log("Failed to alter table: " . $e->getMessage());
}

if (!empty($_POST['rfid_tag'])) {
  try {
    $rfid_tag = trim($_POST['rfid_tag']);
    error_log("Received RFID tag: " . $rfid_tag);

    // 🔹 Fetch User (Student or Professor)
    $query = "SELECT student_user_id AS id, lastname, firstname, photo, 'student' AS role, section_id 
                  FROM student_tbl WHERE rfid_tag = ?
                  UNION
                  SELECT prof_user_id AS id, lastname, firstname, photo, 'professor' AS role, NULL AS section_id 
                  FROM prof_tbl WHERE rfid_tag = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$rfid_tag, $rfid_tag]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');

    if (!$user) {
      echo json_encode([
        'success' => false,
        'message' => 'Scanned tag: ' . $rfid_tag . '. Please make sure this card is registered.',
        'time' => date('h:i:s A')
      ]);
      exit();
    }

    $current_time = date('Y-m-d H:i:s');
    $formatted_time = date('h:i:s A', strtotime($current_time));
    $current_day = date('l');

    // 🔹 Check Last Attendance Record
    $check_tap_query = "SELECT * FROM attendance_tbl 
                            WHERE rfid_tag = ? 
                            AND DATE(time_in) = CURDATE()
                            ORDER BY time_in DESC LIMIT 1";
    $check_tap_stmt = $conn->prepare($check_tap_query);
    $check_tap_stmt->execute([$rfid_tag]);
    $existing_tap = $check_tap_stmt->fetch(PDO::FETCH_ASSOC);

    $next_action = ($existing_tap && $existing_tap['time_out'] === null) ? 'check_out' : 'check_in';

    // 🔹 Fetch Schedule before inserting attendance
    $schedule_query = "SELECT s.schedule_id, s.subject_id 
                           FROM schedule_tbl s
                           WHERE s.schedule_day = ? 
                           AND s.schedule_time <= ?
                           AND ADDTIME(s.schedule_time, '03:00:00') >= ?
                           AND s.prof_user_id = ?
                           ORDER BY s.schedule_time DESC LIMIT 1";
    $schedule_stmt = $conn->prepare($schedule_query);
    $current_time_sql = date('H:i:s');
    
    // For professors, check if they have a valid schedule
    if ($user['role'] === 'professor') {
      $schedule_stmt->execute([$current_day, $current_time_sql, $current_time_sql, $user['id']]);
      $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
      
      if (!$schedule) {
        // No valid schedule for professor
        error_log("No schedule found for professor ID: " . $user['id'] . " on " . $current_day . " at " . $current_time_sql);
        
        // Get a default subject for the professor 
        $subject_query = "SELECT subject_id FROM subject_tbl WHERE prof_user_id = ? LIMIT 1";
        $subject_stmt = $conn->prepare($subject_query);
        $subject_stmt->execute([$user['id']]);
        $default_subject = $subject_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$default_subject) {
          // Get any subject if professor doesn't have one
          $any_subject_query = "SELECT subject_id FROM subject_tbl LIMIT 1";
          $any_subject_stmt = $conn->prepare($any_subject_query);
          $any_subject_stmt->execute();
          $default_subject = $any_subject_stmt->fetch(PDO::FETCH_ASSOC);
          
          if (!$default_subject) {
            error_log("No subject found in the database for No Schedule record");
            echo json_encode([
              'success' => false,
              'message' => "System error: No subjects found in database",
              'time' => $formatted_time
            ]);
            exit();
          }
        }
        
        $subject_id = $default_subject['subject_id'];
        
        // For schedule_id, we'll use the first available schedule record
        $schedule_query = "SELECT schedule_id FROM schedule_tbl LIMIT 1";
        $schedule_stmt = $conn->prepare($schedule_query);
        $schedule_stmt->execute();
        $default_schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$default_schedule) {
          error_log("No schedule found in the database for No Schedule record");
          echo json_encode([
            'success' => false,
            'message' => "System error: No schedules found in database",
            'time' => $formatted_time
          ]);
          exit();
        }
        
        $schedule_id = $default_schedule['schedule_id'];
        error_log("Using subject ID: " . $subject_id . " and schedule ID: " . $schedule_id . " for No Schedule record");
        
        $insert = "INSERT INTO attendance_tbl 
                  (prof_id, rfid_tag, subject_id, schedule_id, time_in, status, a_status) 
                  VALUES (?, ?, ?, ?, ?, 'no_schedule', 'No Schedule')";
        $stmt = $conn->prepare($insert);
        $stmt->execute([
          $user['id'],
          $rfid_tag,
          $subject_id,
          $schedule_id,
          $current_time
        ]);
        
        error_log("Inserted 'No Schedule' record for professor. Insert ID: " . $conn->lastInsertId());
        
        echo json_encode([
          'success' => true,
          'message' => "No valid schedule found for check-in.",
          'time' => $formatted_time,
          'user' => $user,
          'status' => 'no_schedule',
          'a_status' => 'No Schedule'
        ]);
        exit();
      }
    } else {
      // For students, check any active schedule
      $student_schedule_query = "SELECT s.schedule_id, s.subject_id 
                             FROM schedule_tbl s
                             JOIN section_tbl sec ON s.section_id = sec.section_id
                             WHERE s.schedule_day = ? 
                             AND s.schedule_time <= ?
                             AND ADDTIME(s.schedule_time, '03:00:00') >= ?
                             AND sec.section_id = ?
                             ORDER BY s.schedule_time DESC LIMIT 1";
      $student_schedule_stmt = $conn->prepare($student_schedule_query);
      $student_schedule_stmt->execute([$current_day, $current_time_sql, $current_time_sql, $user['section_id']]);
      $schedule = $student_schedule_stmt->fetch(PDO::FETCH_ASSOC);

      // If no section-specific schedule found, try to find any active schedule
      if (!$schedule) {
        $any_schedule_query = "SELECT s.schedule_id, s.subject_id 
                               FROM schedule_tbl s
                               WHERE s.schedule_day = ? 
                               AND s.schedule_time <= ?
                               AND ADDTIME(s.schedule_time, '03:00:00') >= ?
                               ORDER BY s.schedule_time DESC LIMIT 1";
        $any_schedule_stmt = $conn->prepare($any_schedule_query);
        $any_schedule_stmt->execute([$current_day, $current_time_sql, $current_time_sql]);
        $schedule = $any_schedule_stmt->fetch(PDO::FETCH_ASSOC);
      }
    }

    // For students, if no schedule found
    if (!$schedule && $user['role'] === 'student') {
      error_log("No schedule found for student ID: " . $user['id'] . " on " . $current_day . " at " . $current_time_sql);
      echo json_encode([
        'success' => false,
        'message' => "No valid schedule found for check-in.",
        'time' => $formatted_time
      ]);
      exit();
    }

    // 🔹 Check Role-Based Attendance Logic
    if ($user['role'] === 'professor') {
      if ($next_action === 'check_in') {
        // ✅ Professor Check-In
        $insert = "INSERT INTO attendance_tbl 
                          (prof_id, subject_id, schedule_id, rfid_tag, time_in, status, a_status) 
                          VALUES (?, ?, ?, ?, ?, 'check_in', 'Present')";
        $stmt = $conn->prepare($insert);
        $stmt->execute([
          $user['id'],
          $schedule['subject_id'],
          $schedule['schedule_id'],
          $rfid_tag,
          $current_time
        ]);

        echo json_encode([
          'success' => true,
          'message' => 'Professor check-in recorded',
          'user' => $user,
          'status' => 'check_in',
          'time' => $formatted_time,
          'a_status' => 'Present'
        ]);
      } else {
        // ✅ Professor Check-Out & Auto Check Out Students
        $update_prof = "UPDATE attendance_tbl 
                                SET time_out = ?, status = 'check_out', a_status = 'Ended' 
                                WHERE prof_id = ? AND DATE(time_in) = CURDATE() AND status = 'check_in'";
        $stmt = $conn->prepare($update_prof);
        $stmt->execute([$current_time, $user['id']]);

        // Get schedule ID from professor's check-in
        $prof_schedule_query = "SELECT schedule_id FROM attendance_tbl 
                                WHERE prof_id = ? AND DATE(time_in) = CURDATE() 
                                ORDER BY time_in DESC LIMIT 1";
        $prof_schedule_stmt = $conn->prepare($prof_schedule_query);
        $prof_schedule_stmt->execute([$user['id']]);
        $prof_schedule = $prof_schedule_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update all student records for this schedule
        if ($prof_schedule) {
            // Modify this query to keep Present status for students who checked in
            $update_students = "UPDATE attendance_tbl 
                                SET time_out = ?, status = 'check_out',
                                    a_status = CASE 
                                        WHEN a_status = 'Present' THEN 'Present'
                                        ELSE 'Absent'
                                    END
                                WHERE student_id IS NOT NULL 
                                AND schedule_id = ? 
                                AND DATE(time_in) = CURDATE() 
                                AND status = 'check_in'";
            $stmt = $conn->prepare($update_students);
            $stmt->execute([$current_time, $prof_schedule['schedule_id']]);
        }

        // Fetch all affected students to return to frontend
        $student_query = "SELECT s.lastname, s.firstname, s.photo, a.time_in 
                         FROM attendance_tbl a 
                         JOIN student_tbl s ON a.student_id = s.student_user_id
                         WHERE a.schedule_id = ? 
                         AND DATE(a.time_in) = CURDATE()";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->execute([$prof_schedule['schedule_id']]);
        $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $checked_out_students = [];
        foreach ($students as $student) {
            $checked_out_students[] = [
                'user' => [
                    'lastname' => $student['lastname'],
                    'firstname' => $student['firstname'],
                    'photo' => $student['photo']
                ],
                'check_in_time' => date('h:i:s A', strtotime($student['time_in'])),
            ];
        }

        echo json_encode([
          'success' => true,
          'message' => 'Professor checked out. All students have been checked out automatically.',
          'user' => $user,
          'status' => 'check_out',
          'time' => $formatted_time,
          'a_status' => 'Ended',
          'checked_out_students' => $checked_out_students
        ]);
      }
      exit();
    }
    if ($user['role'] === 'student') {
      // ✅ Ensure a Professor Has Checked In (Only for check-in)
      if ($next_action === 'check_in') {
        error_log("Student check-in attempt for student ID: " . $user['id']);
        
        // Check if any professor is checked in today
        $active_prof_query = "SELECT 1 FROM attendance_tbl 
                                  WHERE DATE(time_in) = CURDATE() 
                                  AND prof_id IS NOT NULL 
                                  AND (status = 'check_in' OR status = 'no_schedule')
                                  LIMIT 1";
        $prof_stmt = $conn->prepare($active_prof_query);
        $prof_stmt->execute();
        $active_prof = $prof_stmt->fetchColumn();

        error_log("Active professor found: " . ($active_prof ? "Yes" : "No"));

        if (!$active_prof) {
          echo json_encode([
            'success' => false,
            'message' => 'Please wait for your professor to check in first.',
            'time' => $formatted_time,
            'user' => $user
          ]);
          exit();
        }

        // Check if student already has checked in today
        $check_student_query = "SELECT attendance_id FROM attendance_tbl 
                              WHERE student_id = ? AND DATE(time_in) = CURDATE() 
                              AND status = 'check_in'";
        $check_student_stmt = $conn->prepare($check_student_query);
        $check_student_stmt->execute([$user['id']]);
        $already_checked_in = $check_student_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($already_checked_in) {
          error_log("Student already checked in today: " . $user['id']);
          echo json_encode([
            'success' => false,
            'message' => 'You have already checked in today.',
            'time' => $formatted_time,
            'user' => $user
          ]);
          exit();
        }

        // If we don't have a valid schedule at this point, get one from the professor's record
        if (!$schedule) {
          $prof_schedule_query = "SELECT schedule_id, subject_id FROM attendance_tbl 
                                  WHERE prof_id IS NOT NULL AND DATE(time_in) = CURDATE() 
                                  AND status = 'check_in'
                                  ORDER BY time_in DESC LIMIT 1";
          $prof_schedule_stmt = $conn->prepare($prof_schedule_query);
          $prof_schedule_stmt->execute();
          $schedule = $prof_schedule_stmt->fetch(PDO::FETCH_ASSOC);
          
          if (!$schedule) {
            // Use any valid schedule
            $any_schedule_query = "SELECT schedule_id, subject_id FROM schedule_tbl LIMIT 1";
            $any_schedule_stmt = $conn->prepare($any_schedule_query);
            $any_schedule_stmt->execute();
            $schedule = $any_schedule_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$schedule) {
              error_log("No schedule found for student check-in");
              echo json_encode([
                'success' => false,
                'message' => 'No valid schedule found.',
                'time' => $formatted_time,
                'user' => $user
              ]);
              exit();
            }
          }
        }
        
        error_log("Using schedule_id: " . $schedule['schedule_id'] . " and subject_id: " . $schedule['subject_id'] . " for student check-in");

        // ✅ Student Check-In
        $insert = "INSERT INTO attendance_tbl 
                          (student_id, subject_id, schedule_id, rfid_tag, time_in, status, a_status) 
                          VALUES (?, ?, ?, ?, ?, 'check_in', 'Present')";
        $stmt = $conn->prepare($insert);
        $stmt->execute([
          $user['id'],
          $schedule['subject_id'],
          $schedule['schedule_id'],
          $rfid_tag,
          $current_time
        ]);

        $insert_id = $conn->lastInsertId();
        error_log("Student check-in recorded with ID: " . $insert_id);

        echo json_encode([
          'success' => true,
          'message' => 'Student check-in recorded successfully!',
          'user' => $user,
          'status' => 'check_in',
          'time' => $formatted_time,
          'a_status' => 'Present'
        ]);
      } else {
        // ✅ Student Check-Out
        $update = "UPDATE attendance_tbl 
                           SET time_out = ?, status = 'check_out', a_status = 'Ended'
                           WHERE student_id = ? AND DATE(time_in) = CURDATE() AND status = 'check_in'";
        $stmt = $conn->prepare($update);
        $stmt->execute([$current_time, $user['id']]);

        echo json_encode([
          'success' => true,
          'message' => 'Student check-out recorded successfully!',
          'user' => $user,
          'status' => 'check_out',
          'time' => $formatted_time,
          'a_status' => 'Ended'
        ]);
      }
      exit();
    }
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
  <link href="css/error.css" rel="stylesheet">
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
            <div class="table-body">
              <div class="table-row">
              </div>
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
            <div class="table-body">
              <div class="table-row">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/cardhover.js"></script>

</body>

</html>