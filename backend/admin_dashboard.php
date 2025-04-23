<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

if (isset($_SESSION['name'])) {
    $name_parts = explode(' ', $_SESSION['name']);
    $admin_firstname = $name_parts[0];
    $admin_lastname = isset($name_parts[1]) ? $name_parts[1] : 'Unknown';
} else {
    $admin_lastname = 'Unknown'; 
}

// Handle room addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_room"])) {
    $room_number = $_POST["room_number"];
    $room_name = $_POST["room_name"];
    $status = $_POST["status"];

    $query = "INSERT INTO room_tbl (room_number, room_name, status) VALUES (:room_number, :room_name, :status)";
    $stmt = $conn->prepare($query);
    $stmt->execute(["room_number" => $room_number, "room_name" => $room_name, "status" => $status]);

    header("Location: admin_dashboard.php");
    exit();
}


// Fetch room data
$query = "SELECT * FROM room_tbl";
$stmt = $conn->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/classroomManagement.js" defer></script>
</head>
<body>
<?php include '../sections/nav4.php' ?>
<?php include '../sections/admin_nav.php' ?>
        

       <div id="main-container">
            <h2>Classroom Management</h2>

        
<button class="toggle-btn" onclick="toggleForm()">+</button>

<div id="roomForm" class="hidden-form">
    <div class="card mb-4">
        <h4>Add New Room</h4>
        <form method="POST" action="">
            <div class="mb-2">
                <label>Room Number</label>
                <input type="text" name="room_number" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Room Name</label>
                <input type="text" name="room_name" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Status</label>
                <select name="status" class="form-control1">
                    <option value="Vacant">Vacant</option>
                    <option value="Occupied">Occupied</option>
                </select>
            </div>
            <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
        </form>
    </div>
</div>


            <!-- Room List Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars($room['room_number']); ?></td>
                            <td><?= htmlspecialchars($room['room_name']); ?></td>
                            <td class="<?= $room['status'] == 'Vacant' ? 'text-success' : 'text-danger'; ?>">
                                <?= htmlspecialchars($room['status']); ?>
                            </td>
                            <td>
                                <a href="edit_classroom.php?id=<?= htmlspecialchars($room['room_id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_classroom.php?id=<?= $room['room_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
