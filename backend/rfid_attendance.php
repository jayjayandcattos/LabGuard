<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendanceDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rfid'])) {
    $rfid = $_POST['rfid'];

    // Find user linked to RFID tag
    $query = "SELECT user_id, role_id FROM tbl_users WHERE rfid_tag = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $rfid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        $role_id = $user['role_id'];

        // Check last status (IN/OUT)
        $check_attendance = "SELECT * FROM tbl_attendance WHERE user_id = ? ORDER BY log_time DESC LIMIT 1";
        $stmt = $conn->prepare($check_attendance);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $log_result = $stmt->get_result();
        
        if ($log_result->num_rows > 0) {
            $last_log = $log_result->fetch_assoc();
            $status = ($last_log['status'] == 'IN') ? 'OUT' : 'IN';
        } else {
            $status = 'IN';  // First scan defaults to "IN"
        }

        // Insert into tbl_attendance
        $insert_log = "INSERT INTO tbl_attendance (user_id, status, log_time) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($insert_log);
        $stmt->bind_param("is", $user_id, $status);
        if ($stmt->execute()) {
            echo "Attendance recorded: $status";
        } else {
            echo "Error: " . $conn->error;
        }

        // Update classroom occupancy if professor
        if ($role_id == 2 && $status == 'IN') {
            $update_classroom = "UPDATE tbl_crooms SET current_prof_id = ? WHERE status_id = 1";
            $stmt = $conn->prepare($update_classroom);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        } elseif ($role_id == 2 && $status == 'OUT') {
            $update_classroom = "UPDATE tbl_crooms SET current_prof_id = NULL WHERE current_prof_id = ?";
            $stmt = $conn->prepare($update_classroom);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
    } else {
        echo "RFID Not Registered!";
    }
}
?>
