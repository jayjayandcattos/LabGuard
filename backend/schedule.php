<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Debug settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

date_default_timezone_set('Asia/Manila');

// Add this near the top of the file with your other database modifications
try {
    // Modify schedule_tbl to add end_time column if it doesn't exist
    $conn->exec("ALTER TABLE schedule_tbl ADD COLUMN IF NOT EXISTS schedule_end_time TIME NOT NULL AFTER schedule_time");
    $conn->exec("ALTER TABLE schedule_tbl MODIFY COLUMN schedule_day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL");
} catch (Exception $e) {
    error_log("Error updating schedule table: " . $e->getMessage());
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
        $schedule_end_time = $_POST["schedule_end_time"];
        $schedule_day = $_POST["schedule_day"];

        // Log the values being inserted
        error_log("Inserting schedule with values: " .
            "prof_user_id: $prof_user_id, " .
            "subject_id: $subject_id, " .
            "section_id: $section_id, " .
            "room_id: $room_id, " .
            "schedule_time: $schedule_time, " .
            "schedule_end_time: $schedule_end_time, " .
            "schedule_day: $schedule_day");

        // Check for time conflicts
        $conflict_query = "SELECT COUNT(*) FROM schedule_tbl 
                         WHERE room_id = ? 
                         AND schedule_day = ?
                         AND (
                             (schedule_time <= ? AND schedule_end_time > ?) OR
                             (schedule_time < ? AND schedule_end_time >= ?) OR
                             (schedule_time >= ? AND schedule_time < ?)
                         )";

        $conflict_stmt = $conn->prepare($conflict_query);
        $conflict_stmt->execute([
            $room_id,
            $schedule_day,
            $schedule_end_time,
            $schedule_time,
            $schedule_end_time,
            $schedule_time,
            $schedule_time,
            $schedule_end_time
        ]);

        if ($conflict_stmt->fetchColumn() > 0) {
            error_log("Schedule conflict detected! The room is already booked during this time period.");
            $_SESSION['error_message'] = "Schedule conflict detected! The room is already booked during this time period.";
            header("Location: schedule.php");
            exit();
        }

        // If no conflicts, insert the new schedule
        $query = "INSERT INTO schedule_tbl (prof_user_id, subject_id, section_id, room_id, 
                                          schedule_time, schedule_end_time, schedule_day) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            $prof_user_id,
            $subject_id,
            $section_id,
            $room_id,
            $schedule_time,
            $schedule_end_time,
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
           r.room_name, s.schedule_time, s.schedule_end_time, s.schedule_day
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

if (isset($_SESSION['name'])) {
    $name_parts = explode(' ', $_SESSION['name']);
    $admin_firstname = $name_parts[0];
    $admin_lastname = isset($name_parts[1]) ? $name_parts[1] : 'Unknown';
} else {
    $admin_lastname = 'Unknown';
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/prof.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/classroomManagement.js" defer></script>

    <style>
        #scheduleFormContainer {
            display: none;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        #scheduleFormContainer.show {
            display: block;
        }

        .time-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .time-range span {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include '../sections/nav4.php' ?>
    <?php include '../sections/admin_nav.php' ?>

    <div id="main-container">
        <h2>Professor Schedules</h2>

        <button class="toggle-btn" onclick="toggleForm()">ADD SCHEDULE</button>
        <div id="roomForm" class="hidden-form">
            <div class="card mb-4">

                <h5>Add New Schedule</h5>
                <form action="schedule.php" method="POST" onsubmit="return validateSchedule()">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Professor:</label>
                            <select name="prof_user_id" class="form-control2" required>
                                <option value="">Select Professor</option>
                                <?php foreach ($professors as $prof): ?>
                                    <option value="<?= $prof['prof_user_id'] ?>">
                                        <?= $prof['firstname'] . ' ' . $prof['lastname'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Subject:</label>
                            <select name="subject_id" class="form-control2" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['subject_id'] ?>"><?= $subject['subject_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Section:</label>
                            <select name="section_id" class="form-control2" required>
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= $section['section_id'] ?>"><?= $section['section_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Room:</label>
                            <select name="room_id" class="form-control2" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= $room['room_id'] ?>"><?= $room['room_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Schedule Time:</label>
                            <div class="time-range">
                                <input type="time" name="schedule_time" class="form-control" required>
                                <span>to</span>
                                <input type="time" name="schedule_end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Schedule Day:</label>
                            <select name="schedule_day" class="form-control2" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success">Save Schedule</button>
                    </div>
                </form>
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
                        <td><?= date("h:i A", strtotime($schedule['schedule_time'])) . ' - ' .
                            date("h:i A", strtotime($schedule['schedule_end_time'])) ?></td>
                        <td><?= htmlspecialchars($schedule['schedule_day']) ?></td>
                        <td>
                            <a href="edit_schedule.php?id=<?= $schedule['schedule_id'] ?>"
                                class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- 
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleFormBtn = document.getElementById('toggleFormBtn');
        const scheduleFormContainer = document.getElementById('scheduleFormContainer');
        const cancelBtn = document.getElementById('cancelBtn');
        
        toggleFormBtn.addEventListener('click', function() {
            scheduleFormContainer.classList.toggle('show');
            if (scheduleFormContainer.classList.contains('show')) {
                toggleFormBtn.textContent = 'Hide Form';
            } else {
                toggleFormBtn.textContent = 'Add Schedule';
            }
        });
        
        cancelBtn.addEventListener('click', function() {
            scheduleFormContainer.classList.remove('show');
            toggleFormBtn.textContent = 'Add Schedule';
        });
    });

    function validateSchedule() {
        const startTime = document.querySelector('input[name="schedule_time"]').value;
        const endTime = document.querySelector('input[name="schedule_end_time"]').value;

        if (startTime >= endTime) {
            alert('End time must be later than start time');
            return false;
        }

        return true;
    }
</script> -->

</body>

</html>