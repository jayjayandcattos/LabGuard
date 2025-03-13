<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

// Fetch room data
$query = "SELECT * FROM room_tbl";
$stmt = $conn->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="../css/faculty_dash.css">
</head>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let currentLocation = window.location.href;
        let menuItems = document.querySelectorAll(".nav-item .nav-link");

        menuItems.forEach(item => {
            if (item.href === currentLocation) {
                item.classList.add("active");
            }
        }); i
    });
</script>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="bg-dark text-white p-3 vh-100" style="width: 250px;">
            <h4>Faculty Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="faculty_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="view_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="view_professors.php" class="nav-link text-white">Professors Profile</a></li>
                <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white">Schedule Management</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2>Classroom Overview</h2>
            <div class="room-grid">
    <?php foreach ($rooms as $room): ?>
        <div class="room-card">
            <div class="envelope">
                <div class="room-header">
                    <span class="room-status-text"><?= $room['status']; ?></span>
                    <span class="room-status-indicator <?= $room['status'] == 'Vacant' ? 'vacant' : 'occupied'; ?>"></span>
                </div>
                <div class="room-number">
                    Room <?= htmlspecialchars($room['room_number']); ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

