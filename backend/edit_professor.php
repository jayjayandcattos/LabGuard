<?php
session_start();
require_once "db.php"; // Ensure this connects properly

if (!isset($_GET['id'])) {
    die("Error: Professor ID missing.");
}

$employee_id = $_GET['id'];

// Fetch professor data
$query = "SELECT * FROM prof_tbl WHERE employee_id = :id";
$stmt = $conn->prepare($query);
$stmt->execute(["id" => $employee_id]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    die("Error: Professor not found.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = $_POST["lastname"];
    $firstname = $_POST["firstname"];
    $mi = $_POST["mi"];
    $rfid_tag = $_POST["rfid_tag"];
    $email = $_POST["email"];
    $role_id = $_POST["role_id"];
    $updated_at = date("Y-m-d H:i:s");

    // Handle photo update
    $photo = $professor["photo"];
    if (!empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo = basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
    }

    // Update the database
    $query = "UPDATE prof_tbl SET lastname=:lastname, firstname=:firstname, mi=:mi, rfid_tag=:rfid_tag, email=:email, role_id=:role_id, photo=:photo WHERE employee_id=:employee_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        "lastname" => $lastname,
        "firstname" => $firstname,
        "mi" => $mi,
        "rfid_tag" => $rfid_tag,
        "email" => $email,
        "role_id" => $role_id,
        "photo" => $photo,
        "employee_id" => $employee_id
    ]);

    header("Location: professors.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Professor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <div class="container mt-4">
        <div class="styles-kwan">

            <h2>Edit Professor</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-2">
                    <label>Last Name</label>
                    <input type="text" name="lastname" class="form-control"
                        value="<?= htmlspecialchars($professor['lastname']) ?>" required>
                </div>
                <div class="mb-2">
                    <label>First Name</label>
                    <input type="text" name="firstname" class="form-control"
                        value="<?= htmlspecialchars($professor['firstname']) ?>" required>
                </div>
                <div class="mb-2">
                    <label>Middle Initial</label>
                    <input type="text" name="mi" class="form-control" value="<?= htmlspecialchars($professor['mi']) ?>">
                </div>
                <div class="mb-2">
                    <label>RFID Tag</label>
                    <input type="text" name="rfid_tag" class="form-control"
                        value="<?= htmlspecialchars($professor['rfid_tag']) ?>" required>
                </div>
                <div class="mb-2">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                        value="<?= htmlspecialchars($professor['email']) ?>" required>
                </div>
                <div class="mb-2">
                    <label>Role</label>
                    <select name="role_id" class="form-control" required>
                        <option value="1" <?= $professor['role_id'] == 1 ? 'selected' : '' ?>>Faculty</option>
                        <option value="2" <?= $professor['role_id'] == 2 ? 'selected' : '' ?>>Admin</option>
                        <option value="3" <?= $professor['role_id'] == 3 ? 'selected' : '' ?>>Professor</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Photo</label>
                    <input type="file" name="photo" class="form-control">
                    <img src="uploads/<?= htmlspecialchars($professor['photo']) ?>" width="50" height="50" class="mt-2">
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="professors.php" class="btn btn-secondary" style="margin-left: 45%; margin-bottom: 20px;">Cancel</a>
            </form>
        </div>
    </div>
</body>

</html>