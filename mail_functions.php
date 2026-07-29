<?php
// backend/mail_functions.php
// Functions for sending emails using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mail_config.php';

/**
 * Send an email notification to a professor about absent students
 * 
 * @param int $prof_id Professor ID
 * @param int $schedule_id Schedule ID
 * @param string $current_date Current date
 * @return bool True if email was sent, false otherwise
 */
function sendAbsentStudentsEmail($prof_id, $schedule_id, $current_date) {
    global $conn;
    
    try {
        // Get professor email
        $prof_query = "SELECT p.email, p.firstname, p.lastname, s.subject_name, s.subject_code,
                      sc.schedule_time, sc.schedule_day
                      FROM prof_tbl p
                      JOIN schedule_tbl sc ON sc.prof_user_id = p.prof_user_id
                      JOIN subject_tbl s ON s.subject_id = sc.subject_id
                      WHERE p.prof_user_id = ? AND sc.schedule_id = ?";
        $prof_stmt = $conn->prepare($prof_query);
        $prof_stmt->execute([$prof_id, $schedule_id]);
        $prof_info = $prof_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prof_info) {
            error_log("Professor or schedule information not found");
            return false;
        }
        
        // Find all students in the section for this schedule who didn't check in
        $students_query = "SELECT s.student_id, s.firstname, s.lastname, s.email, sec.section_name
                          FROM student_tbl s
                          JOIN section_tbl sec ON sec.section_id = s.section_id
                          JOIN schedule_tbl sc ON sc.section_id = sec.section_id
                          WHERE sc.schedule_id = ?
                          AND s.student_user_id NOT IN (
                              SELECT a.student_id FROM attendance_tbl a 
                              WHERE a.schedule_id = ? 
                              AND DATE(a.time_in) = ?
                          )";
        $students_stmt = $conn->prepare($students_query);
        $students_stmt->execute([$schedule_id, $schedule_id, $current_date]);
        $absent_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no absent students, no need to send email
        if (empty($absent_students)) {
            error_log("No absent students found for schedule ID $schedule_id on $current_date");
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPDebug  = MAIL_DEBUG;
        
        // Recipients
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($prof_info['email'], $prof_info['firstname'] . ' ' . $prof_info['lastname']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Absent Students Report - {$prof_info['subject_code']} ({$current_date})";
        
        // Build email body
        $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { padding: 20px; }
                h2 { color: #2c3e50; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .header { background-color: #3498db; color: white; padding: 10px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>LabGuard Attendance System</h2>
            </div>
            <div class='container'>
                <h3>Dear Professor {$prof_info['firstname']} {$prof_info['lastname']},</h3>
                <p>The following students were absent from your class today:</p>
                
                <p><strong>Class Details:</strong><br>
                Subject: {$prof_info['subject_code']} - {$prof_info['subject_name']}<br>
                Schedule: {$prof_info['schedule_day']} at " . date('h:i A', strtotime($prof_info['schedule_time'])) . "<br>
                Date: " . date('F j, Y', strtotime($current_date)) . "</p>
                
                <table>
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                    </tr>";
        
        $count = 1;
        foreach ($absent_students as $student) {
            $email_body .= "
                    <tr>
                        <td>{$count}</td>
                        <td>{$student['student_id']}</td>
                        <td>{$student['lastname']}, {$student['firstname']}</td>
                    </tr>";
            $count++;
        }
        
        $email_body .= "
                </table>
                <p>This is an automated message from the LabGuard Attendance System.</p>
            </div>
        </body>
        </html>";
        
        $mail->Body = $email_body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $email_body));
        
        $mail->send();
        error_log("Absent students email sent to {$prof_info['email']} for schedule ID $schedule_id");
        return true;
        
    } catch (Exception $e) {
        error_log("Error sending absent students email: " . $e->getMessage());
        return false;
    }
}
?>
