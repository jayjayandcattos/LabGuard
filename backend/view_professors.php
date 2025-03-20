<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Fetch professors data
$query = "SELECT *, CONCAT(lastname, ', ', firstname, ' ', mi) AS fullname FROM prof_tbl";
$stmt = $conn->prepare($query);
$stmt->execute();
$professors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Professors</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/colorum.css">
    <style>
        .nav-link.active {
            background-color: #152569 !important; /* Bootstrap success color */
        }
    </style>

</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="btn-panel" style="width: 250px;">
            <h4>Faculty Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="faculty_overview.php" class="nav-link text-white">Overview</a></li>
                <li class="nav-item"><a href="faculty_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="view_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="view_professors.php" class="nav-link text-white active">Professors Profile</a></li>
                <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white">Schedule Management</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2>Professors Profile</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Photo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($professors as $professor): ?>
                        <tr>
                            <td><?= htmlspecialchars($professor['employee_id']); ?></td>
                            <td><?= htmlspecialchars($professor['fullname']); ?></td>
                            <td><?= htmlspecialchars($professor['email']); ?></td>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($professor['photo']); ?>" 
                                     width="50" height="50" 
                                     alt="Professor Photo"
                                     class="rounded-circle">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 