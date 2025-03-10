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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="bg-dark text-white p-3 vh-100" style="width: 250px;">
            <h4>Professor Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="prof_dashboard.php" class="nav-link text-white active">Classrooms</a></li>
                <li class="nav-item"><a href="prof_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="prof_schedule.php" class="nav-link text-white">My Schedule</a></li>
                <li class="nav-item"><a href="prof_attendance.php" class="nav-link text-white">Attendance</a></li>
                <li class="nav-item"><a href="prof_profile.php" class="nav-link text-white">My Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2>Welcome, <?php echo $_SESSION["name"]; ?></h2>
            
            <!-- Classroom Overview -->
            <div class="card p-3 mb-4">
                <h4>Classroom Overview</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Room Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?= htmlspecialchars($room['room_number']); ?></td>
                                <td><?= htmlspecialchars($room['room_name']); ?></td>
                                <td class="<?= $room['status'] == 'Vacant' ? 'text-success' : 'text-danger'; ?>">
                                    <?= htmlspecialchars($room['status']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
