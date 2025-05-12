<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Get selected section filter
$section_filter = isset($_GET['section']) ? $_GET['section'] : 'all';
$year_filter = isset($_GET['year']) ? $_GET['year'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Base query
$query = "
    SELECT s.student_user_id, s.student_id, s.lastname, s.firstname, s.mi, s.email, s.rfid_tag, 
           sec.section_name, sec.section_id, s.photo, s.created_at, s.year_level, s.student_status
    FROM student_tbl s
    JOIN section_tbl sec ON s.section_id = sec.section_id
    WHERE 1=1";

// Add filters
if ($section_filter !== 'all') {
    $query .= " AND s.section_id = :section_id";
}
if ($year_filter !== 'all') {
    $query .= " AND s.year_level = :year_level";
}
if ($status_filter !== 'all') {
    $query .= " AND s.student_status = :student_status";
}

$query .= " ORDER BY s.lastname, s.firstname";

$stmt = $conn->prepare($query);

// Bind parameters for the filters
$params = [];
if ($section_filter !== 'all') {
    $params['section_id'] = $section_filter;
}
if ($year_filter !== 'all') {
    $params['year_level'] = $year_filter;
}
if ($status_filter !== 'all') {
    $params['student_status'] = $status_filter;
}

$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sections for the filter dropdown
$sections_query = "SELECT * FROM section_tbl ORDER BY section_name";
$sections_stmt = $conn->prepare($sections_query);
$sections_stmt->execute();
$sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);

// Year levels array
$year_levels = [
    '1' => 'First Year',
    '2' => 'Second Year',
    '3' => 'Third Year',
    '4' => 'Fourth Year'
];

// Student status options
$student_statuses = [
    'regular' => 'Regular',
    'irregular' => 'Irregular'
];

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
    <title>Student Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/classroomManagement.js" defer></script>
    <script src="../js/studentValidation.js" defer></script>
</head>

<body>
    <?php include '../sections/nav4.php' ?>
    <?php include '../sections/admin_nav.php' ?>

    <div id="main-container">
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
                <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    Add Student
                </button>
            </div>
            <div class="col-md-6">
                <!-- Filters -->
                <form action="" method="GET" class="d-flex justify-content-end">
                    <select name="section" class="form-select2 w-25 me-2" onchange="this.form.submit()">
                        <option value="all" <?= $section_filter === 'all' ? 'selected' : '' ?>>All Sections</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['section_id'] ?>" <?= $section_filter == $section['section_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section['section_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="year" class="form-select2 w-25 me-2" onchange="this.form.submit()">
                        <option value="all" <?= $year_filter === 'all' ? 'selected' : '' ?>>All Years</option>
                        <?php foreach ($year_levels as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $year_filter == $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="form-select2 w-25 me-2" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                        <?php foreach ($student_statuses as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $status_filter == $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <table class="table table-hover table-bordered" style="width: 99%;">
            <thead style="width: 100.1%;">
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>RFID Tag</th>
                    <th>Section</th>
                    <th>Year Level</th>
                    <th>Status</th>
                    <th>Photo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-responsive" style="width: 100.8%; height: 500px; overflow-y: auto;">
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <?= htmlspecialchars($student['student_id']) ?>
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <?= htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . ' ' . $student['mi']) ?>
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <?= htmlspecialchars($student['email']) ?>
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <?= htmlspecialchars($student['rfid_tag']) ?>
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <?= htmlspecialchars($student['section_name']) ?>
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <?= htmlspecialchars($student['year_level'] ? $year_levels[$student['year_level']] ?? $student['year_level'] : 'Not Set') ?>
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <?= htmlspecialchars($student['student_status'] ? $student_statuses[$student['student_status']] ?? $student['student_status'] : 'Not Set') ?>
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <img src="uploads/<?= $student['photo'] ?>" width="50" height="50" class="rounded-circle">
                        </td>
                        <td style="overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
                            <a href="edit_student.php?id=<?= $student['student_user_id'] ?>"
                                class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_student.php?id=<?= $student['student_user_id'] ?>" class="btn btn-danger btn-sm"
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
                        <h5 class="modal-title text-black" id="addStudentLabel">Add New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-black">
                        <label class="text-black">Student ID:</label>
                        <input type="text" name="student_id" class="form-control" required>

                        <label class="text-black">Last Name:</label>
                        <input type="text" name="lastname" class="form-control" required>

                        <label class="text-black">First Name:</label>
                        <input type="text" name="firstname" class="form-control" required>

                        <label class="text-black">Middle Initial:</label>
                        <input type="text" name="mi" class="form-control">

                        <label class="text-black">Email:</label>
                        <input type="email" name="email" class="form-control" required>

                        <label class="text-black">RFID Tag:</label>
                        <input type="text" name="rfid_tag" class="form-control" required>

                        <label class="text-black">Section:</label>
                        <select name="section_id" class="form-control2">
                            <?php
                            $secQuery = "SELECT * FROM section_tbl";
                            $secStmt = $conn->prepare($secQuery);
                            $secStmt->execute();
                            while ($sec = $secStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$sec['section_id']}'>{$sec['section_name']}</option>";
                            }
                            ?>
                        </select>

                        <label class="text-black">Year Level:</label>
                        <select name="year_level" class="form-control2" >
                            <?php foreach ($year_levels as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="text-black">Student Status:</label>
                        <select name="student_status" class="form-control2">
                            <?php foreach ($student_statuses as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="text-black">Photo:</label>
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
    </div>
    </div>
</body>

</html>