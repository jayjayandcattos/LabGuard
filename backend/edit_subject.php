<?php
require_once "db.php";

if (isset($_GET["id"])) {
    $subject_id = $_GET["id"];
    $query = "SELECT * FROM subject_tbl WHERE subject_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subject) {
        echo "Subject not found.";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["subject_name"]) && !empty($_POST["prof_user_id"])) {
        $subject_id = $_POST["subject_id"];
        $subject_code = $_POST["subject_code"];
        $subject_name = $_POST["subject_name"];
        $prof_user_id = $_POST["prof_user_id"];

        $query = "UPDATE subject_tbl SET subject_code = ?, subject_name = ?, prof_user_id = ? WHERE subject_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$subject_code, $subject_name, $prof_user_id, $subject_id]);

        header("Location: student_subs.php");
        exit();
    } else {
        echo "Missing required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2>Edit Subject</h2>
        <form action="edit_subject.php" method="POST">
            <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
            
            <label for="subject_code">Course Code:</label>
            <input type="text" name="subject_code" id="subject_code" value="<?php echo htmlspecialchars($subject['subject_code']); ?>" required>

            <label for="subject_name">Course Name:</label>
            <input type="text" name="subject_name" id="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>

            <label for="prof_user_id">Professor:</label>
            <select name="prof_user_id" id="prof_user_id" required>
                <option value="">Select Professor</option>
                <?php
                $profQuery = "SELECT prof_user_id, CONCAT(lastname, ', ', firstname) AS prof_name FROM prof_tbl";
                $profStmt = $conn->query($profQuery);
                while ($professor = $profStmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = ($professor['prof_user_id'] == $subject['prof_user_id']) ? 'selected' : '';
                    echo "<option value='{$professor['prof_user_id']}' $selected>{$professor['prof_name']}</option>";
                }
                ?>
            </select>

            <button type="submit">Update Subject</button>
            <a href="student_subs.php">Cancel</a>
        </form>
    </div>
</body>
</html>
