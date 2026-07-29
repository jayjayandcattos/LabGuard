<?php
    session_start();
    require_once "db.php";

    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $query = "DELETE FROM students WHERE student_user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        header("Location: students.php");
    }
    ?>