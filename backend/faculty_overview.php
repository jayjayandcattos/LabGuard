<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

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
    <link rel="stylesheet" href="../css/colorum.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-link.active {
            background-color: #152569 !important;
        }

        .schedule-header {
            background: #5C6BC0;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .schedule-header h2 {
            margin: 0;
            font-family: 'Arial', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .print-icon {
            font-size: 1.5rem;
            color: white;
            cursor: pointer;
        }
        .days-header {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .day-column {
            background: #3F51B5;
            color: white;
            padding: 0.75rem;
            text-align: center;
            border-right: 1px solid white;
        }

        .day-column:last-child {
            border-right: none;
        }

        .schedule-grid {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .day-schedules {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 16%; /* Ensures equal width for all day columns */
        }

        .schedule-card {
            background: #E8EAF6;
            border-radius: 10px;
            width: 100%;
            min-height: 200px;
            box-sizing: border-box;
            padding: 1rem;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .schedule-card h5 {
            color: #3F51B5;
            margin-bottom: 0.5rem;
        }
        .schedule-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #333;
        }
        .time-info {
            color: #666;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <nav class="btn-panel" style="width: 250px;">
            <h4>Faculty Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="faculty_overview.php" class="nav-link text-white active">Overview</a></li>
                <li class="nav-item"><a href="faculty_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="view_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="view_professors.php" class="nav-link text-white">Professors Profile</a></li>
                <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white">Schedule Management</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <div class="container-fluid p-4">
            <div class="schedule-header">
                <h2>SCHEDULE OVERVIEW</h2>
                <i class="fas fa-print print-icon"></i>
            </div>

            <div class="days-header">
                <?php foreach ($days as $day): ?>
                    <div class="day-column"> <?= $day ?> </div>
                <?php endforeach; ?>
            </div>

            <div class="schedule-grid">
                <?php foreach ($days as $day): ?>
                    <div class="day-schedules">
                        <?php if (isset($schedule_by_day[$day])): ?>
                            <?php foreach ($schedule_by_day[$day] as $room_number => $room_schedules): ?>
                                <?php foreach ($room_schedules as $schedule): ?>
                                    <div class="schedule-card">
                                        <h5><?= htmlspecialchars($room_number) ?></h5>
                                        <p class="time-info"> <?= htmlspecialchars($schedule['time_range']) ?> </p>
                                        <p class="time-info">TIME IN: <?= htmlspecialchars($schedule['time_in']) ?></p>
                                        <p><?= htmlspecialchars($schedule['professor']) ?></p>
                                        <p><?= htmlspecialchars($schedule['subject']) ?></p>
                                        <p><?= htmlspecialchars($schedule['section']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
