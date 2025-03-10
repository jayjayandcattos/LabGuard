<?php
require_once "db.php";

if (!isset($_GET['id'])) {
    die("Error: Faculty ID missing.");
}

$id = $_GET['id'];
$query = "SELECT * FROM faculty_tbl WHERE faculty_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faculty) {
    die("Error: Faculty not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = $_POST["lastname"];
    $firstname = $_POST["firstname"];
    $mi = $_POST["mi"];
    $email = $_POST["email"];
    $rfid_tag = $_POST["rfid_tag"];

    $query = "UPDATE faculty_tbl SET lastname = ?, firstname = ?, mi = ?, email = ?, rfid_tag = ? WHERE faculty_user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$lastname, $firstname, $mi, $email, $rfid_tag, $id]);

    header("Location: faculty.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Faculty</title>
</head>
<body>
    <h2>Edit Faculty</h2>
    <form method="POST">
        Last Name: <input type="text" name="lastname" value="<?= $faculty['lastname'] ?>" required><br>
        First Name: <input type="text" name="firstname" value="<?= $faculty['firstname'] ?>" required><br>
        Middle Initial: <input type="text" name="mi" value="<?= $faculty['mi'] ?>"><br>
        Email: <input type="email" name="email" value="<?= $faculty['email'] ?>" required><br>
        RFID Tag: <input type="text" name="rfid_tag" value="<?= $faculty['rfid_tag'] ?>" required><br>
        <input type="submit" value="Update Faculty">
    </form>
</body>
</html>