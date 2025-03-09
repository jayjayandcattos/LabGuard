<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendanceDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $middle_initial = $_POST['middle_initial'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $rfid_tag = $_POST['rfid_tag'];

    // Insert into database
    $sql = "INSERT INTO tbl_users (student_id, lastname, firstname, middle_initial, email, role_id, rfid_tag) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssis", $student_id, $lastname, $firstname, $middle_initial, $email, $role_id, $rfid_tag);

    if ($stmt->execute()) {
        echo "<script>alert('User added successfully!'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
