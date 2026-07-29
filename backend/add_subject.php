<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["subject_name"]) && !empty($_POST["prof_user_id"])) {
        $subject_code = $_POST["subject_code"];
        $subject_name = $_POST["subject_name"];
        $prof_user_id = $_POST["prof_user_id"];

        // Check for duplicate subject code
        $checkQuery = "SELECT COUNT(*) FROM subject_tbl WHERE subject_code = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$subject_code]);

        if ($checkStmt->fetchColumn() > 0) {
            echo "<script>alert('Subject code already exists!'); window.location.href = 'student_subs.php';</script>";
            exit();
        }

        // Insert if no duplicate
        $query = "INSERT INTO subject_tbl (subject_code, subject_name, prof_user_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$subject_code, $subject_name, $prof_user_id]);

        header("Location: student_subs.php");
        exit();
    } else {
        echo "<script>alert('Missing required fields.'); window.location.href = 'student_subs.php';</script>";
        exit();
    }
}
?>
