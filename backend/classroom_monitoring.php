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

// Fetch assigned classrooms
$sql = "SELECT c.classroom_name, s.schedule_time, 
               u1.lastname AS prof_last, u1.firstname AS prof_first, 
               u2.lastname AS stud_last, u2.firstname AS stud_first 
        FROM tbl_crooms c
        LEFT JOIN tbl_classroom_schedule s ON c.croom_id = s.croom_id
        LEFT JOIN tbl_users u1 ON s.professor_id = u1.user_id
        LEFT JOIN tbl_users u2 ON s.student_id = u2.user_id
        ORDER BY c.classroom_name";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Classroom Monitoring</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Classroom Monitoring</h2>

    <table border="1">
        <tr>
            <th>Classroom</th>
            <th>Status</th>
            <th>Professor</th>
            <th>Student</th>
            <th>Scheduled Time</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['classroom_name'] ?></td>
            <td style="color: <?= ($row['schedule_time']) ? 'red' : 'green' ?>;">
                <?= ($row['schedule_time']) ? "Occupied" : "Available" ?>
            </td>
            <td><?= ($row['schedule_time']) ? $row['prof_last'] . ', ' . $row['prof_first'] : '-' ?></td>
            <td><?= ($row['schedule_time']) ? $row['stud_last'] . ', ' . $row['stud_first'] : '-' ?></td>
            <td><?= ($row['schedule_time']) ? $row['schedule_time'] : '-' ?></td>
        </tr>
        <?php } ?>
    </table>

    <br>
    <a href="admin_dashboard.php"><button>Back to Dashboard</button></a>

</body>
</html>
