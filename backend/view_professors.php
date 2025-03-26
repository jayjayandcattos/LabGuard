<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

$query = "SELECT lastname FROM faculty_tbl WHERE employee_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_STR);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);


// Fetch professors data
$query = "SELECT *, CONCAT(lastname, ', ', firstname, ' ', mi) AS fullname FROM prof_tbl";
$stmt = $conn->prepare($query);
$stmt->execute();
$professors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Professors</title>
    <link rel="stylesheet" href="../css/colorum.css">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">

</head>
<body>
    <?php include '../sections/nav3.php'; ?>
    <?php include '../sections/fac_nav.php'; ?>
    <div id="main-container">
            <h2>PROFESSOR PROFILES</h2>
        <br>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Photo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($professors as $professor): ?>
                        <tr>
                            <td><?= htmlspecialchars($professor['employee_id']); ?></td>
                            <td><?= htmlspecialchars($professor['fullname']); ?></td>
                            <td><?= htmlspecialchars($professor['email']); ?></td>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($professor['photo']); ?>" 
                                     width="50" height="50" 
                                     alt="Professor Photo"
                                     class="rounded-circle">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 