<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

// Handle room addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_room"])) {
    $room_number = $_POST["room_number"];
    $room_name = $_POST["room_name"];
    $status = $_POST["status"];

    $query = "INSERT INTO room_tbl (room_number, room_name, status) VALUES (:room_number, :room_name, :status)";
    $stmt = $conn->prepare($query);
    $stmt->execute(["room_number" => $room_number, "room_name" => $room_name, "status" => $status]);

    header("Location: admin_dashboard.php");
    exit();
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="bg-dark text-white p-3 vh-100" style="width: 250px;">
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
        <div class="container-fluid p-4">
            <h2>Classroom Management</h2>

            <!-- Add Room Form -->
            <div class="card p-3 mb-4">
                <h4>Add New Room</h4>
                <form method="POST" action="">
                    <div class="mb-2">
                        <label>Room Number</label>
                        <input type="text" name="room_number" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Room Name</label>
                        <input type="text" name="room_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Vacant">Vacant</option>
                            <option value="Occupied">Occupied</option>
                        </select>
                    </div>
                    <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
                </form>
            </div>

            <!-- Room List Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Name</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                            <td>
                                <a href="edit_classroom.php?id=<?= htmlspecialchars($room['room_id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_classroom.php?id=<?= $room['room_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
