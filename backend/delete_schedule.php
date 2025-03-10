<?php
session_start();
require_once "db.php"; // Ensure this connects to your database

if (!isset($_GET['id'])) {
    die("Schedule ID not provided.");
}

$schedule_id = $_GET['id'];

// Delete schedule
$deleteQuery = "DELETE FROM schedule_tbl WHERE schedule_id = :schedule_id";
$stmt = $conn->prepare($deleteQuery);
$stmt->bindParam(":schedule_id", $schedule_id, PDO::PARAM_INT);

if ($stmt->execute()) {
    header("Location: schedule.php?success=Schedule deleted successfully");
} else {
    header("Location: schedule.php?error=Failed to delete schedule");
}
exit();
?>
