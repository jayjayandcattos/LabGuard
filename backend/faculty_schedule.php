<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}


$query = "SELECT lastname FROM faculty_tbl WHERE employee_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_STR);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);


// Handle schedule addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_schedule"])) {
    $prof_user_id = $_POST["prof_user_id"];
    $subject_id = $_POST["subject_id"];
    $section_id = $_POST["section_id"];
    $room_id = $_POST["room_id"];
    $schedule_time = $_POST["schedule_time"];
    $schedule_end_time = $_POST["schedule_end_time"];
    $schedule_day = $_POST["schedule_day"];

    // Check if schedule_end_time column exists in schedule_tbl
    try {
        $conn->exec("ALTER TABLE schedule_tbl ADD COLUMN IF NOT EXISTS schedule_end_time TIME NOT NULL AFTER schedule_time");
    } catch (Exception $e) {
        // Column might already exist, continue
    }

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
        $_SESSION['error_message'] = "Schedule conflict detected! The room is already booked during this time period.";
        header("Location: faculty_schedule.php");
        exit();
    }

    $query = "INSERT INTO schedule_tbl (prof_user_id, subject_id, section_id, room_id, schedule_time, schedule_end_time, schedule_day) 
              VALUES (:prof_user_id, :subject_id, :section_id, :room_id, :schedule_time, :schedule_end_time, :schedule_day)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        "prof_user_id" => $prof_user_id,
        "subject_id" => $subject_id,
        "section_id" => $section_id,
        "room_id" => $room_id,
        "schedule_time" => $schedule_time,
        "schedule_end_time" => $schedule_end_time,
        "schedule_day" => $schedule_day
    ]);

    $_SESSION['success_message'] = "Schedule added successfully!";
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
          r.room_name,
          TIME_FORMAT(s.schedule_time, '%h:%i %p') as formatted_time,
          TIME_FORMAT(s.schedule_end_time, '%h:%i %p') as end_time
          FROM schedule_tbl s
          JOIN prof_tbl p ON s.prof_user_id = p.prof_user_id
          JOIN subject_tbl sub ON s.subject_id = sub.subject_id
          JOIN section_tbl sec ON s.section_id = sec.section_id
          JOIN room_tbl r ON s.room_id = r.room_id
          ORDER BY s.schedule_day, s.schedule_time";
$schedules = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management</title>
    <link rel="stylesheet" href="../css/colorum.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <script src="../js/facultySched.js" defer></script>

    <style>
        .time-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .time-range span {
            font-weight: bold;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<?php include '../sections/nav3.php'; ?>
<?php include '../sections/fac_nav.php'; ?>

<div id="main-container">
<h2>SCHEDULE MANAGEMENT</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <button id="toggle-form-btn" class="toggle-btn">ADD SCHEDULE</button>

    <div id="schedule-form" class="schedule-form hidden">
        <form method="POST" action="faculty_schedule.php" onsubmit="return validateSchedule()">
            <div class="form-grid">
                <div class="form-group1">
                    <label>Professor</label>
                    <select name="prof_user_id" required>
                        <?php foreach ($professors as $professor): ?>
                            <option value="<?= $professor['prof_user_id'] ?>"><?= htmlspecialchars($professor['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group1">
                    <label>Subject</label>
                    <select name="subject_id" required>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group1">
                    <label>Section</label>
                    <select name="section_id" required>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['section_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group1">
                    <label>Room</label>
                    <select name="room_id" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group1">
                    <label>Schedule Time</label>
                    <div class="time-range">
                        <input type="time" name="schedule_time" required>
                        <span>to</span>
                        <input type="time" name="schedule_end_time" required>
                    </div>
                </div>
                <div class="form-group1">
                    <label>Day</label>
                    <select name="schedule_day" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_schedule">Add Schedule</button>
        </form>
    </div>   

    <!-- Schedule List -->
    <table class="custom-table">
            <thead>
                <tr>
                    <th>Professor</th>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Room</th>
                    <th>Schedule Time</th>
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
                        <td><?= htmlspecialchars($schedule['formatted_time'] . ' - ' . $schedule['end_time']); ?></td>
                        <td><?= htmlspecialchars($schedule['schedule_day']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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
