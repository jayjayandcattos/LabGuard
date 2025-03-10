<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prof_user_id = $_POST["prof_user_id"];
    $subject_id = $_POST["subject_id"];
    $section_id = $_POST["section_id"];
    $room_id = $_POST["room_id"];
    $schedule_time = $_POST["schedule_time"];
    $time_in = $_POST["time_in"];
    $time_out = $_POST["time_out"];
    $schedule_day = $_POST["schedule_day"];

    // Check for schedule conflicts
    $conflict_query = "SELECT COUNT(*) as count FROM schedule_tbl 
                      WHERE room_id = :room_id 
                      AND schedule_day = :schedule_day
                      AND (
                          (schedule_time BETWEEN :start_time AND :end_time)
                          OR (:schedule_time BETWEEN schedule_time AND time_out)
                      )";
    
    $stmt = $conn->prepare($conflict_query);
    $stmt->execute([
        "room_id" => $room_id,
        "schedule_day" => $schedule_day,
        "start_time" => $schedule_time,
        "end_time" => $time_out,
        "schedule_time" => $schedule_time
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $_SESSION['error'] = "Schedule conflict detected. Please choose a different time or room.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // If no conflicts, add the schedule
    $query = "INSERT INTO schedule_tbl (prof_user_id, subject_id, section_id, room_id, 
              schedule_time, time_in, time_out, schedule_day) 
              VALUES (:prof_user_id, :subject_id, :section_id, :room_id, 
              :schedule_time, :time_in, :time_out, :schedule_day)";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute([
            "prof_user_id" => $prof_user_id,
            "subject_id" => $subject_id,
            "section_id" => $section_id,
            "room_id" => $room_id,
            "schedule_time" => $schedule_time,
            "time_in" => $time_in,
            "time_out" => $time_out,
            "schedule_day" => $schedule_day
        ]);

        $_SESSION['success'] = "Schedule added successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding schedule: " . $e->getMessage();
    }

    // Redirect back to the previous page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: schedule.php");
    exit();
}
?>
