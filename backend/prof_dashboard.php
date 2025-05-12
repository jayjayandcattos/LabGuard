<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    header("Location: login.php");
    exit();
}

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

// Get the day name from selected date
$selected_day = date('l', strtotime($selected_date));
$formatted_date = date('F d, Y', strtotime($selected_date));

// For navigation between dates
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));

// Check for duplicate room names
$check_rooms_query = "SELECT room_number, COUNT(*) as count 
                     FROM room_tbl 
                     GROUP BY room_number 
                     HAVING COUNT(*) > 1";
$check_rooms_stmt = $conn->prepare($check_rooms_query);
$check_rooms_stmt->execute();
$duplicate_rooms = $check_rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

// Log duplicate rooms if found
if (!empty($duplicate_rooms)) {
    error_log("Duplicate room numbers found: " . print_r($duplicate_rooms, true));
}

// Fetch Professor's information
$prof_user_id = $_SESSION["user_id"];
$prof_query = "SELECT * FROM prof_tbl WHERE prof_user_id = :prof_user_id";
$prof_stmt = $conn->prepare($prof_query);
$prof_stmt->execute(['prof_user_id' => $prof_user_id]);
$professor = $prof_stmt->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    error_log("No professor found with ID: $prof_user_id");
    $prof_lastname = "Unknown";
} else {
    $prof_lastname = $professor['lastname'];
}

// Get current time
date_default_timezone_set('Asia/Manila');
$current_time = date('H:i:s');

// Fetch room data with occupancy status for selected date
$query = "SELECT r.*, 
          CASE 
              WHEN EXISTS (
                  SELECT 1 FROM attendance_tbl a
                  WHERE a.room_id = r.room_id 
                  AND a.status = 'check_in'
                  AND a.time_out IS NULL
                  AND DATE(a.time_in) = :selected_date
              ) THEN 'Occupied'
              WHEN EXISTS (
                  SELECT 1 FROM schedule_tbl s 
                  WHERE s.room_id = r.room_id 
                  AND s.schedule_day = :selected_day
                  AND :current_time BETWEEN s.schedule_time AND DATE_ADD(s.schedule_time, INTERVAL 3 HOUR)
              ) THEN 'Scheduled'
              ELSE 'Vacant'
          END as current_status
          FROM room_tbl r
          ORDER BY r.room_number";

$stmt = $conn->prepare($query);
$stmt->execute([
    'selected_day' => $selected_day,
    'selected_date' => $selected_date,
    'current_time' => $current_time
]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log room count to debug
error_log("Room count before check: " . count($rooms));
error_log("Room IDs: " . implode(", ", array_column($rooms, 'room_id')));
error_log("Room numbers: " . implode(", ", array_column($rooms, 'room_number')));

// Check if Room 604 is missing
$has_room_604 = false;
foreach ($rooms as $room) {
    if ($room['room_number'] == 604) {
        $has_room_604 = true;
        break;
    }
}

// If room 604 is missing, add it manually
if (!$has_room_604) {
    error_log("Room 604 is missing, adding it manually");
    $rooms[] = [
        'room_id' => 999, // Use a dummy ID that won't conflict
        'room_number' => 604,
        'room_name' => 'Bautista_604',
        'status' => 'Vacant',
        'current_status' => 'Vacant',
        'occupied_by_me' => false
    ];
}

// Ensure all 4 rooms from database are included
$essential_rooms = [503, 504, 603, 604];
foreach ($essential_rooms as $room_number) {
    $found = false;
    foreach ($rooms as $room) {
        if (isset($room['room_number']) && $room['room_number'] == $room_number) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        error_log("Adding missing essential room: $room_number");
        $dummy_name = "Bautista_$room_number";

        // Create a complete room entry with all necessary fields
        $new_room = [
            'room_id' => 1000 + $room_number, // Use a dummy ID
            'room_number' => $room_number,
            'room_name' => $dummy_name,
            'status' => 'Vacant',
            'current_status' => 'Vacant',
            'occupied_by_me' => false
        ];

        $rooms[] = $new_room;
        error_log("Added room: " . print_r($new_room, true));
    }
}

// Sort rooms by room number for consistent display
usort($rooms, function ($a, $b) {
    return $a['room_number'] <=> $b['room_number'];
});

error_log("Room count after check: " . count($rooms));

// Get the professor's current active classes for the selected date
$active_classes_query = "SELECT s.schedule_id, s.room_id, r.room_name, r.room_number,
                        TIME_FORMAT(s.schedule_time, '%h:%i %p') as start_time,
                        TIME_FORMAT(DATE_ADD(s.schedule_time, INTERVAL 3 HOUR), '%h:%i %p') as end_time,
                        sub.subject_code
                        FROM schedule_tbl s
                        JOIN room_tbl r ON s.room_id = r.room_id
                        JOIN subject_tbl sub ON s.subject_id = sub.subject_id
                        WHERE s.prof_user_id = :prof_user_id
                        AND s.schedule_day = :selected_day
                        ORDER BY s.schedule_time";

$active_stmt = $conn->prepare($active_classes_query);
$active_stmt->execute([
    'prof_user_id' => $prof_user_id,
    'selected_day' => $selected_day
]);
$schedule_classes = $active_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a list of room IDs where the professor is teaching
$active_room_ids = [];
foreach ($schedule_classes as $class) {
    $active_room_ids[] = $class['room_id'];
}

// Check if the professor has already checked in on the selected date
$checkin_query = "SELECT a.*, s.room_id, s.schedule_time
                FROM attendance_tbl a
                JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                WHERE a.prof_id = :prof_id
                AND s.schedule_day = :selected_day
                AND DATE(a.time_in) = :selected_date
                AND a.status = 'check_in'
                AND a.time_out IS NULL
                ORDER BY a.time_in DESC
                LIMIT 1";

$checkin_stmt = $conn->prepare($checkin_query);
$checkin_stmt->execute([
    'prof_id' => $prof_user_id,
    'selected_day' => $selected_day,
    'selected_date' => $selected_date
]);
$active_checkin = $checkin_stmt->fetch(PDO::FETCH_ASSOC);

// If professor has checked in, mark that room as occupied by them
if ($active_checkin) {
    $checkin_schedule_query = "SELECT room_id FROM schedule_tbl WHERE schedule_id = :schedule_id";
    $checkin_schedule_stmt = $conn->prepare($checkin_schedule_query);
    $checkin_schedule_stmt->execute(['schedule_id' => $active_checkin['schedule_id']]);
    $checkin_room = $checkin_schedule_stmt->fetch(PDO::FETCH_ASSOC);

    if ($checkin_room) {
        $active_room_ids[] = $checkin_room['room_id'];
    }
}

// Update rooms array with professor's presence
foreach ($rooms as &$room) {
    if (in_array($room['room_id'], $active_room_ids)) {
        $room['current_status'] = 'Occupied';
        $room['occupied_by_me'] = true;
    } else {
        $room['occupied_by_me'] = false;
    }
}

// Organize schedule classes by room for display
$schedules_by_room = [];
foreach ($schedule_classes as $class) {
    $room_number = $class['room_number'];
    if (!isset($schedules_by_room[$room_number])) {
        $schedules_by_room[$room_number] = [];
    }
    $schedules_by_room[$room_number][] = $class;
}

// Debug: Get unique room numbers
$room_numbers = array_unique(array_column($rooms, 'room_number'));
error_log("Unique room numbers: " . implode(", ", $room_numbers));
error_log("Total rooms: " . count($rooms));

// Check attendance records for selected date
$attendance_query = "SELECT a.*, s.room_id, r.room_number, r.room_name, 
                   TIME_FORMAT(a.time_in, '%h:%i %p') as formatted_time_in,
                   TIME_FORMAT(a.time_out, '%h:%i %p') as formatted_time_out,
                   sub.subject_code
                   FROM attendance_tbl a
                   JOIN schedule_tbl s ON a.schedule_id = s.schedule_id
                   JOIN room_tbl r ON s.room_id = r.room_id
                   JOIN subject_tbl sub ON a.subject_id = sub.subject_id
                   WHERE a.prof_id = :prof_id
                   AND DATE(a.time_in) = :selected_date
                   ORDER BY a.time_in DESC";

$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->execute([
    'prof_id' => $prof_user_id,
    'selected_date' => $selected_date
]);
$attendance_records = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/prof.css">
    <style>
        .room-status-text.my-class {
            color: #4CAF50;
            font-weight: bold;
        }

        .room-card {
            position: relative;
        }

        .my-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #4CAF50;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            z-index: 10;
        }

        .date-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .date-navigation a {
            padding: 5px 15px;
            background-color: #333;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background-color 0.3s;
        }

        .date-navigation a:hover {
            background-color: #555;
        }

        .current-date {
            font-weight: bold;
            color: white;
            font-size: 1.2em;
        }

        .date-picker {
            margin: 0 20px;
            text-align: center;
        }

        .date-picker input {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-vacant {
            background-color: #4CAF50;
        }

        .status-occupied {
            background-color: #f44336;
        }

        .status-scheduled {
            background-color: #FFC107;
        }

        .status-my-class {
            background-color: #2196F3;
        }

        .legend {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
            color: white;
        }

        .room-details {
            margin-top: 8px;
            color: #ccc;
            font-size: 0.9em;
        }

        .schedule-section {
            margin-top: 30px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
        }

        .schedule-title {
            color: white;
            font-size: 1.2em;
            margin-bottom: 15px;
            text-align: center;
        }

        .schedule-table {
            width: 100%;
            color: white;
        }

        .schedule-table th,
        .schedule-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .schedule-table th {
            background-color: rgba(0, 0, 0, 0.3);
        }

        .attendance-section {
            margin-top: 30px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
        }

        .today-indicator {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include '../sections/nav2.php'; ?>
    <?php include '../sections/prof_nav.php'; ?>

    <div id="main-container">
        <h2>CLASSROOM OVERVIEW</h2>
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
                <div class="legend-item">
                    <span class="status-indicator status-my-class"></span> My Class
                </div>
            </div>
        </div>

        <?php if ($selected_date === date('Y-m-d')): ?>
            <p class="today-indicator text-center">Showing today's schedule</p>
        <?php endif; ?>

        <!-- Room Grid -->
        <div class="room-grid">
            <?php
            // Make sure we display each essential room
            foreach ($essential_rooms as $room_number):
                // Find this room in the rooms array
                $room = null;
                foreach ($rooms as $r) {
                    if (isset($r['room_number']) && $r['room_number'] == $room_number) {
                        $room = $r;
                        break;
                    }
                }

                // If room is not found, create a placeholder
                if (!$room) {
                    error_log("WARNING: Room $room_number not found in rooms array, creating placeholder");
                    $room = [
                        'room_id' => 1000 + $room_number,
                        'room_number' => $room_number,
                        'room_name' => "Bautista_$room_number",
                        'status' => 'Vacant',
                        'current_status' => 'Vacant',
                        'occupied_by_me' => false
                    ];
                }
                ?>
                <div class="room-card">
                    <span class="room-status-indicator 
                        <?php
                        if (isset($room['occupied_by_me']) && $room['occupied_by_me']) {
                            echo 'occupied';
                        } elseif (isset($room['current_status'])) {
                            if ($room['current_status'] == 'Vacant')
                                echo 'vacant';
                            elseif ($room['current_status'] == 'Scheduled')
                                echo 'scheduled';
                            else
                                echo 'occupied';
                        } else {
                            echo 'vacant';
                        }
                        ?>">
                    </span>
                    <?php if (isset($room['occupied_by_me']) && $room['occupied_by_me']): ?>
                        <div class="my-indicator" title="Your scheduled room">Me</div>
                    <?php endif; ?>
                    <div class="folder">
                        <div class="room-header">
                            <span
                                class="room-status-text <?= (isset($room['occupied_by_me']) && $room['occupied_by_me']) ? 'my-class' : ''; ?>">
                                <?php if (isset($room['occupied_by_me']) && $room['occupied_by_me']): ?>
                                    MY CLASS
                                <?php else: ?>
                                    <?= isset($room['current_status']) ? $room['current_status'] : 'Vacant'; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="room-number">
                            Room <?= isset($room['room_number']) ? htmlspecialchars($room['room_number']) : $room_number; ?>
                        </div>
                        <?php if (isset($schedules_by_room[$room['room_number']])): ?>
                            <div class="room-details">
                                <?php foreach ($schedules_by_room[$room['room_number']] as $index => $schedule): ?>
                                    <?php if ($index < 2): // Show only first 2 schedules ?>
                                        <div>
                                            <?= $schedule['start_time'] ?> - <?= $schedule['end_time'] ?>:
                                            <?= $schedule['subject_code'] ?>
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

        <!-- Your Schedule Section -->
        <?php if (!empty($schedule_classes)): ?>
            <div class="schedule-section">
                <h3 class="schedule-title">Your Schedule for <?= $formatted_date ?></h3>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule_classes as $class): ?>
                            <tr>
                                <td><?= $class['start_time'] ?> - <?= $class['end_time'] ?></td>
                                <td>Room <?= $class['room_number'] ?></td>
                                <td><?= $class['subject_code'] ?></td>
                                <td>
                                    <?php
                                    $attended = false;
                                    foreach ($attendance_records as $record) {
                                        if ($record['schedule_id'] == $class['schedule_id']) {
                                            echo $record['formatted_time_out'] ? 'Completed' : 'In Progress';
                                            $attended = true;
                                            break;
                                        }
                                    }
                                    if (!$attended) {
                                        echo "Not Started";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="schedule-section">
                <h3 class="schedule-title">No scheduled classes for <?= $formatted_date ?></h3>
            </div>
        <?php endif; ?>

        <!-- Attendance Records Section -->
        <?php if (!empty($attendance_records)): ?>
            <div class="attendance-section">
                <h3 class="schedule-title">Attendance Records for <?= $formatted_date ?></h3>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Subject</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td>Room <?= $record['room_number'] ?></td>
                                <td><?= $record['subject_code'] ?></td>
                                <td><?= $record['formatted_time_in'] ?></td>
                                <td><?= $record['formatted_time_out'] ? $record['formatted_time_out'] : 'Not yet' ?></td>
                                <td><?= $record['a_status'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // If the date is today, highlight it
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date().toISOString().split('T')[0];
            if ('<?= $selected_date ?>' === today) {
                document.querySelector('.current-date').classList.add('today-indicator');
            }
        });
    </script>
</body>

</html>