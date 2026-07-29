<?php
require_once "db.php";

if (!isset($_GET['id'])) {
    die("Error: Faculty ID missing.");
}

$id = $_GET['id'];
$query = "DELETE FROM faculty_tbl WHERE faculty_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);

header("Location: faculty.php");
exit();
?>