<?php
session_start();
require_once "db.php";

// Get student ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    $_SESSION['error'] = "Invalid student ID";
    header("Location: students.php");
    exit();
}

// Fetch student data
$query = "SELECT * FROM student_tbl WHERE student_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: students.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = trim($_POST['lastname']);
    $firstname = trim($_POST['firstname']);
    $mi = trim($_POST['mi']);
    $email = trim($_POST['email']);
    $rfid_tag = trim($_POST['rfid_tag']);
    $section_id = trim($_POST['section_id']);
    
    try {
        // Check if RFID tag exists for other students
        $check_query = "SELECT COUNT(*) FROM student_tbl WHERE rfid_tag = ? AND student_user_id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$rfid_tag, $id]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "RFID tag already exists!";
            header("Location: edit_student.php?id=" . $id);
            exit();
        }

        // Handle photo upload if new photo is provided
        if (isset($_FILES["photo"]) && !empty($_FILES["photo"]["name"])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $photo = basename($_FILES["photo"]["name"]);
            $target_file = $target_dir . $photo;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
            
            // Update query with photo
            $query = "UPDATE student_tbl SET 
                     lastname = ?, firstname = ?, mi = ?, 
                     email = ?, rfid_tag = ?, section_id = ?, 
                     photo = ? 
                     WHERE student_user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$lastname, $firstname, $mi, $email, $rfid_tag, $section_id, $photo, $id]);
        } else {
            // Update query without photo
            $query = "UPDATE student_tbl SET 
                     lastname = ?, firstname = ?, mi = ?, 
                     email = ?, rfid_tag = ?, section_id = ? 
                     WHERE student_user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$lastname, $firstname, $mi, $email, $rfid_tag, $section_id, $id]);
        }

        header("Location: students.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating student: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Edit Student</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="edit_student.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Student ID:</label>
                <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label>Last Name:</label>
                <input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname']) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>First Name:</label>
                <input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname']) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Middle Initial:</label>
                <input type="text" name="mi" value="<?= htmlspecialchars($student['mi']) ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>RFID Tag:</label>
                <input type="text" name="rfid_tag" value="<?= htmlspecialchars($student['rfid_tag']) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Section:</label>
                <select name="section_id" class="form-control" required>
                    <?php
                    $sections = $conn->query("SELECT * FROM section_tbl")->fetchAll();
                    foreach ($sections as $section) {
                        $selected = ($section['section_id'] == $student['section_id']) ? "selected" : "";
                        echo "<option value='" . $section['section_id'] . "' $selected>" . 
                             htmlspecialchars($section['section_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Current Photo:</label><br>
                <img src="uploads/<?= htmlspecialchars($student['photo']) ?>" width="100" class="mb-2"><br>
                <label>Update Photo:</label>
                <input type="file" name="photo" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Update Student</button>
            <a href="students.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html> 