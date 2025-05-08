<?php
session_start();
require_once "db.php";

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Error: Faculty ID missing.";
    header("Location: faculty.php");
    exit();
}

$id = $_GET['id'];
$query = "SELECT * FROM faculty_tbl WHERE faculty_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faculty) {
    $_SESSION['error'] = "Error: Faculty not found.";
    header("Location: faculty.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = trim($_POST["lastname"]);
    $firstname = trim($_POST["firstname"]);
    $mi = trim($_POST["mi"]);
    $email = trim($_POST["email"]);
    $rfid_tag = trim($_POST["rfid_tag"]);

    try {
        // Handle photo upload if new photo is provided
        if (isset($_FILES["photo"]) && !empty($_FILES["photo"]["name"])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $photo = basename($_FILES["photo"]["name"]);
            $target_file = $target_dir . $photo;

            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                // Update including photo
                $query = "UPDATE faculty_tbl SET lastname = ?, firstname = ?, mi = ?, email = ?, rfid_tag = ?, photo = ? WHERE faculty_user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$lastname, $firstname, $mi, $email, $rfid_tag, $photo, $id]);
            } else {
                // If upload fails, update without changing photo
                $query = "UPDATE faculty_tbl SET lastname = ?, firstname = ?, mi = ?, email = ?, rfid_tag = ? WHERE faculty_user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$lastname, $firstname, $mi, $email, $rfid_tag, $id]);

                $_SESSION['error'] = "Failed to upload new photo, other information updated.";
            }
        } else {
            // Update without photo
            $query = "UPDATE faculty_tbl SET lastname = ?, firstname = ?, mi = ?, email = ?, rfid_tag = ? WHERE faculty_user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$lastname, $firstname, $mi, $email, $rfid_tag, $id]);
        }

        $_SESSION['success'] = "Faculty information updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating faculty: " . $e->getMessage();
    }

    header("Location: faculty.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Faculty</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>

    <div class="styles-kwan">
        <h2>Edit Faculty</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div>
            <div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Last Name:</label>
                        <input type="text" name="lastname" class="form-control"
                            value="<?= htmlspecialchars($faculty['lastname']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">First Name:</label>
                        <input type="text" name="firstname" class="form-control"
                            value="<?= htmlspecialchars($faculty['firstname']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Middle Initial:</label>
                        <input type="text" name="mi" class="form-control"
                            value="<?= htmlspecialchars($faculty['mi']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email:</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($faculty['email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">RFID Tag:</label>
                        <input type="text" name="rfid_tag" class="form-control"
                            value="<?= htmlspecialchars($faculty['rfid_tag']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Current Photo:</label><br>
                        <?php if (!empty($faculty['photo'])): ?>
                            <img src="uploads/<?= htmlspecialchars($faculty['photo']) ?>" width="100" class="rounded mb-2">
                        <?php else: ?>
                            <p>No photo available</p>
                        <?php endif; ?>

                        <div class="mt-2">
                            <label class="form-label">Upload New Photo:</label>
                            <input type="file" name="photo" class="form-control">
                            <small class="text-muted">Leave empty to keep current photo</small>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Update Faculty</button>
                        <a href="faculty.php" class="btn btn-secondary"
                            style="margin-left: 55%; margin-bottom: 20px;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>