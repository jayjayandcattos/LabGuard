<?php
session_start();
require_once "db.php";

// Ensure user is logged in as faculty or admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] !== "faculty" && $_SESSION["role"] !== "admin" && $_SESSION["role"] !== "professor")) {
    header("Location: login.php");
    exit();
}

// Get faculty name if the user is faculty
if ($_SESSION["role"] === "faculty") {
    $query = "SELECT lastname FROM faculty_tbl WHERE employee_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_STR);
    $stmt->execute();
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get professor name if the user is professor
if ($_SESSION["role"] === "professor") {
    $prof_query = "SELECT lastname FROM prof_tbl WHERE prof_user_id = :prof_user_id";
    $prof_stmt = $conn->prepare($prof_query);
    $prof_stmt->execute(['prof_user_id' => $_SESSION["user_id"]]);
    $professor = $prof_stmt->fetch(PDO::FETCH_ASSOC);
    $prof_lastname = $professor ? $professor['lastname'] : "Unknown";
}

// Get admin name if the user is admin
if ($_SESSION["role"] === "admin" && !isset($_SESSION['name'])) {
    $admin_query = "SELECT CONCAT(firstname, ' ', lastname) as name FROM admin_tbl WHERE admin_user_id = :admin_id";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->execute(['admin_id' => $_SESSION["user_id"]]);
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $_SESSION['name'] = $admin['name'];
    }
}

// Get room ID from URL, default to first room if not specified
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

// Get date filter from URL, default to today
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = date('Y-m-d');
}

// Get all rooms for dropdown
$rooms_query = "SELECT room_id, room_number, room_name FROM room_tbl ORDER BY room_number";
$rooms_stmt = $conn->prepare($rooms_query);
$rooms_stmt->execute();
$rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

// If no room_id is specified, use the first room from the list
if ($room_id === 0 && !empty($rooms)) {
    $room_id = $rooms[0]['room_id'];
}

// Get room details
$room_query = "SELECT room_number, room_name FROM room_tbl WHERE room_id = ?";
$room_stmt = $conn->prepare($room_query);
$room_stmt->execute([$room_id]);
$room = $room_stmt->fetch(PDO::FETCH_ASSOC);

// Get attendance logs for the selected room and date
$logs_query = "SELECT 
                a.*,
                TIME_FORMAT(a.time_in, '%h:%i %p') as formatted_time_in,
                TIME_FORMAT(a.time_out, '%h:%i %p') as formatted_time_out,
                CASE 
                    WHEN a.prof_id IS NOT NULL THEN CONCAT(p.lastname, ', ', p.firstname)
                    WHEN a.student_id IS NOT NULL THEN CONCAT(s.lastname, ', ', s.firstname)
                    ELSE 'Unknown'
                END as full_name,
                CASE 
                    WHEN a.prof_id IS NOT NULL THEN 'Professor' 
                    ELSE 'Student' 
                END as user_role,
                CASE
                    WHEN a.prof_id IS NOT NULL THEN p.photo
                    WHEN a.student_id IS NOT NULL THEN s.photo
                    ELSE NULL
                END as photo,
                sub.subject_code
                FROM attendance_tbl a
                LEFT JOIN prof_tbl p ON a.prof_id = p.prof_user_id
                LEFT JOIN student_tbl s ON a.student_id = s.student_user_id
                LEFT JOIN subject_tbl sub ON a.subject_id = sub.subject_id
                WHERE a.room_id = ?
                AND DATE(a.time_in) = ?
                ORDER BY a.time_in DESC";

$logs_stmt = $conn->prepare($logs_query);
$logs_stmt->execute([$room_id, $date_filter]);
$logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get prev/next dates for navigation
$prev_date = date('Y-m-d', strtotime($date_filter . ' -1 day'));
$next_date = date('Y-m-d', strtotime($date_filter . ' +1 day'));
$formatted_date = date('F d, Y', strtotime($date_filter));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Logs - LabGuard</title>
    <link rel="stylesheet" href="../css/prof.css">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <style>
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
        }
        .date-picker input {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .logs-container {
            margin-top: 30px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
        }
        .log-table {
            width: 100%;
            color: white;
            border-collapse: collapse;
        }
        .log-table th, .log-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .log-table th {
            background-color: rgba(0, 0, 0, 0.3);
        }
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-checkin {
            background-color: #4CAF50;
        }
        .status-checkout {
            background-color: #f44336;
        }
        .user-photo {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .room-selector {
            margin: 20px 0;
            text-align: center;
        }
        .room-selector select {
            padding: 8px;
            border-radius: 5px;
            background-color:rgba(63, 81, 181, 0.8);
            color: white;
            /* border: 1px solid #555; */
        }
        .no-logs {
            text-align: center;
            padding: 20px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <?php if ($_SESSION["role"] === "faculty"): ?>
        <?php include '../sections/nav3.php'; ?>
        <?php include '../sections/fac_nav.php'; ?>
    <?php elseif ($_SESSION["role"] === "professor"): ?>
        <?php include '../sections/nav2.php'; ?>
        <?php include '../sections/prof_nav.php'; ?>
    <?php elseif ($_SESSION["role"] === "admin"): ?>
        <?php include '../sections/nav4.php'; ?>
    <?php endif; ?>

    <div id="main-container">
        <h2>ROOM ATTENDANCE LOGS</h2>

        <?php if ($room): ?>
            <h3>Room <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['room_name']) ?></h3>
        <?php else: ?>
            <h3>No Room Selected</h3>
        <?php endif; ?>

        <!-- Room Selector -->
        <div class="room-selector">
            <form action="" method="GET">
                <select name="room_id" onchange="this.form.submit()">
                    <option value="">Select a Room</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['room_id'] ?>" <?= ($r['room_id'] == $room_id) ? 'selected' : '' ?>>
                            Room <?= htmlspecialchars($r['room_number']) ?> - <?= htmlspecialchars($r['room_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="date" value="<?= htmlspecialchars($date_filter) ?>">
            </form>
        </div>

        <!-- Date Navigation -->
        <div class="date-navigation">
            <a href="?room_id=<?= $room_id ?>&date=<?= $prev_date ?>">Previous Day</a>
            <span class="current-date"><?= $formatted_date ?></span>
            <a href="?room_id=<?= $room_id ?>&date=<?= $next_date ?>">Next Day</a>
        </div>
        
        <!-- Date Picker Form -->
        <form class="date-picker" action="" method="GET">
            <label for="date-select">Select Date: </label>
            <input type="date" id="date-select" name="date" value="<?= htmlspecialchars($date_filter) ?>" onchange="this.form.submit()">
            <input type="hidden" name="room_id" value="<?= $room_id ?>">
        </form>

        <!-- Logs Table -->
        <div class="logs-container">
            <?php if (empty($logs)): ?>
                <div class="no-logs">No attendance records found for this room on the selected date.</div>
            <?php else: ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Subject</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?php if ($log['photo']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($log['photo']) ?>" alt="User Photo" class="user-photo">
                                    <?php else: ?>
                                        <span>No Photo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['full_name']) ?></td>
                                <td><?= htmlspecialchars($log['user_role']) ?></td>
                                <td><?= htmlspecialchars($log['subject_code']) ?></td>
                                <td><?= htmlspecialchars($log['formatted_time_in']) ?></td>
                                <td><?= $log['formatted_time_out'] ? htmlspecialchars($log['formatted_time_out']) : 'Not checked out' ?></td>
                                <td>
                                    <span class="status-indicator <?= ($log['status'] === 'check_in') ? 'status-checkin' : 'status-checkout' ?>"></span>
                                    <?= htmlspecialchars($log['a_status']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 