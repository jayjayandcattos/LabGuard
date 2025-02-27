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

$croom_id = $_POST['croom_id'];
$professor_id = $_POST['professor_id'];
$student_id = $_POST['student_id'];
$schedule_time = $_POST['schedule_time'];

// Insert into tbl_classroom_schedule
$sql = "INSERT INTO tbl_classroom_schedule (croom_id, professor_id, student_id, schedule_time)
        VALUES ('$croom_id', '$professor_id', '$student_id', '$schedule_time')";

if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Classroom Assigned Successfully!'); window.location.href='assign_classroom.php';</script>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
