<?php
session_start();
require_once "db.php"; 

if (!isset($_GET['id'])) {
    die("Error: Professor ID missing.");
}

$employee_id = $_GET['id'];

try {
    
    $query = "SELECT * FROM prof_tbl WHERE employee_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $employee_id]);
    $professor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$professor) {
        die("Error: Professor not found.");
    }

    
    $query = "DELETE FROM prof_tbl WHERE employee_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $employee_id]); 

    header("Location: professors.php");
    exit();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
