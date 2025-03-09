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

$classroom_id = $_GET['id'];
$query = "DELETE FROM tbl_crooms WHERE croom_id = $classroom_id";

if ($conn->query($query) === TRUE) {
    echo "<script>alert('Classroom Deleted!'); window.location='manage_classrooms.php';</script>";
} else {
    echo "Error: " . $conn->error;
}
?>
