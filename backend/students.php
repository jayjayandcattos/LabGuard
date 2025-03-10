<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

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

$query .= " ORDER BY s.lastname, s.firstname";

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
    <title>Student Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
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

        <!-- Main Content -->
        <div class="container mt-4">
            <h2>Student Management</h2>
            
            <!-- Display Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <!-- Add Student Button -->
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        Add Student
                    </button>
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

            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>RFID Tag</th>
                        <th>Section</th>
                        <th>Photo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . ' ' . $student['mi']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= htmlspecialchars($student['rfid_tag']) ?></td>
                            <td><?= htmlspecialchars($student['section_name']) ?></td>
                            <td><img src="uploads/<?= $student['photo'] ?>" width="50" height="50" class="rounded-circle"></td>
                            <td>
                                <a href="edit_student.php?id=<?= $student['student_user_id'] ?>" 
                                   class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_student.php?id=<?= $student['student_user_id'] ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="add_student.php" method="POST" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStudentLabel">Add New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label>Student ID:</label>
                        <input type="text" name="student_id" class="form-control" required>

                        <label>Last Name:</label>
                        <input type="text" name="lastname" class="form-control" required>

                        <label>First Name:</label>
                        <input type="text" name="firstname" class="form-control" required>

                        <label>Middle Initial:</label>
                        <input type="text" name="mi" class="form-control">

                        <label>Email:</label>
                        <input type="email" name="email" class="form-control" required>

                        <label>RFID Tag:</label>
                        <input type="text" name="rfid_tag" class="form-control" required>

                        <label>Section:</label>
                        <select name="section_id" class="form-control" required>
                            <?php
                            $secQuery = "SELECT * FROM section_tbl";
                            $secStmt = $conn->prepare($secQuery);
                            $secStmt->execute();
                            while ($sec = $secStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$sec['section_id']}'>{$sec['section_name']}</option>";
                            }
                            ?>
                        </select>

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
</body>
</html>
