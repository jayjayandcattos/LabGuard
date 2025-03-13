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

$query .= " ORDER BY sec.section_name, s.lastname";

// Execute query with filters
$params = ['prof_user_id' => $prof_user_id];
if ($section_filter !== 'all') {
    $params['section_id'] = $section_filter;
}
if ($subject_filter !== 'all') {
    $params['subject_id'] = $subject_filter;
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
    <link rel="stylesheet" href="../css/prof.css">
</head>
<body>
    <div class="professor-header">
        <h1>PROFESSOR PROFILE</h1>
        <p>WELCOME PROFESSOR <?= htmlspecialchars($prof_lastname); ?>!</p>
    </div>

    <div class="d-flex">
        <!-- Sidebar -->
        <nav class=" text-white p-3 " >
         
            <ul class=" nav flex-column">
                <li class="nav-item"><a href="prof_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="prof_students.php" class="nav-link text-white active">Students Profile</a></li>
                <li class="nav-item"><a href="prof_schedule.php" class="nav-link text-white">My Schedule</a></li>
                <li class="nav-item"><a href="prof_attendance.php" class="nav-link text-white">Attendance</a></li>
                <li class="nav-item"><a href="prof_profile.php" class="nav-link text-white">My Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div id="main" class="container-fluid p-5">
            <h2>My Students</h2>
            
            <!-- Section and Subject Filters -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <!-- Empty div to maintain spacing -->
                </div>
                <div class="dropdowns">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex ">
                        <select name="section" class="  me-2 " onchange="this.form.submit()">
                            <option value="all" <?= $section_filter === 'all' ? 'selected' : '' ?>>All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= $section['section_id'] ?>" 
                                        <?= $section_filter == $section['section_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($section['section_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="subject" class="  me-2"  onchange="this.form.submit()">
                            <option value="all" <?= $subject_filter === 'all' ? 'selected' : '' ?>>All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>" 
                                        <?= $subject_filter == $subject['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                </div>
            </div>

            <div class="card ">
                <div class="table-container">
                <table class="table ">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Section</th>
                            
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