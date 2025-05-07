<?php
session_start();
require_once "backend/db.php";

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

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
                           ORDER BY s.schedule_time DESC LIMIT 1";
    $schedule_stmt = $conn->prepare($schedule_query);
    $schedule_stmt->execute([$current_day, date('H:i:s')]);
    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
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
            $update_students = "UPDATE attendance_tbl 
                                SET time_out = ?, status = 'check_out', a_status = 'Ended'
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
        $active_prof_query = "SELECT 1 FROM attendance_tbl 
                                  WHERE DATE(time_in) = CURDATE() 
                                  AND prof_id IS NOT NULL 
                                  AND status = 'check_in' 
                                  AND a_status = 'Present'
                                  LIMIT 1";
        $prof_stmt = $conn->prepare($active_prof_query);
        $prof_stmt->execute();
        $active_prof = $prof_stmt->fetchColumn();

        if (!$active_prof) {
          echo json_encode([
            'success' => false,
            'message' => 'Please wait for your professor to check in first.',
            'time' => $formatted_time
          ]);
          exit();
        }

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