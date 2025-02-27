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

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $query = "DELETE FROM tbl_users WHERE user_id = '$id' AND role_id = 1";

    if ($conn->query($query) === TRUE) {
        echo "<script>alert('Admin Deleted!'); window.location='admin_monitoring.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
