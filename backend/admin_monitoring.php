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

// Fetch admins from tbl_users where role_id = 3 (Admin)
$query = "SELECT user_id, student_id, lastname, firstname, email FROM tbl_users WHERE role_id = 1";
$admins = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Monitoring</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Admin Monitoring</h2>

    <table border="1">
        <tr>
            <th>User ID</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $admins->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($row['user_id']) ?></td>
            <td><?= htmlspecialchars($row['student_id']) ?></td>
            <td><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
                <a href="edit_admin.php?id=<?= $row['user_id'] ?>">Edit</a> |
                <a href="delete_admin.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <h3>Add New Admin</h3>
    <form action="add_admin.php" method="POST">
        <input type="text" name="student_id" placeholder="Student ID" required>
        <input type="text" name="lastname" placeholder="Last Name" required>
        <input type="text" name="firstname" placeholder="First Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Add Admin</button>
    </form>

    <br>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</body>
</html>
