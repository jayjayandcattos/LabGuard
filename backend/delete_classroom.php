<?php
session_start();
require_once "db.php";

if (!isset($_GET["id"]) || empty($_GET["id"])) {
    die("Error: Room ID missing.");
}

$room_id = $_GET["id"];

try {
    // Verify room exists
    $query = "SELECT * FROM room_tbl WHERE room_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        die("Error: Room not found.");
    }

    // Attempt to delete
    $query = "DELETE FROM room_tbl WHERE room_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(["id" => $room_id]);

    if ($stmt->rowCount() > 0) {
        echo "Room deleted successfully.";
        header("Location: admin_dashboard.php");
        exit();
    } else {
        die("Error: Deletion failed. Possible foreign key issue.");
    }
} catch (PDOException $e) {
    die("SQL Error: " . $e->getMessage());
}
?>
