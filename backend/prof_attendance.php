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
                        MIN(CASE WHEN a.status = 'check_in' OR a.status = 'check_out' THEN TIME(a.time_in) END) as time_in,
                        MAX(CASE WHEN a.status = 'check_out' THEN TIME(a.time_out) END) as time_out,
                        CASE 
                            -- Prioritize existing a_status if available
                            WHEN MAX(a.a_status) IN ('Present') THEN 'Present'
                            WHEN COUNT(CASE WHEN (a.status = 'check_in' OR a.status = 'check_out') THEN 1 END) > 0 THEN 'Present'
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
    <style>
        .email-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .email-modal.show {
            opacity: 1;
        }

        .email-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.7);
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            font-family: 'Orbitron', sans-serif;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            opacity: 0;
        }

        .email-modal.show .email-modal-content {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }

        .email-modal-content h2 {
            margin-bottom: 25px;
            color: #333;
            text-align: center;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .email-modal-content input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 25px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .email-modal-content input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .email-modal-content button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .submit-email {
            background-color: #4a90e2;
            color: white;
            flex: 1;
        }

        .submit-email:hover {
            background-color: #3a7bc8;
            transform: translateY(-2px);
        }

        .close-modal {
            background-color: #e2e2e2;
            color: #555;
            flex: 1;
        }

        .close-modal:hover {
            background-color: #d4d4d4;
            transform: translateY(-2px);
        }

        /* Request button styling */
        .email-request-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(74, 144, 226, 0.3);
        }

        .email-request-btn:hover {
            background-color: #3a7bc8;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(74, 144, 226, 0.4);
        }
    </style>
</head>

<body>
    <?php include '../sections/nav2.php'; ?>
    <?php include '../sections/prof_nav.php'; ?>

    <!-- Email Modal -->
    <div id="emailModal" class="email-modal">
        <div class="email-modal-content">
            <h2>Request Attendance Summary</h2>
            <form id="emailForm" onsubmit="sendAttendanceSummary(event)">
                <input type="email" id="emailInput" placeholder="Enter your email address" required>
                <input type="hidden" id="currentSection" name="section">
                <input type="hidden" id="currentDate" name="date">
                <div class="modal-buttons">
                    <button type="button" class="close-modal" onclick="closeEmailModal()">Cancel</button>
                    <button type="submit" class="submit-email">Send Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-container">
        <button class="email-request-btn" onclick="openEmailModal()">Request for Attendance Summary</button>
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

<script>
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function openEmailModal() {
        // Get the date from the date input or use current date if not set
        const dateInput = document.getElementById('date');
        const currentDate = dateInput && dateInput.value ? dateInput.value : formatDate(new Date());
        
        // Set current values to hidden fields
        document.getElementById('currentSection').value = document.getElementById('section').value;
        document.getElementById('currentDate').value = currentDate;
        
        // Show modal with animation
        const emailModal = document.getElementById('emailModal');
        emailModal.style.display = 'block';
        
        // Trigger reflow to enable transition from display none
        void emailModal.offsetWidth;
        
        // Add show class for animation
        emailModal.classList.add('show');
    }

    function closeEmailModal() {
        const emailModal = document.getElementById('emailModal');
        
        // Remove show class to trigger fade out
        emailModal.classList.remove('show');
        
        // Wait for animation to complete before hiding
        setTimeout(() => {
            emailModal.style.display = 'none';
        }, 300); // Match this with CSS transition duration
    }

    function sendAttendanceSummary(event) {
        event.preventDefault();
        
        // Get values from form
        const email = document.getElementById('emailInput').value;
        const section = document.getElementById('currentSection').value;
        const date = document.getElementById('currentDate').value;

        fetch('../backend/send_attendance_summary.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                section: section,
                date: date
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Attendance summary has been sent to your email!');
            } else {
                alert('Error sending attendance summary: ' + data.message);
            }
            closeEmailModal();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending attendance summary: ' + error.message);
            closeEmailModal();
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const emailModal = document.getElementById('emailModal');
        if (event.target === emailModal) {
            closeEmailModal();
        }
    });
</script>