<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

$query = "SELECT lastname FROM faculty_tbl WHERE employee_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_STR);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="../css/colorum.css">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
   
</head>
<body>
    <?php include '../sections/nav3.php'; ?>
    <?php include '../sections/fac_nav.php'; ?>
    <div id="main-container">
        <div class="block">
            <h2>STUDENT PROFILES</h2>
            <div class="filter-section">
                <form action="" method="GET">
                    <select name="section" onchange="this.form.submit()">
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
        <table class="custom-table">
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
                                class="student-photo">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
