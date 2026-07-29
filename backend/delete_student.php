<?php
session_start();
require_once "db.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // First check if student exists
        $check_query = "SELECT student_id FROM student_tbl WHERE student_user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$id]);
        
        if ($check_stmt->rowCount() == 0) {
            $_SESSION['error'] = "Student not found";
            header("Location: students.php");
            exit();
        }

        // Delete the student
        $query = "DELETE FROM student_tbl WHERE student_user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Student deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete student";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid student ID";
}

header("Location: students.php");
exit();
?> 