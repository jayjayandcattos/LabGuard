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
    $query = "SELECT * FROM tbl_users WHERE user_id = '$id' AND role_id = 3";
    $result = $conn->query($query);
    $admin = $result->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $conn->real_escape_string($_POST['user_id']);
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $email = $conn->real_escape_string($_POST['email']);

    $query = "UPDATE tbl_users SET student_id='$student_id', lastname='$lastname', firstname='$firstname', email='$email' WHERE user_id='$id'";
    
    if ($conn->query($query) === TRUE) {
        echo "<script>alert('Admin Updated!'); window.location='admin_monitoring.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Admin</title>
</head>
<body>
    <h2>Edit Admin</h2>
    <form method="POST">
        <input type="hidden" name="user_id" value="<?= $admin['user_id'] ?>">
        <input type="text" name="student_id" value="<?= htmlspecialchars($admin['student_id']) ?>" required>
        <input type="text" name="lastname" value="<?= htmlspecialchars($admin['lastname']) ?>" required>
        <input type="text" name="firstname" value="<?= htmlspecialchars($admin['firstname']) ?>" required>
        <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
        <button type="submit">Update Admin</button>
    </form>
    <br>
    <a href="admin_monitoring.php">Back</a>
</body>
</html>
