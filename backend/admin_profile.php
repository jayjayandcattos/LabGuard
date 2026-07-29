<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$admin_user_id = $_SESSION["user_id"];
$query = "SELECT * FROM admin_tbl WHERE admin_id = :id OR admin_user_id = :admin_user_id";
$stmt = $conn->prepare($query);
$stmt->execute([
    "id" => $_SESSION["user_id"],
    "admin_user_id" => $admin_user_id
]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Error: Admin data not found.");
}

$admin_query = "SELECT lastname FROM admin_tbl WHERE admin_user_id = :admin_user_id";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->execute(['admin_user_id' => $admin_user_id]);

$admin_lastname = $admin_stmt->fetch(PDO::FETCH_ASSOC);
$admin_lastname = $admin_lastname ? $admin_lastname['lastname'] : "Unknown";

$update_status = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $lastname = $_POST["lastname"];
        $firstname = $_POST["firstname"];
        $email = $_POST["email"];

        $photo = $admin["photo"];
        if (!empty($_FILES["photo"]["name"])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $photo = basename($_FILES["photo"]["name"]);
            $upload_path = $target_dir . $photo;
            
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_path)) {
                throw new Exception("Failed to upload image to " . $upload_path);
            }
        }

        // Check which admin ID to use based on database structure
        $id_field = isset($admin['admin_id']) ? 'admin_id' : 'admin_user_id';
        $id_value = isset($admin['admin_id']) ? $admin['admin_id'] : $admin_user_id;
        
        $query = "UPDATE admin_tbl SET 
                lastname=:lastname, 
                firstname=:firstname, 
                email=:email, 
                photo=:photo 
                WHERE $id_field=:id_value";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            "lastname" => $lastname,
            "firstname" => $firstname,
            "email" => $email,
            "photo" => $photo,
            "id_value" => $id_value
        ]);
        
        if ($result) {
            $update_status = '<div class="alert alert-success">Profile updated successfully!</div>';
            // Refresh admin data after update
            $refresh_query = "SELECT * FROM admin_tbl WHERE admin_id = :id OR admin_user_id = :admin_user_id";
            $stmt = $conn->prepare($refresh_query);
            $stmt->execute([
                "id" => $_SESSION["user_id"],
                "admin_user_id" => $admin_user_id
            ]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $update_status = '<div class="alert alert-danger">Update failed: ' . implode(', ', $stmt->errorInfo()) . '</div>';
        }
    } catch (Exception $e) {
        $update_status = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/prof.css">
        <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
    .alert {
        margin: 10px 0;
        padding: 10px;
        border-radius: 5px;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    </style>
</head>

<body>
    <?php include '../sections/admin_nav.php'; ?>
    <?php include '../sections/nav4.php' ?>

    <div id="main-container">
        <h2>MY PROFILE</h2>
        
        <?php if (!empty($update_status)): ?>
            <?= $update_status ?>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-image">
                <img src="uploads/<?= htmlspecialchars($admin['photo'] ?? 'default.png'); ?>" alt="Profile Photo" id="profile-preview">
            </div>
            <div class="profile-form">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Admin ID</label>
                            <input type="text" value="<?= htmlspecialchars($admin['admin_id'] ?? ''); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastname" value="<?= htmlspecialchars($admin['lastname'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="firstname" value="<?= htmlspecialchars($admin['firstname'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? ''); ?>" required>
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
            if (e.target.files && e.target.files.length > 0) {
                var fileName = e.target.files[0].name;
                var label = document.querySelector('.custom-file-label');
                label.textContent = fileName;
                
                // Preview the image
                var file = e.target.files[0];
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html> 