<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendanceDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all classrooms
$classrooms = $conn->query("SELECT * FROM tbl_crooms");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Classrooms</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Manage Classrooms</h2>

    <!-- Add Classroom Form -->
    <h3>Add New Classroom</h3>
    <form action="add_classroom.php" method="POST">
        <input type="text" name="room_num" placeholder="Room Number" required>
        <input type="text" name="classroom_name" placeholder="Classroom Name" required>
        <input type="number" name="capacity" placeholder="Capacity" required>
        <button type="submit">Add Classroom</button>
    </form>

    <!-- Classrooms Table -->
    <h3>Classroom List</h3>
    <table border="1">
        <tr>
            <th>Room Number</th>
            <th>Classroom Name</th>
            <th>Capacity</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $classrooms->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($row['room_num']) ?></td>
            <td><?= htmlspecialchars($row['classroom_name']) ?></td>
            <td><?= htmlspecialchars($row['capacity']) ?></td>
            <td>
                <a href="edit_classroom.php?id=<?= $row['croom_id'] ?>">Edit</a> |
                <a href="delete_classroom.php?id=<?= $row['croom_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <br>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</body>
</html>
