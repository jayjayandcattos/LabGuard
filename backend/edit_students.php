<?php
    session_start();
    require_once "db.php";
    
    $id = $_GET['id'];
    $student = $conn->query("SELECT * FROM students WHERE student_user_id = $id")->fetch();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $lastname = $_POST['lastname'];
        $firstname = $_POST['firstname'];
        $mi = $_POST['mi'];
        $email = $_POST['email'];
        $rfid_tag = $_POST['rfid_tag'];
        $section_id = $_POST['section_id'];
        
        $query = "UPDATE students SET lastname = ?, firstname = ?, mi = ?, email = ?, rfid_tag = ?, section_id = ? WHERE student_user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$lastname, $firstname, $mi, $email, $rfid_tag, $section_id, $id]);
        header("Location: students.php");
    }
    ?>

    <form action="edit_student.php?id=<?= $id ?>" method="POST">
        <input type="text" name="lastname" value="<?= $student['lastname'] ?>" required>
        <input type="text" name="firstname" value="<?= $student['firstname'] ?>" required>
        <input type="text" name="mi" value="<?= $student['mi'] ?>">
        <input type="email" name="email" value="<?= $student['email'] ?>" required>
        <input type="text" name="rfid_tag" value="<?= $student['rfid_tag'] ?>" required>
        <select name="section_id" required>
            <?php
            $sections = $conn->query("SELECT section_id, section_name FROM sections")->fetchAll();
            foreach ($sections as $section) {
                $selected = ($section['section_id'] == $student['section_id']) ? "selected" : "";
                echo "<option value='{$section['section_id']}' $selected>{$section['section_name']}</option>";
            }
            ?>
        </select>
        <button type="submit">Update Student</button>
    </form>