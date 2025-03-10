<?php
session_start();
require_once "db.php"; // Ensure this connects properly

if (!isset($_GET['id'])) {
    die("Error: Admin ID missing.");
}

$admin_id = $_GET['id'];

// Fetch admin data
$query = "SELECT * FROM admin_tbl WHERE admin_id = :id";
$stmt = $conn->prepare($query);
$stmt->execute(["id" => $admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Error: Admin not found.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = $_POST["lastname"];
    $firstname = $_POST["firstname"];
    $mi = $_POST["mi"];
    $email = $_POST["email"];
    $role_id = $_POST["role_id"];
    $updated_at = date("Y-m-d H:i:s");

    // Handle photo update
    $photo = $admin["photo"];
    if (!empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo = basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
    }

    // Update the database
    $query = "UPDATE admin_tbl SET lastname=:lastname, firstname=:firstname, mi=:mi, email=:email, role_id=:role_id, photo=:photo WHERE admin_id=:admin_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        "lastname" => $lastname,
        "firstname" => $firstname,
        "mi" => $mi,
        "email" => $email,
        "role_id" => $role_id,
        "photo" => $photo,
        "admin_id" => $admin_id
    ]);

    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Edit Admin</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-2">
                <label>Last Name</label>
                <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($admin['lastname']) ?>" required>
            </div>
            <div class="mb-2">
                <label>First Name</label>
                <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($admin['firstname']) ?>" required>
            </div>
            <div class="mb-2">
                <label>Middle Initial</label>
                <input type="text" name="mi" class="form-control" value="<?= htmlspecialchars($admin['mi']) ?>">
            </div>
            <div class="mb-2">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
            </div>
            <div class="mb-2">
                <label>Role</label>
                <select name="role_id" class="form-control" required>
                    <option value="2" <?= $admin['role_id'] == 2 ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Photo</label>
                <input type="file" name="photo" class="form-control">
                <img src="uploads/<?= htmlspecialchars($admin['photo']) ?>" width="50" height="50" class="mt-2">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="admin.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html> 