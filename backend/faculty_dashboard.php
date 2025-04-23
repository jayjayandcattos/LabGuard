<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

$query = "SELECT lastname FROM faculty_tbl WHERE employee_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_STR);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT * FROM room_tbl";
$stmt = $conn->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch room data
$query = "SELECT room_id, room_number, room_name, 
          CASE 
              WHEN EXISTS (
                  SELECT 1 FROM schedule_tbl sch 
                  WHERE sch.room_id = room_tbl.room_id 
                  AND sch.schedule_day = DAYNAME(CURDATE())
                  AND CURRENT_TIME BETWEEN sch.schedule_time AND DATE_ADD(sch.schedule_time, INTERVAL 3 HOUR)
              ) THEN 'Occupied'
              ELSE 'Vacant'
          END as status
          FROM room_tbl
          ORDER BY room_number";

$stmt = $conn->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Fetch schedule data grouped by day
$schedule_query = "SELECT 
    sch.schedule_id,
    sch.schedule_day,
    r.room_number,
    r.room_name,
    TIME_FORMAT(sch.schedule_time, '%h:%i %p') as start_time,
    TIME_FORMAT(DATE_ADD(sch.schedule_time, INTERVAL 3 HOUR), '%h:%i %p') as estimated_end_time,
    (SELECT TIME_FORMAT(time_in, '%h:%i %p') 
     FROM attendance_tbl 
     WHERE schedule_id = sch.schedule_id 
     AND status = 'check_in' 
     AND DATE(time_in) = CURDATE()
     ORDER BY time_in DESC 
     LIMIT 1) as actual_time_in,
    (SELECT TIME_FORMAT(time_out, '%h:%i %p') 
     FROM attendance_tbl 
     WHERE schedule_id = sch.schedule_id 
     AND status = 'check_out' 
     AND DATE(time_out) = CURDATE()
     ORDER BY time_out DESC 
     LIMIT 1) as actual_time_out,
    CONCAT(p.lastname, ', ', p.firstname) as professor_name,
    sub.subject_code as subject_code,
    sec.section_name as section_name
FROM schedule_tbl sch
JOIN room_tbl r ON sch.room_id = r.room_id
JOIN prof_tbl p ON sch.prof_user_id = p.prof_user_id
JOIN subject_tbl sub ON sch.subject_id = sub.subject_id
JOIN section_tbl sec ON sch.section_id = sec.section_id
ORDER BY 
    FIELD(sch.schedule_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
    r.room_number,
    sch.schedule_time";

$schedule_stmt = $conn->prepare($schedule_query);
$schedule_stmt->execute();
$schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);


$schedule_by_day = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
foreach ($days as $day) {
    $schedule_by_day[$day] = [];
    foreach ($schedules as $schedule) {
        if ($schedule['schedule_day'] === $day) {
            if (!isset($schedule_by_day[$day][$schedule['room_number']])) {
                $schedule_by_day[$day][$schedule['room_number']] = [];
            }
            // Use actual end time if available, otherwise use estimated
            $end_time = $schedule['actual_time_out'] ?: $schedule['estimated_end_time'];
            
            $schedule_by_day[$day][$schedule['room_number']][] = [
                'time_range' => $schedule['start_time'] . ' - ' . $end_time,
                'time_in' => $schedule['actual_time_in'] ?: 'Not yet',
                'time_out' => $schedule['actual_time_out'] ?: 'Not yet',
                'professor' => $schedule['professor_name'],
                'subject' => $schedule['subject_code'],
                'section' => $schedule['section_name'],
                'has_checked_out' => !empty($schedule['actual_time_out'])
            ];
        }
    }
}


date_default_timezone_set('Asia/Manila');
$current_time = date('h:i A');
$current_day = date('l');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="../css/prof.css">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
</head>

<body>

    <body>
        <?php include '../sections/nav3.php'; ?>
        <?php include '../sections/fac_nav.php'; ?>
        <div id="main-container">
            <div class="BLOCK">
                <h2>CLASSROOMS OVERVIEW</h2>
                <div class="room-grid">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card">
                            <span class="room-status-indicator <?= $room['status'] == 'Vacant' ? 'vacant' : 'occupied'; ?>"></span>
                            <div class="folder">
                                <div class="room-header">
                                    <span class="room-status-text"><?= $room['status']; ?></span>
                                </div>
                                <div class="room-number">
                                    Room <?= htmlspecialchars($room['room_number']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>



            <pre>
    <?php
    ?>
    </pre>
    </body>

</html>