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
<?php include 'prof_nav.php'?>

        <!-- Main Content -->
        <div id="main-profile" class="container-fluid p-4">
            <h2 >My Profile</h2>
            <div  class=" p-3">
                <div class="row">
                    <div class="col-md-8 text-center mb-3">
                        <img src="uploads/<?= htmlspecialchars($professor['photo']); ?>" 
                             class="rounded-circle mb-3" alt="Profile Photo" style="width: 100px; height: 100px; display:inline; position: grid; ">
                     </div>
                        <div  class="col-md-3">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="name"> 
                                    <div class="mb-3">
                                        <label>Employee ID</label>
                                        <input id="data-to" type="text" class="form-control" value="<?= htmlspecialchars($professor['employee_id']); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label>Last Name</label>
                                        <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($professor['lastname']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>First Name</label>
                                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($professor['firstname']); ?>" required>
                                    </div>
                                <div class="mb-3">
                                    <label>Middle Initial</label>
                                    <input type="text" name="mi" class="form-control" value="<?= htmlspecialchars($professor['mi']); ?>">
                                </div> <button type="submit" class="updatebtn">Update Profile</button>
                                </div>
                                <div class="miscellaneousInfo">
                                    <div class="mb-3">
                                     <label>Email</label>
                                     <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($professor['email']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                     <label>RFID Tag</label>
                                     <input type="text" class="form-control" value="<?= htmlspecialchars($professor['rfid_tag']); ?>" readonly>
                                     </div>
                                    <div class="mb-3">
                                      <label>Update Photo</label>
                                        <input type="file" name="photo" id="imgUpload">   
                                    </div>
                                </div>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

</body>
</html> 