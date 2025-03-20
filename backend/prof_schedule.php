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
    <link rel="stylesheet" href="../css/prof.css">
</head>
<body>
    <div class="professor-header">
        <h1>PROFESSOR PROFILE</h1>
        <p>WELCOME PROFESSOR <?= htmlspecialchars($prof_lastname); ?>!</p>
    </div>
    <?php include 'prof_nav.php'?>



        <!-- Main Content -->
        <div id="main" class="container-fluid p-3" style="margin-right: 10%; width: 70%;">
            <h2>My Teaching Schedule</h2>
            <div class="row mb-3">
                <div class="col-md-6">
                    <!-- Empty div to maintain spacing -->
                </div>    

                <div class="col-mid-2">
    <table id="tabs" class=" table-bordered">
        <div id="theads" class="table table-header-container">
            <div id="table-header">Monday</div>
            <div id="table-header">Tuesday</div>
            <div id="table-header">Wednesday</div>
            <div id="table-header">Friday</div>
            <div id="table-header">Thursday</div>
            <div id="table-header">Saturday</div>
        </div>
        <div id="for-the-boxes">
            <?php foreach ($schedules as $schedule): ?>
                <div id="contents-of-boxes">
                    <div><?= htmlspecialchars($schedule['room_name']); ?></div>
                    <div><?= htmlspecialchars($schedule['formatted_time']); ?></div>
                    <div><?= htmlspecialchars($schedule['formatted_time_in']); ?></div>
                    <div><?= htmlspecialchars($schedule['formatted_time_out']); ?></div>
                    <div><?= htmlspecialchars($schedule['subject_name']); ?></div>
                    <div><?= htmlspecialchars($schedule['section_name']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </table>
</div>

            </div>
        </div>
    
</body>
</html> 