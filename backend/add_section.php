<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["section_name"], $_POST["section_level"])) {
        $section_name = $_POST["section_name"];
        $section_level = $_POST["section_level"];

        // Check for duplicate section_name
        $checkQuery = "SELECT COUNT(*) FROM section_tbl WHERE section_name = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$section_name]);

        if ($checkStmt->fetchColumn() > 0) {
            echo "<script>alert('Section name already exists!'); window.location.href = 'student_secs.php';</script>";
            exit();
        }

        $query = "INSERT INTO section_tbl (section_name, section_level) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$section_name, $section_level]);

        header("Location: student_secs.php");
        exit();
    } else {
        echo "<script>alert('Missing required fields.'); window.location.href = 'student_secs.php';</script>";
        exit();
    }
}
?>
