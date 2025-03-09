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

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch admins
$admins = $conn->query("SELECT * FROM tbl_users WHERE role_id = 1");

// Fetch professors
$professors = $conn->query("SELECT * FROM tbl_users WHERE role_id = 2");

// Fetch students
$students = $conn->query("SELECT * FROM tbl_users WHERE role_id = 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Admin Dashboard - RFID Management</h2>

    <!-- Admins Table -->
    <h3>Admin Users</h3>
    <table border="1">
        <tr>
            <th>User ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Email</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $admins->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['lastname'] ?></td>
            <td><?= $row['firstname'] ?></td>
            <td><?= $row['email'] ?></td>
            <td>
                <a href="edit_admin.php?id=<?= $row['user_id'] ?>">Edit</a> |
                <a href="delete_admin.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- Professors Table -->
    <h3>Professors</h3>
    <table border="1">
        <tr>
            <th>User ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Email</th>
            <th>RFID Tag</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $professors->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['lastname'] ?></td>
            <td><?= $row['firstname'] ?></td>
            <td><?= $row['email'] ?></td>
            <td><?= $row['rfid_tag'] ?></td>
            <td>
                <a href="edit_user.php?id=<?= $row['user_id'] ?>">Edit</a> |
                <a href="delete_user.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- Students Table -->
    <h3>Students</h3>
    <table border="1">
        <tr>
            <th>User ID</th>
            <th>Student ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Email</th>
            <th>RFID Tag</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $students->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['student_id'] ?></td>
            <td><?= $row['lastname'] ?></td>
            <td><?= $row['firstname'] ?></td>
            <td><?= $row['email'] ?></td>
            <td><?= $row['rfid_tag'] ?></td>
            <td>
                <a href="edit_user.php?id=<?= $row['user_id'] ?>">Edit</a> |
                <a href="delete_user.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <h3>Add New User</h3>
    <form action="add_user.php" method="POST">
        <input type="text" name="student_id" placeholder="Student ID" required>
        <input type="text" name="lastname" placeholder="Last Name" required>
        <input type="text" name="firstname" placeholder="First Name" required>
        <input type="text" name="middle_initial" placeholder="Middle Initial">
        <input type="email" name="email" placeholder="Email" required>
        <select name="role_id">
            <option value="2">Professor</option>
            <option value="3">Student</option>
        </select>
        <input type="text" name="rfid_tag" placeholder="RFID Tag">
        <button type="submit">Add User</button>
    </form>

    <a href="classroom_monitoring.php"><button>Classroom Monitoring</button></a>
    <a href="manage_classrooms.php"><button>Manage Classrooms</button></a>
    <a href="admin_monitoring.php"><button>Admin Monitoring</button></a>
    <a href="assign_classroom.php"><button>Assign Classrooms</button></a>
    <li><a href="logout.php">Logout</a></li>

</body>
</html>
