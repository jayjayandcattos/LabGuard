<?php
require_once "db.php";

if (isset($_GET["id"])) {
    $section_id = $_GET["id"];
    $query = "SELECT * FROM section_tbl WHERE section_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$section_id]);
    $section = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$section) {
        echo "section not found.";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["section_name"]) && !empty($_POST["section_level"])) {
        $section_id = $_POST["section_id"];
        $section_name = $_POST["section_name"];
        $section_level = $_POST["section_level"];

        $query = "UPDATE section_tbl SET section_name = ?, section_level = ? WHERE section_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$section_name, $section_level, $section_id]);

        header("Location: student_secs.php");
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
    <title>Edit Section</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2>Edit Section</h2>
        <form action="edit_section.php" method="POST">
            <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section['section_id']); ?>">

            <label for="section_name">Course Name:</label>
            <input type="text" name="section_name" id="section_name" value="<?php echo htmlspecialchars($section['section_name']); ?>" required>

            <label for="section_level">Section Level:</label>
            <input type="text" name="section_level" id="section_level" value="<?php echo htmlspecialchars($section['section_level']); ?>" required>

            <button type="submit">Update section</button>
            <a href="student_secs.php">Cancel</a>
        </form>
    </div>
</body>
</html>
