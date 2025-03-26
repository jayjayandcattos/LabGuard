<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

$query = "SELECT lastname FROM faculty_tbl WHERE employee_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_STR);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

// Define days array
$days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

// Fetch all schedules with related information
$query = "SELECT s.*, 
          r.room_name,
          CONCAT(p.lastname, ', ', p.firstname) AS professor_name,
          sub.subject_name, sub.subject_code,
          sec.section_name
          FROM schedule_tbl s
          JOIN room_tbl r ON s.room_id = r.room_id
          JOIN prof_tbl p ON s.prof_user_id = p.prof_user_id
          JOIN subject_tbl sub ON s.subject_id = sub.subject_id
          JOIN section_tbl sec ON s.section_id = sec.section_id
          ORDER BY s.schedule_day, s.schedule_time";

$stmt = $conn->prepare($query);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize schedules by day and room
$schedule_by_day = [];
foreach ($schedules as $schedule) {
    $day = strtoupper($schedule['schedule_day']);
    $room = $schedule['room_name'];

    if (!isset($schedule_by_day[$day])) {
        $schedule_by_day[$day] = [];
    }
    if (!isset($schedule_by_day[$day][$room])) {
        $schedule_by_day[$day][$room] = [];
    }

    // Format the schedule data
    $schedule_by_day[$day][$room][] = [
        'time_range' => date('h:i A', strtotime($schedule['time_in'])) . ' - ' . date('h:i A', strtotime($schedule['time_out'])),
        'time_in' => date('h:i A', strtotime($schedule['time_in'])),
        'professor' => $schedule['professor_name'],
        'subject' => $schedule['subject_code'],
        'section' => $schedule['section_name']
    ];
}

// Get current time and day
date_default_timezone_set('Asia/Manila');
$current_time = date('h:i A');
$current_day = date('l');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Overview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">

</head>

<body>
    <?php include '../sections/nav3.php'; ?>
    <?php include '../sections/fac_nav.php'; ?>
    <div id="main-container">
        <div class="BLOCK">
            <h2>SCHEDULE OVERVIEW</h2>
            <i class="fas fa-print print-icon" title="Print Schedule"></i>
        </div>

        <div class="days-header">
            <?php foreach ($days as $day): ?>
                <div class="day-column"><?= strtoupper($day) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="schedule-grid-container">
            <div class="schedule-grid">
                <?php foreach ($days as $day): ?>
                    <div class="day-schedules">
                        <?php if (isset($schedule_by_day[$day])): ?>
                            <?php foreach ($schedule_by_day[$day] as $room_number => $room_schedules): ?>
                                <?php foreach ($room_schedules as $schedule): ?>
                                    <div class="schedule-card">
                                        <h5><?= htmlspecialchars($room_number) ?></h5>
                                        <p class="time-info"><?= htmlspecialchars($schedule['time_range']) ?></p>
                                        <p><strong>Professor:</strong> <?= htmlspecialchars($schedule['professor']) ?></p>
                                        <p><strong>Subject:</strong> <?= htmlspecialchars($schedule['subject']) ?></p>
                                        <p><strong>Section:</strong> <?= htmlspecialchars($schedule['section']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-schedule">No classes scheduled</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    </div>

</body>