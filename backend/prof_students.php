<?php
session_start();
require_once "db.php";

// Debug session information
error_log("Session Data: " . print_r($_SESSION, true));

// Update the role check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    error_log("Auth Failed - User ID: " . ($_SESSION["user_id"] ?? 'not set') . ", Role: " . ($_SESSION["role"] ?? 'not set'));
    header("Location: login.php");
    exit();
}

// Get filters
$section_filter = isset($_GET['section']) ? $_GET['section'] : 'all';
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : 'all';
$year_filter = isset($_GET['year']) ? $_GET['year'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get professor's ID
$prof_user_id = $_SESSION["user_id"];

// Debug information
error_log("Debug Information:");
error_log("Professor ID: " . $prof_user_id);

// Base query
$query = "
    SELECT DISTINCT 
        s.student_id, 
        s.lastname, 
        s.firstname, 
        s.mi, 
        s.email, 
        s.photo,
        s.year_level,
        s.student_status,
        sec.section_name,
        sub.subject_name
    FROM schedule_tbl sch
    JOIN section_tbl sec ON sch.section_id = sec.section_id
    JOIN student_tbl s ON sec.section_id = s.section_id
    JOIN subject_tbl sub ON sch.subject_id = sub.subject_id
    WHERE sch.prof_user_id = :prof_user_id";

// Add filters
if ($section_filter !== 'all') {
    $query .= " AND sec.section_id = :section_id";
}
if ($subject_filter !== 'all') {
    $query .= " AND sub.subject_id = :subject_id";
}
if ($year_filter !== 'all') {
    $query .= " AND s.year_level = :year_level";
}
if ($status_filter !== 'all') {
    $query .= " AND s.student_status = :student_status";
}

$query .= " ORDER BY sec.section_name, s.lastname";

// Execute query with filters
$params = ['prof_user_id' => $prof_user_id];
if ($section_filter !== 'all') {
    $params['section_id'] = $section_filter;
}
if ($subject_filter !== 'all') {
    $params['subject_id'] = $subject_filter;
}
if ($year_filter !== 'all') {
    $params['year_level'] = $year_filter;
}
if ($status_filter !== 'all') {
    $params['student_status'] = $status_filter;
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For debugging
error_log("Number of students found: " . count($students));

// Fetch sections for the filter dropdown
$sections_query = "
    SELECT DISTINCT sec.section_id, sec.section_name
    FROM section_tbl sec
    JOIN schedule_tbl sch ON sec.section_id = sch.section_id
    WHERE sch.prof_user_id = :prof_user_id
    ORDER BY sec.section_name";
$sections_stmt = $conn->prepare($sections_query);
$sections_stmt->execute(['prof_user_id' => $prof_user_id]);
$sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch subjects for the filter dropdown
$subjects_query = "
    SELECT DISTINCT sub.subject_id, sub.subject_name
    FROM subject_tbl sub
    JOIN schedule_tbl sch ON sub.subject_id = sch.subject_id
    WHERE sch.prof_user_id = :prof_user_id
    ORDER BY sub.subject_name";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->execute(['prof_user_id' => $prof_user_id]);
$subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Fetch Professor's Last Name
$prof_query = "SELECT lastname FROM prof_tbl WHERE prof_user_id = :prof_user_id";
$prof_stmt = $conn->prepare($prof_query);

if ($prof_stmt->execute(['prof_user_id' => $prof_user_id])) {
    $professor = $prof_stmt->fetch(PDO::FETCH_ASSOC);

    if ($professor) {
        $prof_lastname = $professor['lastname'];
    } else {
        error_log("No professor found with prof_user_id: " . $prof_user_id);
        $prof_lastname = "Unknown";
    }
} else {
    error_log("Query execution failed: " . implode(" | ", $prof_stmt->errorInfo()));
    $prof_lastname = "Error";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/prof.css">
</head>

<body>
    <?php include '../sections/nav2.php' ?>
    <?php include '../sections/prof_nav.php'; ?>

    <!-- Main Content -->
    <div id="main-container">
        <h2>MY STUDENTS</h2>

        <!-- Section and Subject Filters -->
        <div class="row mb-3">
            <div class="col-md-6">
                <!-- Empty div to maintain spacing -->
            </div>
            <div class="dropdowns">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <select name="section" class="me-2" onchange="this.form.submit()">
                            <option value="all" <?= $section_filter === 'all' ? 'selected' : '' ?>>All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= $section['section_id'] ?>"
                                    <?= $section_filter == $section['section_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($section['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="subject" class="me-2" onchange="this.form.submit()">
                            <option value="all" <?= $subject_filter === 'all' ? 'selected' : '' ?>>All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>"
                                    <?= $subject_filter == $subject['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" class="me-2" onchange="this.form.submit()">
                            <option value="all" <?= $year_filter === 'all' ? 'selected' : '' ?>>All Years</option>
                            <?php foreach ($year_levels as $value => $label): ?>
                                <option value="<?= $value ?>"
                                    <?= $year_filter == $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="me-2" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                            <?php foreach ($student_statuses as $value => $label): ?>
                                <option value="<?= $value ?>"
                                    <?= $status_filter == $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table class="table ">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Section</th>
                        <th>Year Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($student['photo']); ?>"
                                    width="50" height="50"
                                    alt="Student Photo"
                                    class="rounded-circle">
                            </td>
                            <td><?= htmlspecialchars($student['student_id']); ?></td>
                            <td><?= htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . ' ' . $student['mi']); ?></td>
                            <td><?= htmlspecialchars($student['email']); ?></td>
                            <td><?= htmlspecialchars($student['section_name']); ?></td>
                            <td><?= htmlspecialchars($student['year_level'] ? $year_levels[$student['year_level']] ?? $student['year_level'] : 'Not Set'); ?></td>
                            <td><?= htmlspecialchars($student['student_status'] ? $student_statuses[$student['student_status']] ?? $student['student_status'] : 'Not Set'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
    </div>
</body>

</html>