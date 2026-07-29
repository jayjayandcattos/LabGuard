<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    header("Location: login.php");
    exit();
}

// Fetch professor's schedules
$prof_user_id = $_SESSION["user_id"];
// Log the session data for debugging
error_log("Session data: " . print_r($_SESSION, true));

// Define days array
$days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

$query = "SELECT s.*, 
          CONCAT(p.lastname, ', ', p.firstname) AS professor_name,
          p.lastname, p.firstname,
          sub.subject_name, sub.subject_code,
          sec.section_name,
          r.room_name,
          DATE_FORMAT(s.schedule_time, '%h:%i %p') as formatted_time,
          DATE_FORMAT(DATE_ADD(s.schedule_time, INTERVAL 3 HOUR), '%h:%i %p') as formatted_end_time,
          (SELECT DATE_FORMAT(time_in, '%h:%i %p') 
           FROM attendance_tbl 
           WHERE prof_id = p.prof_user_id 
           AND schedule_id = s.schedule_id
           AND status = 'check_in'
           AND DATE(time_in) = CURDATE()
           ORDER BY time_in DESC 
           LIMIT 1) as actual_time_in,
          (SELECT DATE_FORMAT(time_out, '%h:%i %p') 
           FROM attendance_tbl 
           WHERE prof_id = p.prof_user_id 
           AND schedule_id = s.schedule_id
           AND status = 'check_out'
           AND DATE(time_out) = CURDATE()
           ORDER BY time_out DESC 
           LIMIT 1) as actual_time_out
          FROM schedule_tbl s
          JOIN prof_tbl p ON s.prof_user_id = p.prof_user_id
          JOIN subject_tbl sub ON s.subject_id = sub.subject_id
          JOIN section_tbl sec ON s.section_id = sec.section_id
          JOIN room_tbl r ON s.room_id = r.room_id
          WHERE p.prof_user_id = :prof_user_id
          ORDER BY FIELD(s.schedule_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), 
          s.schedule_time";

$stmt = $conn->prepare($query);
$stmt->execute([
    "prof_user_id" => $prof_user_id
]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize schedules by day
$schedulesByDay = [];
foreach ($days as $day) {
    $schedulesByDay[$day] = [];
}

foreach ($schedules as $schedule) {
    $day = strtoupper($schedule['schedule_day']);
    if (isset($schedulesByDay[$day])) {
        $schedulesByDay[$day][] = $schedule;
    }
}

// Fetch Professor's Last Name
$prof_query = "SELECT lastname FROM prof_tbl WHERE prof_user_id = :prof_user_id";
$prof_stmt = $conn->prepare($prof_query);

if ($prof_stmt->execute(['prof_user_id' => $prof_user_id])) {
    $professor = $prof_stmt->fetch(PDO::FETCH_ASSOC);

    if ($professor) {
        $prof_lastname = $professor['lastname'];
    } else {
        error_log("No professor found with prof_user_id: " . $prof_user_id);
        $prof_lastname = "Unknown";
    }
} else {
    error_log("Query execution failed: " . implode(" | ", $prof_stmt->errorInfo()));
    $prof_lastname = "Error";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/prof.css">
    <style>
        .days-header {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            background-color: #3f4b7f;
            color: white;
            padding: 10px 0;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 20px;
            font-family: "Monomaniac One", sans-serif;
        }
        
        .schedule-grid-container {
            overflow-x: auto;
        }
        
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            width: 100%;
        }
        
        .day-schedules {
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-height: 100px;
        }
        
        .schedule-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .schedule-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .schedule-card h5 {
            color: #3f4b7f;
            font-weight: bold;
            margin-bottom: 10px;
            font-family: "Monomaniac One", sans-serif;
        }
        
        .time-info {
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .time-info.actual {
            color: #22c55e;
            font-weight: bold;
        }
        
        .time-info.estimated {
            color: #888;
            font-style: italic;
        }
        
        .empty-schedule {
            padding: 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #3f4b7f;
            font-weight: bold;
            font-style: italic;
        }
        
        .BLOCK {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include '../sections/nav2.php' ?>
    <?php include '../sections/prof_nav.php'; ?>

    <!-- Main Content -->
    <div id="main-container">
        <div class="BLOCK">
            <h2>MY TEACHING SCHEDULE</h2>
        </div>

        <div class="days-header">
            <?php foreach ($days as $day): ?>
                <div class="day-column"><?= $day ?></div>
            <?php endforeach; ?>
        </div>

        <div class="schedule-grid-container">
            <div class="schedule-grid">
                <?php foreach ($days as $day): ?>
                    <div class="day-schedules">
                        <?php if (!empty($schedulesByDay[$day])): ?>
                            <?php foreach ($schedulesByDay[$day] as $schedule): ?>
                                <div class="schedule-card">
                                    <h5><?= htmlspecialchars($schedule['room_name']); ?></h5>
                                    <p class="time-info <?= !empty($schedule['actual_time_in']) ? 'actual' : 'estimated' ?>">
                                        <?= htmlspecialchars($schedule['formatted_time']); ?> - <?= htmlspecialchars($schedule['formatted_end_time']); ?>
                                        <?php if (!empty($schedule['actual_time_in'])): ?>
                                            <small>(ACTUAL)</small>
                                        <?php else: ?>
                                            <small>(ESTIMATED)</small>
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Check-in:</strong> <?= htmlspecialchars($schedule['actual_time_in'] ?? 'Not yet'); ?></p>
                                    <p><strong>Check-out:</strong> <?= htmlspecialchars($schedule['actual_time_out'] ?? 'Not yet'); ?></p>
                                    <p><strong>SUBJECT:</strong> <?= htmlspecialchars($schedule['subject_name']); ?></p>
                                    <p><strong>SECTION:</strong> <?= htmlspecialchars($schedule['section_name']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-schedule">No classes scheduled</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>