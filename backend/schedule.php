<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Debug settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

// Add this near the top of the file with your other database modifications
try {
    $conn->exec("ALTER TABLE schedule_tbl MODIFY COLUMN schedule_day ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL");
    error_log("Added Sunday back to schedule_day options");
} catch (Exception $e) {
    error_log("Error updating schedule_day enum: " . $e->getMessage());
}

// Handle schedule addition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Log the POST data for debugging
        error_log("Schedule POST data: " . print_r($_POST, true));
        
        $prof_user_id = $_POST["prof_user_id"];
        $subject_id = $_POST["subject_id"];
        $section_id = $_POST["section_id"];
        $room_id = $_POST["room_id"];
        $schedule_time = $_POST["schedule_time"];
        $time_in = $_POST["time_in"];
        $time_out = $_POST["time_out"];
        $schedule_day = $_POST["schedule_day"];

        // Log the values being inserted
        error_log("Inserting schedule with values: " . 
                 "prof_user_id: $prof_user_id, " .
                 "subject_id: $subject_id, " .
                 "section_id: $section_id, " .
                 "room_id: $room_id, " .
                 "schedule_time: $schedule_time, " .
                 "time_in: $time_in, " .
                 "time_out: $time_out, " .
                 "schedule_day: $schedule_day");

        $query = "INSERT INTO schedule_tbl (prof_user_id, subject_id, section_id, room_id, 
                                          schedule_time, time_in, time_out, schedule_day) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            $prof_user_id,
            $subject_id,
            $section_id,
            $room_id,
            $schedule_time,
            $time_in,
            $time_out,
            $schedule_day
        ]);

        if ($result) {
            error_log("Schedule inserted successfully");
            $_SESSION['success_message'] = "Schedule added successfully!";
        } else {
            error_log("Schedule insertion failed");
            $_SESSION['error_message'] = "Failed to add schedule.";
        }

        // Redirect to prevent form resubmission
        header("Location: schedule.php");
        exit();
    } catch (PDOException $e) {
        error_log("Schedule addition error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error adding schedule: " . $e->getMessage();
        header("Location: schedule.php");
        exit();
    }
}

// Display messages if they exist
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Fetch all schedules with professor, subject, section, and room details
$query = "
    SELECT s.schedule_id, p.firstname, p.lastname, subj.subject_name, sec.section_name, 
           r.room_name, s.schedule_time, s.time_in, s.time_out, s.schedule_day
    FROM schedule_tbl s
    JOIN prof_tbl p ON s.prof_user_id = p.prof_user_id
    JOIN subject_tbl subj ON s.subject_id = subj.subject_id
    JOIN section_tbl sec ON s.section_id = sec.section_id
    JOIN room_tbl r ON s.room_id = r.room_id
    ORDER BY s.schedule_day, s.schedule_time";

$stmt = $conn->prepare($query);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Professors
$profQuery = "SELECT prof_user_id, firstname, lastname FROM prof_tbl";
$profStmt = $conn->prepare($profQuery);
$profStmt->execute();
$professors = $profStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Subjects
$subjQuery = "SELECT subject_id, subject_name FROM subject_tbl";
$subjStmt = $conn->prepare($subjQuery);
$subjStmt->execute();
$subjects = $subjStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Sections
$secQuery = "SELECT section_id, section_name FROM section_tbl";
$secStmt = $conn->prepare($secQuery);
$secStmt->execute();
$sections = $secStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Rooms
$roomQuery = "SELECT room_id, room_name FROM room_tbl";
$roomStmt = $conn->prepare($roomQuery);
$roomStmt->execute();
$rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="bg-dark text-white p-3" style="width: 250px; height: 100vh;">
            <h4>Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link text-white">Classroom</a></li>
                <li class="nav-item"><a href="schedule.php" class="nav-link text-white">Schedule</a></li>
                <li class="nav-item"><a href="professors.php" class="nav-link text-white">Professors</a></li>
                <li class="nav-item"><a href="faculty.php" class="nav-link text-white">Faculty</a></li>
                <li class="nav-item"><a href="students.php" class="nav-link text-white">Students</a></li>
                <li class="nav-item"><a href="student_subs.php" class="nav-link text-white">Student Subjects</a></li>
                <li class="nav-item"><a href="student_secs.php" class="nav-link text-white">Student Sections</a></li>
                <li class="nav-item"><a href="admin.php" class="nav-link text-white">Admin</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container mt-4">
            <h2>Professor Schedules</h2>
            
            <!-- Add Schedule Button -->
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addScheduleModal">Add Schedule</button>
                <!-- Add Schedule Modal -->
                <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addScheduleModalLabel">Add Schedule</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="schedule.php" method="POST">
                                    <label>Professor:</label>
                                    <select name="prof_user_id" class="form-control" required>
                                        <option value="">Select Professor</option>
                                        <?php foreach ($professors as $prof): ?>
                                            <option value="<?= $prof['prof_user_id'] ?>"><?= $prof['firstname'] . ' ' . $prof['lastname'] ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Subject:</label>
                                    <select name="subject_id" class="form-control" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?= $subject['subject_id'] ?>"><?= $subject['subject_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Section:</label>
                                    <select name="section_id" class="form-control" required>
                                        <option value="">Select Section</option>
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= $section['section_id'] ?>"><?= $section['section_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Room:</label>
                                    <select name="room_id" class="form-control" required>
                                        <option value="">Select Room</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?= $room['room_id'] ?>"><?= $room['room_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Schedule Time:</label>
                                    <input type="time" name="schedule_time" class="form-control" required>

                                    <label>Time In:</label>
                                    <input type="time" name="time_in" class="form-control" required>

                                    <label>Time Out:</label>
                                    <input type="time" name="time_out" class="form-control" required>

                                    <label>Schedule Day:</label>
                                    <select name="schedule_day" class="form-control" required>
                                        <option value="Sunday">Sunday</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                    </select>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success">Save Schedule</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['firstname'] . ' ' . $schedule['lastname']) ?></td>
                            <td><?= htmlspecialchars($schedule['subject_name']) ?></td>
                            <td><?= htmlspecialchars($schedule['section_name']) ?></td>
                            <td><?= htmlspecialchars($schedule['room_name']) ?></td>
                            <td><?= date("h:i A", strtotime($schedule['schedule_time'])) ?></td>
                            <td><?= $schedule['time_in'] ? date("h:i A", strtotime($schedule['time_in'])) : 'Not yet tapped' ?></td>
                            <td><?= $schedule['time_out'] ? date("h:i A", strtotime($schedule['time_out'])) : 'Not yet tapped' ?></td>
                            <td><?= htmlspecialchars($schedule['schedule_day']) ?></td>
                            <td>
                                <a href="edit_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
