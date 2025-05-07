<?php
session_start();
require_once "db.php";
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$section = $data['section'] ?? '';
$date = $data['date'] ?? '';

// Validate date format
if (!$email || !$section || !$date || !strtotime($date)) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit();
}

// Format date for display
$displayDate = date('F j, Y', strtotime($date));
$sqlDate = date('Y-m-d', strtotime($date));

try {
    // Get attendance records
    $query = "SELECT 
                st.student_id,
                st.lastname,
                st.firstname,
                sub.subject_name,
                sch.schedule_day,
                CASE 
                    WHEN MAX(a.a_status) IN ('Present') THEN 'Present'
                    WHEN COUNT(CASE WHEN (a.status = 'check_in' OR a.status = 'check_out') THEN 1 END) > 0 THEN 'Present'
                    ELSE 'Absent'
                END as attendance_status,
                MIN(CASE WHEN a.status = 'check_in' OR a.status = 'check_out' THEN TIME(a.time_in) END) as time_in,
                MAX(CASE WHEN a.status = 'check_out' THEN TIME(a.time_out) END) as time_out
            FROM student_tbl st
            LEFT JOIN attendance_tbl a ON st.student_user_id = a.student_id 
                AND DATE(a.time_in) = :date
            LEFT JOIN schedule_tbl sch ON st.section_id = sch.section_id
                AND sch.schedule_day = DAYNAME(:date)
            LEFT JOIN subject_tbl sub ON sch.subject_id = sub.subject_id
            WHERE st.section_id = :section
            GROUP BY st.student_id, st.lastname, st.firstname, sub.subject_name, sch.schedule_day
            ORDER BY st.lastname, st.firstname";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        'date' => $sqlDate,
        'section' => $section
    ]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get section name
    $section_query = "SELECT section_name FROM section_tbl WHERE section_id = ?";
    $section_stmt = $conn->prepare($section_query);
    $section_stmt->execute([$section]);
    $section_name = $section_stmt->fetchColumn();

    // Create email content
    $emailContent = "<html><body>";
    $emailContent .= "<h2>Attendance Summary</h2>";
    $emailContent .= "<p>Good Day Professor!</p>";
    $emailContent .= "<p>This is the attendance summary for " . htmlspecialchars($section_name) . " on " . htmlspecialchars($displayDate) . ".</p>";
    $emailContent .= "<p>Please review the attendance records below:</p>";
    $emailContent .= "<p><strong>Date:</strong> " . htmlspecialchars($displayDate) . "</p>";
    $emailContent .= "<p><strong>Section:</strong> " . htmlspecialchars($section_name) . "</p>";
    
    $emailContent .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    $emailContent .= "<tr><th>Student ID</th><th>Name</th><th>Subject</th><th>Status</th><th>Time In</th><th>Time Out</th></tr>";
    
    foreach ($records as $record) {
        $emailContent .= "<tr>";
        $emailContent .= "<td>" . htmlspecialchars($record['student_id']) . "</td>";
        $emailContent .= "<td>" . htmlspecialchars($record['lastname'] . ", " . $record['firstname']) . "</td>";
        $emailContent .= "<td>" . htmlspecialchars($record['subject_name'] ?? '-') . "</td>";
        $emailContent .= "<td>" . htmlspecialchars($record['attendance_status']) . "</td>";
        $emailContent .= "<td>" . htmlspecialchars($record['time_in'] ?? '-') . "</td>";
        $emailContent .= "<td>" . htmlspecialchars($record['time_out'] ?? '-') . "</td>";
        $emailContent .= "</tr>";
    }
    
    $emailContent .= "</table>";
    $emailContent .= "</body></html>";

    // Configure PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'jhonrey.loreno77@gmail.com';
    $mail->Password = 'rxlk ojvq mxxa ybgf';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('jhonrey.loreno77@gmail.com', 'LabGuard System');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = "Attendance Summary - " . $section_name . " - " . $date;
    $mail->Body = $emailContent;

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);

} catch (Exception $e) {
    error_log("Error sending email: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error sending email: ' . $e->getMessage()]);
}
?> 