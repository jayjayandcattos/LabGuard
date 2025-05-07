<?php
session_start();
require_once "db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = trim($_POST["employee_id"]);
    $lastname = trim($_POST["lastname"]);
    $firstname = trim($_POST["firstname"]);
    $mi = trim($_POST["mi"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $rfid_tag = trim($_POST["rfid_tag"]);

    // Check for duplicates
    $check = $conn->prepare("SELECT * FROM faculty_tbl WHERE employee_id = ? OR email = ? OR rfid_tag = ?");
    $check->execute([$employee_id, $email, $rfid_tag]);

    if ($check->rowCount() > 0) {
        echo "<script>alert('Duplicate entry detected! Employee ID, Email, or RFID already exists.'); window.location.href='faculty.php';</script>";
        exit();
    }

    // Handle photo upload
    $photo = "default.jpg"; // Default photo
    if (isset($_FILES["photo"]) && !empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        
        // Create directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $photo = basename($_FILES["photo"]["name"]);
        $target_file = $target_dir . $photo;
        
        // Debug: Check file upload
        error_log("Attempting to upload faculty photo: " . $target_file);
        
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            error_log("Failed to upload faculty photo: " . print_r($_FILES["photo"]["error"], true));
            // Continue with default photo if upload fails
        }
    }

    // Insert faculty with photo
    $query = "INSERT INTO faculty_tbl (employee_id, role_id, lastname, firstname, mi, email, password, rfid_tag, photo) 
              VALUES (?, 2, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    try {
        $result = $stmt->execute([$employee_id, $lastname, $firstname, $mi, $email, $password, $rfid_tag, $photo]);
        
        if ($result) {
            $_SESSION['success'] = "Faculty member added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add faculty member.";
            error_log("Failed to add faculty. Database error: " . print_r($stmt->errorInfo(), true));
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding faculty: " . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    }

    header("Location: faculty.php");
    exit();
}
?>
