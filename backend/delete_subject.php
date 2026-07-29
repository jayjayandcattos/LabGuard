<?php
require_once "db.php";

if (isset($_GET["id"])) {
    $subject_id = $_GET["id"];
    
    $query = "DELETE FROM subject_tbl WHERE subject_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$subject_id]);
    
    header("Location: student_subs.php");
    exit();
}
?>