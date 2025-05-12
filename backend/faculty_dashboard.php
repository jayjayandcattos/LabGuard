<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

// Get the selected date parameter from URL
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d'); // Default to today if invalid format
}

$query = "SELECT lastname FROM faculty_tbl WHERE employee_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_STR);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

// Get room data for the selected date
$query = "SELECT room_id, room_number, room_name, 
          CASE 
              WHEN EXISTS (
                  SELECT 1 FROM attendance_tbl att
                  WHERE att.room_id = room_tbl.room_id 
                  AND DATE(att.time_in) = :selected_date
                  AND att.status = 'check_in'
                  AND att.time_out IS NULL
              ) THEN 'Occupied'
              WHEN EXISTS (
                  SELECT 1 FROM schedule_tbl sch 
                  WHERE sch.room_id = room_tbl.room_id 
                  AND sch.schedule_day = DAYNAME(:selected_date)
              ) THEN 'Scheduled'
              ELSE 'Vacant'
          END as status
          FROM room_tbl
          ORDER BY room_number";

$stmt = $conn->prepare($query);
$stmt->bindParam(':selected_date', $selected_date);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Get the day name from selected date
$selected_day = date('l', strtotime($selected_date));

// Fetch schedule data grouped by day for the selected date
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
     AND DATE(time_in) = :selected_date
     ORDER BY time_in DESC 
     LIMIT 1) as actual_time_in,
    (SELECT TIME_FORMAT(time_out, '%h:%i %p') 
     FROM attendance_tbl 
     WHERE schedule_id = sch.schedule_id 
     AND status = 'check_out' 
     AND DATE(time_out) = :selected_date
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
WHERE sch.schedule_day = :selected_day
ORDER BY 
    r.room_number,
    sch.schedule_time";

$schedule_stmt = $conn->prepare($schedule_query);
$schedule_stmt->bindParam(':selected_date', $selected_date);
$schedule_stmt->bindParam(':selected_day', $selected_day);
$schedule_stmt->execute();
$schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// Format the schedules by room
$schedules_by_room = [];
foreach ($schedules as $schedule) {
    $room_number = $schedule['room_number'];
    if (!isset($schedules_by_room[$room_number])) {
        $schedules_by_room[$room_number] = [];
    }

    // Use actual end time if available, otherwise use estimated
    $end_time = $schedule['actual_time_out'] ?: $schedule['estimated_end_time'];

    $schedules_by_room[$room_number][] = [
        'time_range' => $schedule['start_time'] . ' - ' . $end_time,
        'time_in' => $schedule['actual_time_in'] ?: 'Not yet',
        'time_out' => $schedule['actual_time_out'] ?: 'Not yet',
        'professor' => $schedule['professor_name'],
        'subject' => $schedule['subject_code'],
        'section' => $schedule['section_name'],
        'has_checked_out' => !empty($schedule['actual_time_out'])
    ];
}

// For navigation between dates
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));

date_default_timezone_set('Asia/Manila');
$current_time = date('h:i A');
$formatted_date = date('F d, Y', strtotime($selected_date));

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
    <style>
        
    </style>
</head>

<body>
    <?php include '../sections/nav3.php'; ?>
    <?php include '../sections/fac_nav.php'; ?>
    <div id="main-container">
        <div>
            <h2>CLASSROOMS OVERVIEW</h2>
            <div style="   display: flex; flex-direction: column; align-items: center;
                           border: 2px solid aliceblue; border-radius: 30px;
                           margin: 20px 0;    width: 100%;    padding: 20px;
                           gap: 20px;    left: -30px;">
                <!-- Date Navigation -->
                <div class="date-navigation">
                    <a href="?date=<?= $prev_date ?>">Previous Day</a>
                    <span class="current-date"><?= $formatted_date ?> (<?= $selected_day ?>)</span>
                    <a href="?date=<?= $next_date ?>">Next Day</a>
                </div>

                <!-- Date Picker Form -->
                <form class="date-picker" action="" method="GET">
                    <label for="date-select">Select Date: </label>
                    <input type="date" id="date-select" name="date" value="<?= $selected_date ?>"
                        onchange="this.form.submit()">
                </form>

                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <span class="status-indicator status-vacant"></span> Vacant
                    </div>
                    <div class="legend-item">
                        <span class="status-indicator status-scheduled"></span> Scheduled
                    </div>
                    <div class="legend-item">
                        <span class="status-indicator status-occupied"></span> Occupied
                    </div>
                </div>
            </div>

            <div class="room-grid">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <span class="room-status-indicator 
                            <?php
                            if ($room['status'] == 'Vacant')
                                echo 'vacant';
                            elseif ($room['status'] == 'Scheduled')
                                echo 'scheduled';
                            else
                                echo 'occupied';
                            ?>">
                        </span>
                        <div class="folder">
                            <div class="room-header">
                                <span class="room-status-text"><?= $room['status']; ?></span>
                            </div>
                            <div class="room-number">
                                Room <?= htmlspecialchars($room['room_number']); ?>
                            </div>
                            <?php if (isset($schedules_by_room[$room['room_number']])): ?>
                                <div class="room-details">
                                    <?php foreach ($schedules_by_room[$room['room_number']] as $index => $schedule): ?>
                                        <?php if ($index < 2): // Show only first 2 schedules ?>
                                            <div>
                                                <?= $schedule['time_range'] ?> - <?= $schedule['subject'] ?>
                                                (<?= $schedule['section'] ?>)
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if (count($schedules_by_room[$room['room_number']]) > 2): ?>
                                        <div>+ <?= count($schedules_by_room[$room['room_number']]) - 2 ?> more...</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>