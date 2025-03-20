<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Handle schedule addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_schedule"])) {
    $prof_user_id = $_POST["prof_user_id"];
    $subject_id = $_POST["subject_id"];
    $section_id = $_POST["section_id"];
    $room_id = $_POST["room_id"];
    $schedule_time = $_POST["schedule_time"];
    $time_in = $_POST["time_in"];
    $time_out = $_POST["time_out"];
    $schedule_day = $_POST["schedule_day"];

    $query = "INSERT INTO schedule_tbl (prof_user_id, subject_id, section_id, room_id, schedule_time, time_in, time_out, schedule_day) 
              VALUES (:prof_user_id, :subject_id, :section_id, :room_id, :schedule_time, :time_in, :time_out, :schedule_day)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        "prof_user_id" => $prof_user_id,
        "subject_id" => $subject_id,
        "section_id" => $section_id,
        "room_id" => $room_id,
        "schedule_time" => $schedule_time,
        "time_in" => $time_in,
        "time_out" => $time_out,
        "schedule_day" => $schedule_day
    ]);

    header("Location: faculty_schedule.php");
    exit();
}

// Fetch necessary data for dropdowns
$professors = $conn->query("SELECT prof_user_id, CONCAT(lastname, ', ', firstname) AS name FROM prof_tbl")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $conn->query("SELECT subject_id, subject_name FROM subject_tbl")->fetchAll(PDO::FETCH_ASSOC);
$sections = $conn->query("SELECT section_id, section_name FROM section_tbl")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT room_id, room_name FROM room_tbl WHERE status = 'Vacant'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing schedules
$query = "SELECT s.*, 
          CONCAT(p.lastname, ', ', p.firstname) AS professor_name,
          sub.subject_name,
          sec.section_name,
          r.room_name
          FROM schedule_tbl s
          JOIN prof_tbl p ON s.prof_user_id = p.prof_user_id
          JOIN subject_tbl sub ON s.subject_id = sub.subject_id
          JOIN section_tbl sec ON s.section_id = sec.section_id
          JOIN room_tbl r ON s.room_id = r.room_id";
$schedules = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/colorum.css">
    <style>
        .nav-link.active {
            background-color: #152569 !important; /* Bootstrap success color */
        }
    </style>

</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="btn-panel" style="width: 250px;">
           
            <ul class="nav flex-column">
                <li class="nav-item"><a href="faculty_overview.php" class="nav-link text-white">Overview</a></li>
                <li class="nav-item"><a href="faculty_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="view_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="view_professors.php" class="nav-link text-white">Professors Profile</a></li>
                <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white active">Schedule Management</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            

            <!-- Add Schedule Form -->
            <div class="card p-3 mb-4">
                
                <form method="POST" action="faculty_schedule.php">
                    <div class="row">
                        <div class="infopanel">
                            <label>Professor</label>
                            <select name="prof_user_id" class="form-control" required>
                                <?php foreach ($professors as $professor): ?>
                                    <option value="<?= $professor['prof_user_id'] ?>"><?= htmlspecialchars($professor['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Subject</label>
                            <select name="subject_id" class="form-control" required>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Section</label>
                            <select name="section_id" class="form-control" required>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['section_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Room</label>
                            <select name="room_id" class="form-control" required>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Schedule Time</label>
                            <input type="time" name="schedule_time" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Time In</label>
                            <input type="time" name="time_in" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Time Out</label>
                            <input type="time" name="time_out" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Day</label>
                            <select name="schedule_day" class="form-control" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
                </form>
            </div>

            <!-- Schedule List -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Professor</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Room</th>
                        <th>Schedule Time</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Day</th>
                    </tr>
                </thead>

                    

                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['professor_name']); ?></td>
                            <td><?= htmlspecialchars($schedule['subject_name']); ?></td>
                            <td><?= htmlspecialchars($schedule['section_name']); ?></td>
                            <td><?= htmlspecialchars($schedule['room_name']); ?></td>
                            <td><?= htmlspecialchars($schedule['schedule_time']); ?></td>
                            <td><?= htmlspecialchars($schedule['time_in']); ?></td>
                            <td><?= htmlspecialchars($schedule['time_out']); ?></td>
                            <td><?= htmlspecialchars($schedule['schedule_day']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 