<?php
require_once "db.php";

if (isset($_GET["id"])) {
    $section_id = $_GET["id"];
    
    $query = "DELETE FROM section_tbl WHERE section_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$section_id]);
    
    header("Location: student_secs.php");
    exit();
}
?>