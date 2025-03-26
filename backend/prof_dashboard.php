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

// Fetch Professor's Last Name
$prof_user_id = $_SESSION["user_id"];
$prof_query = "SELECT lastname FROM prof_tbl WHERE prof_user_id = :prof_user_id";
$prof_stmt = $conn->prepare($prof_query);
$prof_stmt->execute(['prof_user_id' => $prof_user_id]);

$professor = $prof_stmt->fetch(PDO::FETCH_ASSOC);
$prof_lastname = $professor ? $professor['lastname'] : "Unknown";

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
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/prof.css">
</head>

<body>
    <?php include '../sections/nav2.php'; ?>
    <?php include '../sections/prof_nav.php'; ?>

    <div id="main-container">
        <h2>CLASSROOM OVERVIEW</h2>
        <div class="room-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="room-card">
                    <span class="room-status-indicator <?= $room['status'] == 'Vacant' ? 'vacant' : 'occupied'; ?>"></span>
                    <div class="folder">
                        <div class="room-header">
                            <span class="room-status-text"><?= $room['status']; ?></span>
                        </div>
                        <div class="room-number">
                            Room <?= htmlspecialchars($room['room_number']); ?>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

</body>

</html>