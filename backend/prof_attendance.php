<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    header("Location: login.php");
    exit();
}

$prof_user_id = $_SESSION["user_id"];

$prof_query = "SELECT lastname FROM prof_tbl WHERE prof_user_id = :prof_user_id";
$prof_stmt = $conn->prepare($prof_query);

if ($prof_stmt->execute(['prof_user_id' => $prof_user_id])) {
    $professor = $prof_stmt->fetch(PDO::FETCH_ASSOC);
    $prof_lastname = $professor ? $professor['lastname'] : "Unknown";
} else {
    error_log("Query execution failed: " . implode(" | ", $prof_stmt->errorInfo()));
    $prof_lastname = "Error";
}

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');


$section_query = "SELECT DISTINCT s.section_id, s.section_name
                 FROM schedule_tbl sch
                 JOIN section_tbl s ON sch.section_id = s.section_id
                 WHERE sch.prof_user_id = ?";
$section_stmt = $conn->prepare($section_query);
$section_stmt->execute([$prof_user_id]);
$sections = $section_stmt->fetchAll(PDO::FETCH_ASSOC);

// If no section is selected in GET, use the first section from the list
$selected_section = isset($_GET['section']) ? $_GET['section'] : (!empty($sections) ? $sections[0]['section_id'] : null);

// Get attendance for selected section
$attendance_query = "SELECT 
                        st.student_id,
                        st.lastname,
                        st.firstname,
                        MIN(CASE WHEN a.status = 'check_in' THEN TIME(a.time_in) END) as time_in,
                        MAX(CASE WHEN a.status = 'check_out' THEN TIME(a.time_out) END) as time_out,
                        CASE 
                            WHEN COUNT(CASE WHEN a.status = 'check_in' THEN 1 END) > 0 THEN 'Present'
                            ELSE 'Absent'
                        END as attendance_status,
                        EXISTS (
                            SELECT 1 FROM attendance_tbl a2 
                            WHERE a2.student_id = st.student_user_id 
                            AND DATE(a2.time_out) = ? 
                            AND a2.status = 'check_out'
                        ) as has_checked_out,
                        sub.subject_name,
                        sch.schedule_day
                    FROM student_tbl st
                    LEFT JOIN attendance_tbl a ON st.student_user_id = a.student_id 
                        AND DATE(a.time_in) = ?
                    LEFT JOIN schedule_tbl sch ON st.section_id = sch.section_id
                        AND sch.schedule_day = DAYNAME(?)
                    LEFT JOIN subject_tbl sub ON sch.subject_id = sub.subject_id
                    WHERE st.section_id = ?
                        AND EXISTS (
                            SELECT 1 FROM schedule_tbl sch2 
                            WHERE sch2.section_id = st.section_id 
                            AND sch2.schedule_day = DAYNAME(?)
                        )
                    GROUP BY st.student_user_id, st.student_id, st.lastname, st.firstname, sub.subject_name, sch.schedule_day
                    ORDER BY st.lastname, st.firstname";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Professor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/prof.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
</head>

<body>
    <?php include '../sections/nav2.php'; ?>
    <?php include '../sections/prof_nav.php'; ?>

    <!-- Main Content -->
    <div id="main-container">
        <h2>ATTENDANCE RECORD</h2>

        <div id="dropdowns">
            <form method="GET" class="custom-dropdown-form">
                <div class="custom-input">
                    <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
                </div>
                <div class="custom-select">
                    <select id="section" name="section" onchange="this.form.submit()">
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['section_id'] ?>"
                                <?= ($selected_section == $section['section_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section['section_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selected_section):
            $attendance_stmt = $conn->prepare($attendance_query);
            $attendance_stmt->execute([$date, $date, $date, $selected_section, $date]);
            $attendance_records = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <h2 class="my-3">
                Section: <?= htmlspecialchars($sections[array_search($selected_section, array_column($sections, 'section_id'))]['section_name']) ?>
            </h2>


            <?php if (empty($attendance_records)): ?>
                <div class="alert">
                    No students found in this section or no attendance records for the selected date.
                </div>
            <?php else: ?>

                <table id="table-for-attendance" class="table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Day</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tablebody-attendance">
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['student_id']) ?></td>
                                <td><?= htmlspecialchars($record['lastname'] . ', ' . $record['firstname']) ?></td>
                                <td><?= htmlspecialchars($record['subject_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($record['schedule_day'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= $record['attendance_status'] == 'Present' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= htmlspecialchars($record['attendance_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
    </div>
<?php endif; ?>
</div>
<?php else: ?>
    <div class="alert alert-info">
        No sections found. Please make sure you have assigned sections in your schedule.
    </div>
<?php endif; ?>
</div>
</body>


</html>