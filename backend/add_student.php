<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Check if we're receiving POST data
    error_log("Received POST data: " . print_r($_POST, true));
    
    // Get form data with validation
    $student_id = trim($_POST["student_id"] ?? '');
    $lastname = trim($_POST["lastname"] ?? '');
    $firstname = trim($_POST["firstname"] ?? '');
    $mi = trim($_POST["mi"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $section_id = trim($_POST["section_id"] ?? '');
    $rfid_tag = trim($_POST["rfid_tag"] ?? ''); // Make sure to include RFID tag

    // Validate required fields
    if (empty($student_id) || empty($lastname) || empty($firstname) || empty($email) || empty($section_id)) {
        $_SESSION['error'] = "All required fields must be filled out.";
        header("Location: students.php");
        exit();
    }
    
    // Handle photo upload
    $photo = "default.jpg"; // Default photo
    if (isset($_FILES["photo"]) && !empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo = basename($_FILES["photo"]["name"]);
        $target_file = $target_dir . $photo;
        
        // Debug: Check file upload
        error_log("Attempting to upload file: " . $target_file);
        
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            error_log("Failed to upload file: " . print_r($_FILES["photo"]["error"], true));
        }
    }

    try {
        // Check if student ID already exists
        $check_query = "SELECT COUNT(*) FROM student_tbl WHERE student_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$student_id]);
        
        if ($check_stmt->fetchColumn() > 0) {
            echo "<script>alert('Student ID already exists!'); window.location.href = 'students.php';</script>";
            exit();
        }
        // Insert new student with role_id = 4 (Student)
        $query = "INSERT INTO student_tbl (student_id, role_id, lastname, firstname, mi, email, rfid_tag, section_id, photo) 
                  VALUES (:student_id, :role_id, :lastname, :firstname, :mi, :email, :rfid_tag, :section_id, :photo)";
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            "student_id" => $student_id,
            "role_id" => 4, // 4 is for Student role
            "lastname" => $lastname,
            "firstname" => $firstname,
            "mi" => $mi,
            "email" => $email,
            "rfid_tag" => $rfid_tag,
            "section_id" => $section_id,
            "photo" => $photo
        ]);

        if ($result) {
            $_SESSION['success'] = "Student added successfully!";
            error_log("Student added successfully: " . $student_id);
        } else {
            $_SESSION['error'] = "Failed to add student.";
            error_log("Failed to add student. Database error: " . print_r($stmt->errorInfo(), true));
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding student: " . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    }

    header("Location: students.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: students.php");
    exit();
}
?>
