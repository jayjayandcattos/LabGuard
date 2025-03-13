<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professor") {
    header("Location: login.php");
    exit();
}

// Fetch professor's data
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

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastname = $_POST["lastname"];
    $firstname = $_POST["firstname"];
    $mi = $_POST["mi"];
    $email = $_POST["email"];

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

    // Refresh the page to show updated info
    header("Location: prof_profile.php");
    exit();
}
// Fetch Professor's Last Name
$prof_query = "SELECT lastname FROM prof_tbl WHERE prof_user_id = :prof_user_id";
$prof_stmt = $conn->prepare($prof_query);

if ($prof_stmt->execute(['prof_user_id' => $prof_user_id])) {
    $professor = $prof_stmt->fetch(PDO::FETCH_ASSOC);

    if ($professor) {
        $prof_lastname = $professor['lastname'];
    } else {
        error_log("No professor found with prof_user_id: " . $prof_user_id);
        $prof_lastname = "Unknown";
    }
} else {
    error_log("Query execution failed: " . implode(" | ", $prof_stmt->errorInfo()));
    $prof_lastname = "Error";
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

    <link rel="stylesheet" href="../css/prof.css">
</head>

<body>
    <div class="professor-header">
        <h1>PROFESSOR PROFILE</h1>
        <p>WELCOME PROFESSOR <?= htmlspecialchars($prof_lastname); ?>!</p>
    </div>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class=" text-white p-3 ">

            <ul class=" nav flex-column" style="margin-left: 10%;">
                <li class="nav-item"><a href="prof_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="prof_students.php" class="nav-link text-white ">Students Profile</a></li>
                <li class="nav-item"><a href="prof_schedule.php" class="nav-link text-white active">My Schedule</a></li>
                <li class="nav-item"><a href="prof_attendance.php" class="nav-link text-white">Attendance</a></li>
                <li class="nav-item"><a href="prof_profile.php" class="nav-link text-white">My Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2>My Profile</h2>
            <div id="main" class="card p-3">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <img src="uploads/<?= htmlspecialchars($professor['photo']); ?>"
                            class="rounded-circle mb-3"
                            alt="Profile Photo"
                            style="width: 200px; height: 200px; object-fit: cover;">
                    </div>
                    <div ID="data-to-to input" class="col-md-12">
                        <form method="POST" enctype="multipart/form-data">
                            <div id="inputs" class="mb-3">
                                <label>Employee ID</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($professor['employee_id']); ?>" readonly>
                            </div>
                            <div id="inputs" class="mb-3">
                                <label>Last Name</label>
                                <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($professor['lastname']); ?>" required>
                            </div>
                            <div id="inputs" class="mb-3">
                                <label>First Name</label>
                                <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($professor['firstname']); ?>" required>
                            </div>
                            <div id="inputs" class="mb-3">
                                <label>Middle Initial</label>
                                <input type="text" name="mi" class="form-control" value="<?= htmlspecialchars($professor['mi']); ?>">
                            </div>
                            <div id="inputs" class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($professor['email']); ?>" required>
                            </div>
                            <div id="inputs" class="mb-3">
                                <label>RFID Tag</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($professor['rfid_tag']); ?>" readonly>
                            </div>
                            <div id="inputs" class="mb-3">
                                <label>Update Photo</label>
                                <input type="file" name="photo" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>