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

// Fetch room data
$query = "SELECT * FROM room_tbl";
$stmt = $conn->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/prof_dashboard.css">
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class=" text-white p-3 ">

            <ul class=" nav flex-column">
                <li class="nav-item"><a href="prof_dashboard.php" class="nav-link text-white active">Classrooms</a></li>
                <li class="nav-item"><a href="prof_students.php" class="nav-link text-white ">Students Profile</a></li>
                <li class="nav-item"><a href="prof_schedule.php" class="nav-link text-white">My Schedule</a></li>
                <li class="nav-item"><a href="prof_attendance.php" class="nav-link text-white">Attendance</a></li>
                <li class="nav-item"><a href="prof_profile.php" class="nav-link text-white">My Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main>
            <h1>PROFESSOR PROFILE</h1>
            <p>WELCOME PROFESSOR <?= htmlspecialchars($_SESSION["name"]); ?>!</p>

            <div id="classroom-overview">
                <h2>CLASSROOM OVERVIEW</h2>
                <div class="room-container">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-box <?= $room['status'] == 'Vacant' ? 'vacant' : 'occupied'; ?>">
                            <p class="status"><?= htmlspecialchars($room['status']); ?></p>
                            <div class="status-light <?= $room['status'] == 'Vacant' ? 'vacant-light' : 'occupied-light'; ?>"></div>
                            <h3>ROOM <?= htmlspecialchars($room['room_number']); ?></h3>
                            <?php if ($room['status'] == 'Vacant'): ?>
                                <button class="occupy-btn">OCCUPY THIS ROOM</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>