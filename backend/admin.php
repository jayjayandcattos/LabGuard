<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Ensure the database connection is working
if (!$conn) {
    die("Database connection failed!");
}

// Handle admin addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_admin"])) {
    $admin_id = $_POST["admin_id"];
    $role_id = isset($_POST["role_id"]) ? $_POST["role_id"] : 2; // Default role_id to 2 (Admin)
    $lastname = isset($_POST["lastname"]) ? $_POST["lastname"] : "";
    $firstname = isset($_POST["firstname"]) ? $_POST["firstname"] : "";
    $mi = isset($_POST["mi"]) ? $_POST["mi"] : "";
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $created_at = date("Y-m-d H:i:s");

    // Handle photo upload
    $photo = "default.jpg"; // Default photo
    if (!empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo = basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
    }

    // Insert into database
    $query = "INSERT INTO admin_tbl (admin_id, role_id, lastname, firstname, mi, email, password, photo, created_at) 
              VALUES (:admin_id, :role_id, :lastname, :firstname, :mi, :email, :password, :photo, :created_at)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        "admin_id" => $admin_id,
        "role_id" => $role_id,
        "lastname" => $lastname,
        "firstname" => $firstname,
        "mi" => $mi,
        "email" => $email,
        "password" => $password,
        "photo" => $photo,
        "created_at" => $created_at
    ]);

    header("Location: admin.php");
    exit();
}

// Fetch admin data
$query = "SELECT *, CONCAT(lastname, ', ', firstname, ' ', mi) AS fullname FROM admin_tbl";
$stmt = $conn->prepare($query);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="bg-dark text-white p-3 vh-100" style="width: 250px;">
            <h4>Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link text-white">Classroom</a></li>
                <li class="nav-item"><a href="schedule.php" class="nav-link text-white">Schedule</a></li>
                <li class="nav-item"><a href="professors.php" class="nav-link text-white">Professors</a></li>
                <li class="nav-item"><a href="faculty.php" class="nav-link text-white">Faculty</a></li>
                <li class="nav-item"><a href="students.php" class="nav-link text-white">Students</a></li>
                <li class="nav-item"><a href="student_subs.php" class="nav-link text-white">Student Subjects</a></li>
                <li class="nav-item"><a href="student_secs.php" class="nav-link text-white">Student Sections</a></li>
                <li class="nav-item"><a href="admin.php" class="nav-link text-white">Admin</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2 class="mb-4">Admin Management</h2>
            <div class="card p-3 mb-4">
                <h4>Add New Admin</h4>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Admin ID</label>
                            <input type="text" name="admin_id" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Role</label>
                            <select name="role_id" class="form-control" required>
                                <option value="2">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Last Name</label>
                            <input type="text" name="lastname" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>First Name</label>
                            <input type="text" name="firstname" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Middle Initial</label>
                            <input type="text" name="mi" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Photo</label>
                            <input type="file" name="photo" class="form-control">
                        </div>
                    </div>
                    <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
                </form>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Admin ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Photo</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['admin_id']); ?></td>
                            <td><?= htmlspecialchars($admin['fullname']); ?></td>
                            <td><?= htmlspecialchars($admin['email']); ?></td>
                            <td><img src="uploads/<?= htmlspecialchars($admin['photo']); ?>" width="50" height="50"></td>
                            <td><?= htmlspecialchars($admin['created_at']); ?></td>
                            <td>
                                <a href="edit_admin.php?id=<?= htmlspecialchars($admin['admin_id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete_admin.php?id=<?= htmlspecialchars($admin['admin_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 