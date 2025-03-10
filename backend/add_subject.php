<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["subject_name"]) && !empty($_POST["prof_user_id"])) {
        $subject_code = $_POST["subject_code"];
        $subject_name = $_POST["subject_name"];
        $prof_user_id = $_POST["prof_user_id"];

        $query = "INSERT INTO subject_tbl (subject_code, subject_name, prof_user_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$subject_code, $subject_name, $prof_user_id]);

        header("Location: student_subs.php");
        exit();
    } else {
        echo "Missing required fields.";
    }
}
?>
