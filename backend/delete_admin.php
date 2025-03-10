<?php
session_start();
require_once "db.php"; // Ensure this connects properly

if (!isset($_GET['id'])) {
    die("Error: Admin ID missing.");
}

$admin_id = $_GET['id'];

try {
    // Fetch admin data to verify existence
    $query = "SELECT * FROM admin_tbl WHERE admin_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        die("Error: Admin not found.");
    }

    // Delete the admin
    $query = "DELETE FROM admin_tbl WHERE admin_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $admin_id]);

    header("Location: admin.php");
    exit();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?> 