<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Fetch all faculty members
$query = "SELECT * FROM faculty_tbl ORDER BY lastname, firstname";
$stmt = $conn->prepare($query);
$stmt->execute();
$faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="d-flex">
        <nav class="bg-dark text-white p-3" style="width: 250px; height: 100vh;">
            <h4>Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link text-white">Classroom</a></li>
                <li class="nav-item"><a href="schedule.php" class="nav-link text-white">Schedule</a></li>
                <li class="nav-item"><a href="professors.php" class="nav-link text-white">Professors</a></li>
                <li class="nav-item"><a href="faculty.php" class="nav-link text-white">Faculty</a></li>
                <li class="nav-item"><a href="students.php" class="nav-link text-white">Students</a></li>
                <li class="nav-item"><a href="student_subs.php" class="nav-link text-white">Student Subjects</a></li>
                <li class="nav-item"><a href="student_secs.php" class="nav-link text-white">Student Sections</a></li>
                <li class="nav-item"><a href="admin.php" class="nav-link text-white">Admin</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <div class="container mt-4">
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
                        <h5 class="modal-title" id="addFacultyLabel">Add Faculty Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label>Employee ID:</label>
                        <input type="text" name="employee_id" class="form-control" required>
                        <label>Last Name:</label>
                        <input type="text" name="lastname" class="form-control" required>
                        <label>First Name:</label>
                        <input type="text" name="firstname" class="form-control" required>
                        <label>Middle Initial:</label>
                        <input type="text" name="mi" class="form-control">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-control" required>
                        <label>Password:</label>
                        <input type="password" name="password" class="form-control" required>
                        <label>RFID Tag:</label>
                        <input type="text" name="rfid_tag" class="form-control" required>
                        <label>Photo:</label>
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
