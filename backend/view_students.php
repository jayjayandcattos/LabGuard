<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Get selected section filter
$section_filter = isset($_GET['section']) ? $_GET['section'] : 'all';

// Base query
$query = "
    SELECT s.student_user_id, s.student_id, s.lastname, s.firstname, s.mi, s.email, s.rfid_tag, 
           sec.section_name, sec.section_id, s.photo, s.created_at
    FROM student_tbl s
    JOIN section_tbl sec ON s.section_id = sec.section_id";

// Add section filter if not showing all
if ($section_filter !== 'all') {
    $query .= " WHERE s.section_id = :section_id";
}

$query .= " ORDER BY sec.section_name, s.lastname, s.firstname";

$stmt = $conn->prepare($query);

// Execute with section filter if specified
if ($section_filter !== 'all') {
    $stmt->execute(['section_id' => $section_filter]);
} else {
    $stmt->execute();
}

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sections for the filter dropdown
$sections_query = "SELECT * FROM section_tbl ORDER BY section_name";
$sections_stmt = $conn->prepare($sections_query);
$sections_stmt->execute();
$sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
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
                <li class="nav-item"><a href="view_students.php" class="nav-link text-white active">Students Profile</a></li>
                <li class="nav-item"><a href="view_professors.php" class="nav-link text-white">Professors Profile</a></li>
                <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white">Schedule Management</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2>Students Profile</h2>
            
            <!-- Sort Options -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <!-- Empty div to maintain spacing -->
                </div>
                <div class="col-md-6">
                    <!-- Section Filter -->
                    <form action="" method="GET" class="d-flex justify-content-end">
                        <select name="section" class="form-select w-50 me-2" onchange="this.form.submit()">
                            <option value="all" <?= $section_filter === 'all' ? 'selected' : '' ?>>All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= $section['section_id'] ?>" 
                                        <?= $section_filter == $section['section_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($section['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div class="card p-3">
                <table class="table table-hover">
                    
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Section</th>
                            <th>Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id']); ?></td>
                                <td><?= htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . ' ' . $student['mi']); ?></td>
                                <td><?= htmlspecialchars($student['email']); ?></td>
                                <td><?= htmlspecialchars($student['section_name']); ?></td>
                                <td>
                                    <img src="uploads/<?= htmlspecialchars($student['photo']); ?>" 
                                         width="50" height="50" 
                                         alt="Student Photo"
                                         class="rounded-circle">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 