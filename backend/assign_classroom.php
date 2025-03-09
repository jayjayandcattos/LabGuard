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

// Fetch classrooms
$classrooms = $conn->query("SELECT * FROM tbl_crooms");

// Fetch professors (Role: Professor)
$professors = $conn->query("SELECT * FROM tbl_users WHERE role_id = (SELECT role_id FROM tbl_roles WHERE role_name = 'Professor')");

// Fetch students (Role: Student)
$students = $conn->query("SELECT * FROM tbl_users WHERE role_id = (SELECT role_id FROM tbl_roles WHERE role_name = 'Student')");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Assign Classroom</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Assign Classroom to Student & Professor</h2>

    <form action="save_assignment.php" method="POST">
        <label>Classroom:</label>
        <select name="croom_id" required>
            <?php while ($row = $classrooms->fetch_assoc()) { ?>
                <option value="<?= $row['croom_id'] ?>"><?= $row['classroom_name'] ?></option>
            <?php } ?>
        </select>

        <label>Professor:</label>
        <select name="professor_id" required>
            <?php while ($row = $professors->fetch_assoc()) { ?>
                <option value="<?= $row['user_id'] ?>"><?= $row['lastname'] . ', ' . $row['firstname'] ?></option>
            <?php } ?>
        </select>

        <label>Student:</label>
        <select name="student_id" required>
            <?php while ($row = $students->fetch_assoc()) { ?>
                <option value="<?= $row['user_id'] ?>"><?= $row['lastname'] . ', ' . $row['firstname'] ?></option>
            <?php } ?>
        </select>

        <label>Schedule Time:</label>
        <input type="datetime-local" name="schedule_time" required>

        <button type="submit">Assign</button>
    </form>

    <br>
    <a href="admin_dashboard.php"><button>Back to Dashboard</button></a>

</body>
</html>
