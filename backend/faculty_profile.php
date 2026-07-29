<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

$faculty_user_id = $_SESSION["user_id"];
$query = "SELECT * FROM faculty_tbl WHERE employee_id = :id OR faculty_user_id = :faculty_user_id";
$stmt = $conn->prepare($query);
$stmt->execute([
    "id" => $_SESSION["user_id"],
    "faculty_user_id" => $faculty_user_id
]);
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faculty) {
    die("Error: Faculty data not found.");
}

$faculty_query = "SELECT lastname FROM faculty_tbl WHERE faculty_user_id = :faculty_user_id";
$faculty_stmt = $conn->prepare($faculty_query);
$faculty_stmt->execute(['faculty_user_id' => $faculty_user_id]);

$faculty_lastname = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
$faculty_lastname = $faculty_lastname ? $faculty_lastname['lastname'] : "Unknown";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = $_POST["lastname"];
    $firstname = $_POST["firstname"];
    $mi = $_POST["mi"];
    $email = $_POST["email"];

    $photo = $faculty["photo"];
    if (!empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo = basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
    }

    $query = "UPDATE faculty_tbl SET 
              lastname=:lastname, 
              firstname=:firstname, 
              mi=:mi, 
              email=:email, 
              photo=:photo 
              WHERE faculty_user_id=:faculty_user_id";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        "lastname" => $lastname,
        "firstname" => $firstname,
        "mi" => $mi,
        "email" => $email,
        "photo" => $photo,
        "faculty_user_id" => $faculty_user_id
    ]);

    header("Location: faculty_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Technician Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/prof.css">
<style>
.form-group input {
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 10px;
  backdrop-filter: blur(5px);
  width: 200px ;
}</style>
</head>

<body>
    <?php include '../sections/fac_nav.php'; ?>
    <?php include '../sections/nav3.php' ?>

    <div id="main-container">
        <h2>MY PROFILE</h2>
        <div class="profile-container">
            <div class="profile-image">
                <img src="uploads/<?= htmlspecialchars($faculty['photo'] ?? 'default.png'); ?>" alt="Profile Photo">
            </div>
            <div class="profile-form">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Employee ID</label>
                            <input type="text" value="<?= htmlspecialchars($faculty['employee_id'] ?? ''); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastname" value="<?= htmlspecialchars($faculty['lastname'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="firstname" value="<?= htmlspecialchars($faculty['firstname'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Initial</label>
                            <input type="text" name="mi" value="<?= htmlspecialchars($faculty['mi'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($faculty['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>RFID Tag</label>
                            <input type="text" value="<?= htmlspecialchars($faculty['rfid_tag'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <input type="file" name="photo" id="imgUpload" class="custom-file-input">
                            <label for="imgUpload" class="custom-file-label" data-text="No file chosen">
                                Update Photo
                            </label>
                        </div>
                        <button type="submit" class="update-button">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('imgUpload').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var label = document.querySelector('.custom-file-label');
            label.textContent = fileName;
        });
    </script>
</body>

</html> 