<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Fetch all faculty members
$query = "SELECT * FROM faculty_tbl ORDER BY lastname, firstname";
$stmt = $conn->prepare($query);
$stmt->execute();
$faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['name'])) {
    $name_parts = explode(' ', $_SESSION['name']);
    $admin_firstname = $name_parts[0];
    $admin_lastname = isset($name_parts[1]) ? $name_parts[1] : 'Unknown';
} else {
    $admin_lastname = 'Unknown'; 
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            <h2>Faculty Management</h2>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addFacultyModal">Add Faculty</button>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>RFID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faculty as $f): ?>
                        <tr>
                            <td><img src="uploads/<?= htmlspecialchars($f['photo']) ?>" width="50" height="50"></td>
                            <td><?= htmlspecialchars($f['employee_id']) ?></td>
                            <td><?= htmlspecialchars($f['lastname'] . ', ' . $f['firstname'] . ' ' . $f['mi']) ?></td>
                            <td><?= htmlspecialchars($f['email']) ?></td>
                            <td><?= htmlspecialchars($f['rfid_tag']) ?></td>
                            <td>
                                <a href="edit_faculty.php?id=<?= $f['faculty_user_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_faculty.php?id=<?= $f['faculty_user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="add_faculty.php" method="POST" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addFacultyLabel" style="color: black;">Add Faculty Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label style="color: black;">Employee ID:</label>
                        <input type="text" name="employee_id" class="form-control" required>
                        <label style="color: black;">Last Name:</label>
                        <input type="text" name="lastname" class="form-control" required>
                        <label style="color: black;">First Name:</label>
                        <input type="text" name="firstname" class="form-control" required>
                        <label style="color: black;">Middle Initial:</label>
                        <input type="text" name="mi" class="form-control">
                        <label style="color: black;">Email:</label>
                        <input type="email" name="email" class="form-control" required>
                        <label style="color: black;">Password:</label>
                        <input type="password" name="password" class="form-control" required>
                        <label style="color: black;">RFID Tag:</label>
                        <input type="text" name="rfid_tag" class="form-control" required>
                        <label style="color: black;">Photo:</label>
                        <input type="file" name="photo" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
