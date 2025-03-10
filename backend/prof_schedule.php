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
$query = "SELECT s.*, 
          CONCAT(p.lastname, ', ', p.firstname) AS professor_name,
          sub.subject_name,
          sec.section_name,
          r.room_name,
          DATE_FORMAT(s.schedule_time, '%h:%i %p') as formatted_time,
          DATE_FORMAT(s.time_in, '%h:%i %p') as formatted_time_in,
          DATE_FORMAT(s.time_out, '%h:%i %p') as formatted_time_out
          FROM schedule_tbl s
          JOIN prof_tbl p ON s.prof_user_id = p.prof_user_id
          JOIN subject_tbl sub ON s.subject_id = sub.subject_id
          JOIN section_tbl sec ON s.section_id = sec.section_id
          JOIN room_tbl r ON s.room_id = r.room_id
          WHERE p.employee_id = :prof_id OR p.prof_user_id = :prof_user_id
          ORDER BY FIELD(s.schedule_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), 
          s.schedule_time";

$stmt = $conn->prepare($query);
$stmt->execute([
    "prof_id" => $_SESSION["user_id"],
    "prof_user_id" => $prof_user_id
]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="bg-dark text-white p-3 vh-100" style="width: 250px;">
            <h4>Professor Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="prof_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="prof_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="prof_schedule.php" class="nav-link text-white active">My Schedule</a></li>
                <li class="nav-item"><a href="prof_attendance.php" class="nav-link text-white">Attendance</a></li>
                <li class="nav-item"><a href="prof_profile.php" class="nav-link text-white">My Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2>My Teaching Schedule</h2>
            <div class="card p-3">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Subject</th>
                            <th>Section</th>
                            <th>Room</th>
                            <th>Schedule Time</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?= htmlspecialchars($schedule['schedule_day']); ?></td>
                                <td><?= htmlspecialchars($schedule['subject_name']); ?></td>
                                <td><?= htmlspecialchars($schedule['section_name']); ?></td>
                                <td><?= htmlspecialchars($schedule['room_name']); ?></td>
                                <td><?= htmlspecialchars($schedule['formatted_time']); ?></td>
                                <td><?= htmlspecialchars($schedule['formatted_time_in']); ?></td>
                                <td><?= htmlspecialchars($schedule['formatted_time_out']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 