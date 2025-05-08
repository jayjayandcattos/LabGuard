<?php
session_start();
require_once "db.php";

// Check if schedule ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid schedule ID.");
}

$schedule_id = $_GET['id'];

// Fetch schedule details
$query = "
    SELECT * FROM schedule_tbl 
    WHERE schedule_id = :schedule_id
";
$stmt = $conn->prepare($query);
$stmt->bindParam(":schedule_id", $schedule_id, PDO::PARAM_INT);
$stmt->execute();
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    die("Schedule not found.");
}

// Fetch dropdown options
$professors = $conn->query("SELECT prof_user_id, firstname, lastname FROM prof_tbl")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $conn->query("SELECT subject_id, subject_name FROM subject_tbl")->fetchAll(PDO::FETCH_ASSOC);
$sections = $conn->query("SELECT section_id, section_name FROM section_tbl")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $conn->query("SELECT room_id, room_name FROM room_tbl")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prof_user_id = $_POST['prof_user_id'];
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];
    $room_id = $_POST['room_id'];
    $schedule_time = $_POST['schedule_time'];
    $schedule_end_time = $_POST['schedule_end_time'];
    $schedule_day = $_POST['schedule_day'];
    
    // Check for time conflicts (excluding the current schedule being edited)
    $conflict_query = "SELECT COUNT(*) FROM schedule_tbl 
                     WHERE room_id = ? 
                     AND schedule_day = ?
                     AND schedule_id != ?
                     AND (
                         (schedule_time <= ? AND schedule_end_time > ?) OR
                         (schedule_time < ? AND schedule_end_time >= ?) OR
                         (schedule_time >= ? AND schedule_time < ?)
                     )";
    
    $conflict_stmt = $conn->prepare($conflict_query);
    $conflict_stmt->execute([
        $room_id,
        $schedule_day,
        $schedule_id,
        $schedule_end_time,
        $schedule_time,
        $schedule_end_time,
        $schedule_time,
        $schedule_time,
        $schedule_end_time
    ]);
    
    if ($conflict_stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Schedule conflict detected! The room is already booked during this time period.";
        header("Location: edit_schedule.php?id=" . $schedule_id);
        exit();
    }
    
    $updateQuery = "
        UPDATE schedule_tbl SET
            prof_user_id = :prof_user_id,
            subject_id = :subject_id,
            section_id = :section_id,
            room_id = :room_id,
            schedule_time = :schedule_time,
            schedule_end_time = :schedule_end_time,
            schedule_day = :schedule_day
        WHERE schedule_id = :schedule_id
    ";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        ':prof_user_id' => $prof_user_id,
        ':subject_id' => $subject_id,
        ':section_id' => $section_id,
        ':room_id' => $room_id,
        ':schedule_time' => $schedule_time,
        ':schedule_end_time' => $schedule_end_time,
        ':schedule_day' => $schedule_day,
        ':schedule_id' => $schedule_id
    ]);

    $_SESSION['success_message'] = "Schedule updated successfully!";
    header("Location: schedule.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
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
    <div class="container mt-5">
        <h2>Edit Schedule</h2>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="return validateSchedule()">
            <div class="mb-3">
                <label>Professor:</label>
                <select name="prof_user_id" class="form-control" required>
                    <?php foreach ($professors as $prof): ?>
                        <option value="<?= $prof['prof_user_id'] ?>" <?= $prof['prof_user_id'] == $schedule['prof_user_id'] ? 'selected' : '' ?>>
                            <?= $prof['firstname'] . ' ' . $prof['lastname'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Subject:</label>
                <select name="subject_id" class="form-control" required>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['subject_id'] ?>" <?= $subject['subject_id'] == $schedule['subject_id'] ? 'selected' : '' ?>>
                            <?= $subject['subject_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Section:</label>
                <select name="section_id" class="form-control" required>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= $section['section_id'] ?>" <?= $section['section_id'] == $schedule['section_id'] ? 'selected' : '' ?>>
                            <?= $section['section_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Room:</label>
                <select name="room_id" class="form-control" required>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['room_id'] ?>" <?= $room['room_id'] == $schedule['room_id'] ? 'selected' : '' ?>>
                            <?= $room['room_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Schedule Time:</label>
                <div class="time-range">
                    <input type="time" name="schedule_time" class="form-control" value="<?= $schedule['schedule_time'] ?>" required>
                    <span>to</span>
                    <input type="time" name="schedule_end_time" class="form-control" value="<?= $schedule['schedule_end_time'] ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label>Schedule Day:</label>
                <select name="schedule_day" class="form-control" required>
                    <?php
                    $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                    foreach ($days as $day): ?>
                        <option value="<?= $day ?>" <?= $day == $schedule['schedule_day'] ? 'selected' : '' ?>>
                            <?= $day ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Update Schedule</button>
            <a href="schedule.php" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
    
    <script>
        function validateSchedule() {
            const startTime = document.querySelector('input[name="schedule_time"]').value;
            const endTime = document.querySelector('input[name="schedule_end_time"]').value;

            if (startTime >= endTime) {
                alert('End time must be later than start time');
                return false;
            }

            return true;
        }
    </script>
</body>
</html>
