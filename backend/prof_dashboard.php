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
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/prof.css">
</head>
<body>
    <?php include 'prof_nav.php'?>

        <!-- Main Content -->
       
            <!-- Classroom Overview -->
            <div id="main-container" >
                <h4 id="overview-title">Classroom Overview</h4>
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
  </div>
</body>
</html>