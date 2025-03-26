<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    header("Location: login.php");
    exit();
}

$prof_user_id = $_SESSION["user_id"];
$query = "SELECT * FROM prof_tbl WHERE employee_id = :id OR prof_user_id = :prof_user_id";
$stmt = $conn->prepare($query);
$stmt->execute([
    "id" => $_SESSION["user_id"],
    "prof_user_id" => $prof_user_id
]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    die("Error: Professor data not found.");
}

$prof_query = "SELECT lastname FROM prof_tbl WHERE prof_user_id = :prof_user_id";
$prof_stmt = $conn->prepare($prof_query);
$prof_stmt->execute(['prof_user_id' => $prof_user_id]);

$professor_lastname = $prof_stmt->fetch(PDO::FETCH_ASSOC);
$prof_lastname = $professor_lastname ? $professor_lastname['lastname'] : "Unknown";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = $_POST["lastname"];
    $firstname = $_POST["firstname"];
    $mi = $_POST["mi"];
    $email = $_POST["email"];

    $photo = $professor["photo"];
    if (!empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo = basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
    }

    $query = "UPDATE prof_tbl SET 
              lastname=:lastname, 
              firstname=:firstname, 
              mi=:mi, 
              email=:email, 
              photo=:photo 
              WHERE prof_user_id=:prof_user_id";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        "lastname" => $lastname,
        "firstname" => $firstname,
        "mi" => $mi,
        "email" => $email,
        "photo" => $photo,
        "prof_user_id" => $prof_user_id
    ]);

    header("Location: prof_profile.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/prof.css">
</head>

<body>
    <?php include '../sections/prof_nav.php'; ?>
    <?php include '../sections/nav2.php' ?>

    <div id="main-container">
        <h2>MY PROFILE</h2>
        <div class="profile-container">
            <div class="profile-image">
                <img src="uploads/<?= htmlspecialchars($professor['photo']); ?>" alt="Profile Photo">
            </div>
            <div class="profile-form">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Employee ID</label>
                            <input type="text" value="<?= htmlspecialchars($professor['employee_id']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastname" value="<?= htmlspecialchars($professor['lastname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="firstname" value="<?= htmlspecialchars($professor['firstname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Initial</label>
                            <input type="text" name="mi" value="<?= htmlspecialchars($professor['mi']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($professor['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>RFID Tag</label>
                            <input type="text" value="<?= htmlspecialchars($professor['rfid_tag']); ?>" readonly>
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
</body>

</html>