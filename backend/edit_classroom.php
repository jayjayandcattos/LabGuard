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
$classroom = $conn->query("SELECT * FROM tbl_crooms WHERE croom_id = $classroom_id")->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $classroom_name = $conn->real_escape_string($_POST['classroom_name']);
    $query = "UPDATE tbl_crooms SET classroom_name = '$classroom_name' WHERE croom_id = $classroom_id";

    if ($conn->query($query) === TRUE) {
        echo "<script>alert('Classroom Updated!'); window.location='manage_classrooms.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Classroom</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Edit Classroom</h2>
    <form method="POST">
        <input type="text" name="classroom_name" value="<?= $classroom['classroom_name'] ?>" required>
        <button type="submit">Update</button>
    </form>
    <br>
    <a href="manage_classrooms.php">Back</a>
</body>
</html>
