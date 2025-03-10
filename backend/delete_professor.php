<?php
session_start();
require_once "db.php"; // Ensure this connects properly

if (!isset($_GET['id'])) {
    die("Error: Professor ID missing.");
}

$employee_id = $_GET['id'];

try {
    // Fetch professor data to verify existence
    $query = "SELECT * FROM prof_tbl WHERE employee_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $employee_id]);
    $professor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$professor) {
        die("Error: Professor not found.");
    }

    // Delete the professor
    $query = "DELETE FROM prof_tbl WHERE employee_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $employee_id]); // FIXED: Using $employee_id

    header("Location: professors.php");
    exit();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
