<?php
session_start();
require_once "db.php"; // Ensure this connects to your database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

// Check if room ID is provided
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$room_id = $_GET['id'];

// Fetch room details
$query = "SELECT * FROM room_tbl WHERE room_id = :id";
$stmt = $conn->prepare($query);
$stmt->execute([":id" => $room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header("Location: admin_dashboard.php");
    exit();
}

// Handle update request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_room"])) {
    $room_number = $_POST["room_number"];
    $room_name = $_POST["room_name"];
    $status = $_POST["status"];

    $updateQuery = "UPDATE room_tbl SET room_number = :room_number, room_name = :room_name, status = :status WHERE room_id = :id";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        "room_number" => $room_number,
        "room_name" => $room_name,
        "status" => $status,
        "id" => $id
    ]);

    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Classroom</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Classroom</h2>
        <form method="POST">
            <div class="mb-3">
                <label>Room Number</label>
                <input type="text" name="room_number" class="form-control" value="<?= htmlspecialchars($room['room_number']); ?>" required>
            </div>
            <div class="mb-3">
                <label>Room Name</label>
                <input type="text" name="room_name" class="form-control" value="<?= htmlspecialchars($room['room_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Vacant" <?= $room['status'] == 'Vacant' ? 'selected' : ''; ?>>Vacant</option>
                    <option value="Occupied" <?= $room['status'] == 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                </select>
            </div>
            <button type="submit" name="update_room" class="btn btn-primary">Update Room</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
