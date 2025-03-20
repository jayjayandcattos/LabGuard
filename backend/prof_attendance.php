<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    header("Location: login.php");
    exit();
}

// Get current date if not specified
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get sections taught by the professor
$section_query = "SELECT DISTINCT s.section_id, s.section_name
                 FROM schedule_tbl sch
                 JOIN section_tbl s ON sch.section_id = s.section_id
                 WHERE sch.prof_user_id = ?";
$section_stmt = $conn->prepare($section_query);
$section_stmt->execute([$_SESSION["user_id"]]);
$sections = $section_stmt->fetchAll(PDO::FETCH_ASSOC);

// If no section is selected in GET, use the first section from the list
$selected_section = isset($_GET['section']) ? $_GET['section'] : 
                   (!empty($sections) ? $sections[0]['section_id'] : null);

// Get attendance for selected section
$attendance_query = "SELECT 
                        st.student_id,
                        st.lastname,
                        st.firstname,
                        MIN(CASE WHEN a.status = 'check_in' THEN TIME(a.timestamp) END) as time_in,
                        MAX(CASE WHEN a.status = 'check_out' THEN TIME(a.timestamp) END) as time_out,
                        CASE 
                            WHEN COUNT(CASE WHEN a.status = 'check_in' THEN 1 END) > 0 THEN 'Present'
                            ELSE 'Absent'
                        END as attendance_status,
                        EXISTS (
                            SELECT 1 FROM attendance_tbl a2 
                            WHERE a2.student_id = st.student_user_id 
                            AND DATE(a2.timestamp) = ? 
                            AND a2.status = 'check_out'
                        ) as has_checked_out,
                        sub.subject_name,
                        sch.schedule_day
                    FROM student_tbl st
                    LEFT JOIN attendance_tbl a ON st.student_user_id = a.student_id 
                        AND DATE(a.timestamp) = ?
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
</head>
<body>

<?php include 'prof_nav.php'?>


        <!-- Main Content -->
        <div id="main" class="container-fluid p-6" style="margin-top: 50px; width: 150vh ;  height: 70vh; overflow-y: hidden;">
            <h2 style="margin-left: 20px;  margin-top: 10px;">Attendance Record</h2>
            
            <!-- Date and Section Selection -->
            <div id="dropdowns" class="card p-3 mb-4" style=" top:0px; background-color: transparent; border: none;">
    <form method="GET" class="d-flex align-items-center gap-3" style=" align-self:first baseline; ">
        <div>
           
            <input type="date" class="form-control" id="date" name="date" 
                   value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
        </div>
        <div>
            
            <select class="form-control" id="section" name="section" onchange="this.form.submit()">
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


            <!-- Attendance Table -->
            <?php if ($selected_section): 
                $attendance_stmt = $conn->prepare($attendance_query);
                $attendance_stmt->execute([$date, $date, $date, $selected_section, $date]);
                $attendance_records = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="card p-3" >
                <h4>Section: <?= htmlspecialchars($sections[array_search($selected_section, array_column($sections, 'section_id'))]['section_name']) ?></h4>
                <?php if (empty($attendance_records)): ?>
                    <div class="alert alert-info">
                        No students found in this section or no attendance records for the selected date.
                    </div>
                <?php else: ?>
                    <table id="table-for-attendance" class="table table-bordered" style="max-height: 50vh; ">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Day</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tablebody-attendance" style="overflow-y: scroll;">
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
                <?php endif; ?>
            </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No sections found. Please make sure you have assigned sections in your schedule.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 