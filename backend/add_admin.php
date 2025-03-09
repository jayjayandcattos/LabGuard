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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $query = "INSERT INTO tbl_users (student_id, lastname, firstname, email, password, role_id) 
              VALUES ('$student_id', '$lastname', '$firstname', '$email', '$password', 1)";
    
    if ($conn->query($query) === TRUE) {
        echo "<script>alert('Admin Added!'); window.location='admin_monitoring.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
