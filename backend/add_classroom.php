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
    $room_num = trim($_POST['room_num']);
    $classroom_name = trim($_POST['classroom_name']);
    $capacity = intval($_POST['capacity']); // Convert input to integer

    // Validate input
    if (empty($room_num) || empty($classroom_name) || empty($capacity)) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
        exit();
    }

    // Check if room number already exists
    $check_query = "SELECT * FROM tbl_crooms WHERE room_num = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $room_num);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Room number already exists!'); window.history.back();</script>";
        exit();
    }

    // Insert new classroom
    $query = "INSERT INTO tbl_crooms (room_num, classroom_name, capacity) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $room_num, $classroom_name, $capacity);

    if ($stmt->execute()) {
        echo "<script>alert('Classroom Added!'); window.location='manage_classrooms.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
