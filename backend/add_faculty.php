<?php
require_once "db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST["employee_id"];
    $lastname = $_POST["lastname"];
    $firstname = $_POST["firstname"];
    $mi = $_POST["mi"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $rfid_tag = $_POST["rfid_tag"];

    // Check for duplicates
    $check = $conn->prepare("SELECT * FROM faculty_tbl WHERE employee_id = ? OR email = ? OR rfid_tag = ?");
    $check->execute([$employee_id, $email, $rfid_tag]);

    if ($check->rowCount() > 0) {
        echo "<script>alert('Duplicate entry detected! Employee ID, Email, or RFID already exists.'); window.location.href='faculty.php';</script>";
        exit();
    }

    // Insert if no duplicates
    $query = "INSERT INTO faculty_tbl (employee_id, role_id, lastname, firstname, mi, email, password, rfid_tag) VALUES (?, 2, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$employee_id, $lastname, $firstname, $mi, $email, $password, $rfid_tag]);

    header("Location: faculty.php");
    exit();
}
?>
