<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Fetch all subjects with assigned professors
$query = "
    SELECT subj.subject_id, subj.subject_code, subj.subject_name, p.firstname, p.lastname
    FROM subject_tbl subj
    LEFT JOIN prof_tbl p ON subj.prof_user_id = p.prof_user_id
    ORDER BY subj.subject_name";

$stmt = $conn->prepare($query);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management</title>
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
            <h2>Subject Management</h2>
            
            <!-- Add Subject Button -->
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add Subject</button>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Assigned Professor</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td> <?= htmlspecialchars($subject ['subject_code']) ?> </td>
                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td><?= $subject['firstname'] ? htmlspecialchars($subject['firstname'] . ' ' . $subject['lastname']) : 'Unassigned' ?></td>
                            <td>
                                <a href="edit_subject.php?id=<?= $subject['subject_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_subject.php?id=<?= $subject['subject_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="add_subject.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSubjectLabel">Add New Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label>Course Code:</label>
                        <input type="text" name="subject_code" class="form-control" required>

                        <label>Subject Name:</label>
                        <input type="text" name="subject_name" class="form-control" required>

                        <label>Assign Professor:</label>
                        <select name="prof_user_id" class="form-control">
                            <option value="">-- Select a Professor --</option>
                            <?php
                            $profQuery = "SELECT prof_user_id, firstname, lastname FROM prof_tbl";
                            $profStmt = $conn->prepare($profQuery);
                            $profStmt->execute();
                            while ($prof = $profStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$prof['prof_user_id']}'>{$prof['firstname']} {$prof['lastname']}</option>";
                            }
                            ?>
                        </select>
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
