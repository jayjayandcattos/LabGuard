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
                 MODIFY COLUMN status enum('check_in','check_out','ended','no_schedule','wrong_class') NOT NULL DEFAULT 'ended',
                 MODIFY COLUMN a_status enum('Present','Absent','Late','Ended','No Schedule','Wrong Class') NOT NULL";
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
    $current_time_sql = date('H:i:s');

    // Update any active professors with ended schedules to "No Schedule" status
    // This is done for every tap to ensure the database is always up-to-date
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
    $ended_stmt->execute([$current_time_sql]);

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
    $schedule_query = "SELECT s.schedule_id, s.subject_id, s.section_id, s.room_id, s.schedule_time
                           FROM schedule_tbl s
                           WHERE s.schedule_day = ? 
                           AND TIME(ADDTIME(s.schedule_time, '-00:30:00')) <= ?
                           AND ADDTIME(s.schedule_time, '03:30:00') >= ?
                           AND s.prof_user_id = ?
                           ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(s.schedule_time, ?))) ASC LIMIT 1";
    $schedule_stmt = $conn->prepare($schedule_query);

    // For professors, check if they have a valid schedule
    if ($user['role'] === 'professor') {
      $schedule_stmt->execute([$current_day, $current_time_sql, $current_time_sql, $user['id'], $current_time_sql]);
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
      // For students, check if there's an active professor with a schedule
      $active_prof_query = "SELECT a.schedule_id, a.subject_id, s.section_id, s.room_id
                           FROM attendance_tbl a
                           JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                           WHERE DATE(a.time_in) = CURDATE() 
                           AND a.prof_id IS NOT NULL 
                           AND (a.status = 'check_in' OR a.status = 'no_schedule')
                           ORDER BY a.time_in DESC LIMIT 1";
      $active_prof_stmt = $conn->prepare($active_prof_query);
      $active_prof_stmt->execute();
      $active_prof = $active_prof_stmt->fetch(PDO::FETCH_ASSOC);

      if ($active_prof) {
        // Check if student belongs to the section of the active schedule
        if ($user['section_id'] != $active_prof['section_id']) {
          // Student is not in the correct section
          $insert = "INSERT INTO attendance_tbl 
                    (student_id, subject_id, schedule_id, room_id, rfid_tag, time_in, status, a_status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'wrong_class', 'Wrong Class')";
          $stmt = $conn->prepare($insert);
          $stmt->execute([
            $user['id'],
            $active_prof['subject_id'],
            $active_prof['schedule_id'],
            $active_prof['room_id'],
            $rfid_tag,
            $current_time
          ]);

          echo json_encode([
            'success' => false,
            'message' => "You are not enrolled in this class section.",
            'time' => $formatted_time,
            'user' => $user,
            'status' => 'wrong_class',
            'a_status' => 'Wrong Class'
          ]);
          exit();
        }

        $schedule = $active_prof;
      } else {
        // No active professor found
        echo json_encode([
          'success' => false,
          'message' => "Please wait for your professor to check in first.",
          'time' => $formatted_time,
          'user' => $user
        ]);
        exit();
      }
    }

    // 🔹 Check Role-Based Attendance Logic
    if ($user['role'] === 'professor') {
      if ($next_action === 'check_in') {
        // ✅ Professor Check-In
        $insert = "INSERT INTO attendance_tbl 
                          (prof_id, subject_id, schedule_id, room_id, rfid_tag, time_in, status, a_status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'check_in', 'Present')";
        $stmt = $conn->prepare($insert);
        $stmt->execute([
          $user['id'],
          $schedule['subject_id'],
          $schedule['schedule_id'],
          $schedule['room_id'],
          $rfid_tag,
          $current_time
        ]);

        // Get room information
        $room_query = "SELECT room_number, room_name FROM room_tbl WHERE room_id = ?";
        $room_stmt = $conn->prepare($room_query);
        $room_stmt->execute([$schedule['room_id']]);
        $room = $room_stmt->fetch(PDO::FETCH_ASSOC);

        // Check if professor is checking in early or late
        $schedule_time = $schedule['schedule_time'];
        $time_diff = strtotime($current_time_sql) - strtotime($schedule_time);
        $message = 'Professor check-in recorded';
        $a_status = 'Present';

        if ($time_diff < -60) { // More than 1 minute early
          $minutes_early = abs(round($time_diff / 60));
          $message = "Professor check-in recorded ($minutes_early minutes early)";
        } else if ($time_diff > 60 && $time_diff < 900) { // 1-15 minutes late
          $minutes_late = round($time_diff / 60);
          $message = "Professor check-in recorded ($minutes_late minutes late)";
        } else if ($time_diff >= 900) { // More than 15 minutes late
          $minutes_late = round($time_diff / 60);
          $message = "Professor check-in recorded ($minutes_late minutes late - marked as LATE)";
          $a_status = 'Late';

          // Update attendance status to Late if more than 15 minutes late
          $update_late = "UPDATE attendance_tbl SET a_status = 'Late' WHERE prof_id = ? AND DATE(time_in) = CURDATE() ORDER BY time_in DESC LIMIT 1";
          $late_stmt = $conn->prepare($update_late);
          $late_stmt->execute([$user['id']]);
        }

        // Update any active professors with ended schedules to "No Schedule" status
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
        $ended_stmt->execute([$current_time_sql]);

        echo json_encode([
          'success' => true,
          'message' => $message,
          'user' => $user,
          'status' => 'check_in',
          'time' => $formatted_time,
          'a_status' => $a_status,
          'room' => $room ? $room : null
        ]);
      } else {
        // ✅ Professor Check-Out & Auto Check Out Students
        $update_prof = "UPDATE attendance_tbl 
                                SET time_out = ?, status = 'check_out', a_status = 'Ended' 
                                WHERE prof_id = ? AND DATE(time_in) = CURDATE() AND status = 'check_in'";
        $stmt = $conn->prepare($update_prof);
        $stmt->execute([$current_time, $user['id']]);

        // Get schedule ID from professor's check-in
        $prof_schedule_query = "SELECT a.schedule_id, r.room_id, r.room_number, r.room_name 
                                FROM attendance_tbl a
                                JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                                JOIN room_tbl r ON s.room_id = r.room_id
                                WHERE a.prof_id = ? AND DATE(a.time_in) = CURDATE() 
                                ORDER BY a.time_in DESC LIMIT 1";
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
          'checked_out_students' => $checked_out_students,
          'room' => $prof_schedule ? ['room_number' => $prof_schedule['room_number'], 'room_name' => $prof_schedule['room_name']] : null
        ]);
      }
      exit();
    }
    if ($user['role'] === 'student') {
      // ✅ Ensure a Professor Has Checked In (Only for check-in)
      if ($next_action === 'check_in') {
        error_log("Student check-in attempt for student ID: " . $user['id']);

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

        // Check if student belongs to the section of the active schedule
        if ($user['section_id'] != $schedule['section_id']) {
          // Student is not in the correct section
          $insert = "INSERT INTO attendance_tbl 
                    (student_id, subject_id, schedule_id, room_id, rfid_tag, time_in, status, a_status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'wrong_class', 'Wrong Class')";
          $stmt = $conn->prepare($insert);
          $stmt->execute([
            $user['id'],
            $schedule['subject_id'],
            $schedule['schedule_id'],
            $schedule['room_id'],
            $rfid_tag,
            $current_time
          ]);

          echo json_encode([
            'success' => false,
            'message' => "You are not enrolled in this class section.",
            'time' => $formatted_time,
            'user' => $user,
            'status' => 'wrong_class',
            'a_status' => 'Wrong Class'
          ]);
          exit();
        }

        error_log("Using schedule_id: " . $schedule['schedule_id'] . " and subject_id: " . $schedule['subject_id'] . " for student check-in");

        // ✅ Student Check-In
        $insert = "INSERT INTO attendance_tbl 
                          (student_id, subject_id, schedule_id, room_id, rfid_tag, time_in, status, a_status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'check_in', 'Present')";
        $stmt = $conn->prepare($insert);
        $stmt->execute([
          $user['id'],
          $schedule['subject_id'],
          $schedule['schedule_id'],
          $schedule['room_id'],
          $rfid_tag,
          $current_time
        ]);

        $insert_id = $conn->lastInsertId();
        error_log("Student check-in recorded with ID: " . $insert_id);

        // Get room information
        $room_query = "SELECT room_number, room_name FROM room_tbl WHERE room_id = ?";
        $room_stmt = $conn->prepare($room_query);
        $room_stmt->execute([$schedule['room_id']]);
        $room = $room_stmt->fetch(PDO::FETCH_ASSOC);

        // Get class schedule time for this class
        if (!isset($schedule['schedule_time'])) {
          $schedule_time_query = "SELECT schedule_time FROM schedule_tbl WHERE schedule_id = ?";
          $schedule_time_stmt = $conn->prepare($schedule_time_query);
          $schedule_time_stmt->execute([$schedule['schedule_id']]);
          $schedule_time_result = $schedule_time_stmt->fetch(PDO::FETCH_ASSOC);
          $schedule_time = $schedule_time_result ? $schedule_time_result['schedule_time'] : null;
        } else {
          $schedule_time = $schedule['schedule_time'];
        }

        $message = 'Student check-in recorded successfully!';

        // Check if student is checking in early or late relative to class time
        if ($schedule_time) {
          $time_diff = strtotime($current_time_sql) - strtotime($schedule_time);
          if ($time_diff < -60) { // More than 1 minute early
            $minutes_early = abs(round($time_diff / 60));
            $message = "Student check-in recorded ($minutes_early minutes early)";
          } else if ($time_diff > 60 && $time_diff < 900) { // 1-15 minutes late
            $minutes_late = round($time_diff / 60);
            $message = "Student check-in recorded ($minutes_late minutes late)";
          } else if ($time_diff >= 900) { // More than 15 minutes late
            $minutes_late = round($time_diff / 60);
            $message = "Student check-in recorded ($minutes_late minutes late - marked as LATE)";

            // Update attendance status to Late if more than 15 minutes late
            $update_late = "UPDATE attendance_tbl SET a_status = 'Late' WHERE attendance_id = ?";
            $late_stmt = $conn->prepare($update_late);
            $late_stmt->execute([$insert_id]);
          }
        }

        echo json_encode([
          'success' => true,
          'message' => $message,
          'user' => $user,
          'status' => 'check_in',
          'time' => $formatted_time,
          'a_status' => 'Present',
          'room' => $room ? $room : null
        ]);
      } else {
        // ✅ Student Check-Out
        $get_student_attendance = "SELECT a.*, r.room_number, r.room_name 
                                  FROM attendance_tbl a
                                  JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                                  JOIN room_tbl r ON a.room_id = r.room_id
                                  WHERE a.student_id = ? AND DATE(a.time_in) = CURDATE() 
                                  AND a.status = 'check_in'";
        $get_stmt = $conn->prepare($get_student_attendance);
        $get_stmt->execute([$user['id']]);
        $student_attendance = $get_stmt->fetch(PDO::FETCH_ASSOC);

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
          'a_status' => 'Ended',
          'room' => $student_attendance ? ['room_number' => $student_attendance['room_number'], 'room_name' => $student_attendance['room_name']] : null
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
  <script src="js/activeClass.js" defer></script>
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

    <!-- New section for active class information -->
    <div id="active-class-container" class="active-class-container" style="display: none;">
      <h2>ACTIVE CLASS</h2>
      <div class="active-class-info">
        <div class="class-details">
          <div class="subject-info">
            <span class="label">Subject:</span>
            <span id="active-subject">-</span>
          </div>
          <div class="professor-info">
            <span class="label">Professor:</span>
            <span id="active-professor">-</span>
          </div>
          <div class="room-info">
            <span class="label">Room:</span>
            <span id="active-room">-</span>
          </div>
          <div class="time-info">
            <span class="label">Time:</span>
            <span id="active-time">-</span>
          </div>
        </div>
      </div>
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
              <span>ROOM</span>
              <span>CHECK IN</span>
              <span>CHECK OUT</span>
              <span>STATUS</span>
            </div>
            <div class="table-body">

            </div>
          </div>
        </div>
        <div class="section">
          <div class="table">
            <h1>STUDENTS</h1>
            <div class="table-header">
              <span>PHOTO</span>
              <span>NAME</span>
              <span>ROOM</span>
              <span>CHECK IN</span>
              <span>CHECK OUT</span>
              <span>STATUS</span>
            </div>
            <div class="table-body">

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/cardhover.js"></script>

</body>

</html>