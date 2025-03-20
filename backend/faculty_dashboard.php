<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

// Fetch room data
$query = "SELECT room_id, room_number, room_name, 
          CASE 
              WHEN EXISTS (
                  SELECT 1 FROM schedule_tbl sch 
                  WHERE sch.room_id = room_tbl.room_id 
                  AND sch.schedule_day = DAYNAME(CURDATE())
                  AND CURRENT_TIME BETWEEN sch.schedule_time AND sch.time_out
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
    TIME_FORMAT(sch.time_out, '%h:%i %p') as end_time,
    TIME_FORMAT(sch.time_in, '%h:%i %p') as actual_time_in,
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

// Organize schedules by day and room
$schedule_by_day = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
foreach ($days as $day) {
    $schedule_by_day[$day] = [];
    foreach ($schedules as $schedule) {
        if ($schedule['schedule_day'] === $day) {
            if (!isset($schedule_by_day[$day][$schedule['room_number']])) {
                $schedule_by_day[$day][$schedule['room_number']] = [];
            }
            $schedule_by_day[$day][$schedule['room_number']][] = [
                'time_range' => $schedule['start_time'] . ' - ' . $schedule['end_time'],
                'time_in' => $schedule['actual_time_in'] ?: 'Not yet',
                'professor' => $schedule['professor_name'],
                'subject' => $schedule['subject_code'],
                'section' => $schedule['section_name']
            ];
        }
    }
}

// Get current time for the clock display
date_default_timezone_set('Asia/Manila');
$current_time = date('h:i A');
$current_day = date('l');

// The data is now organized in $schedule_by_day array
// You can access it like: $schedule_by_day['Monday'][603] to get all schedules for Monday in room 603
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/colorum.css">
    <style>
        .nav-link.active {
            background-color: #152569 !important; 
        }
    </style>

</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="btn-panel" style="width: 250px;">
            <h4>Faculty Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="faculty_overview.php" class="nav-link text-white">Overview</a></li>
                <li class="nav-item"><a href="faculty_dashboard.php" class="nav-link text-white active">Classrooms</a></li>
                <li class="nav-item"><a href="view_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="view_professors.php" class="nav-link text-white">Professors Profile</a></li>
                <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white">Schedule Management</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
    <h2>Classroom Overview</h2>
    <div class="row">
        <?php foreach ($rooms as $room): ?>
            <div class="col-md-4 mb-3">
                <div class="card p-3" style="border-left: 8px solid <?= $room['status'] == 'Vacant' ? '#28a745' : '#dc3545'; ?>;">
                    <h5 class="card-title">Room <?= htmlspecialchars($room['room_number']); ?></h5>
                    <p class="card-text"><strong>Name:</strong> <?= htmlspecialchars($room['room_name']); ?></p>
                    <p class="card-text">
                        <strong>Status:</strong>
                        <span class="badge" style="background-color: <?= $room['status'] == 'Vacant' ? '#28a745' : '#dc3545'; ?>;">
                            <?= htmlspecialchars($room['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


    <!-- You can keep your existing HTML structure and add this for debugging: -->
    <pre>
    <?php 
    // Uncomment this line to debug the schedule data structure
    // print_r($schedule_by_day); 
    ?>
    </pre>
</body>
</html>
