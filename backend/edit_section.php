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
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <div class="container mt-5">
        <div class="styles-kwan">
            <h2>Edit Section</h2>
            <form action="edit_section.php" method="POST">
                <input class="form-control" type="hidden" name="section_id"
                    value="<?php echo htmlspecialchars($section['section_id']); ?>">
                <br>
                <label for="section_name">Course Name:</label>
                <input class="form-control" type="text" name="section_name" id="section_name"
                    value="<?php echo htmlspecialchars($section['section_name']); ?>" required>
                <br>

                <label for="section_level">Section Level:</label>
                <input class="form-control" type="text" name="section_level" id="section_level"
                    value="<?php echo htmlspecialchars($section['section_level']); ?>" required>
                <br>

                <button class="btn btn-primary" type="submit">Update section</button>
                <a class="btn btn-secondary mt-3" href="student_secs.php"
                    style="margin-left: 45%; margin-bottom: 20px;">Cancel</a>
            </form>
        </div>
    </div>
</body>

</html>