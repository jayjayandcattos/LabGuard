<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["section_name"], $_POST["section_level"])) {
        $section_name = $_POST["section_name"];
        $section_level = $_POST["section_level"];

        $query = "INSERT INTO section_tbl (section_name, section_level) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$section_name, $section_level]);

        header("Location: student_secs.php");
        exit();
    } else {
        echo "Missing required fields.";
    }
}
?>
