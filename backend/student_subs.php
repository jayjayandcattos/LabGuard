<?php
session_start();
require_once "db.php"; // Ensure this file properly connects to the database

// Fetch all subjects with assigned professors
$query = "
    SELECT subj.subject_id, subj.subject_code, subj.subject_name, p.firstname, p.lastname
    FROM subject_tbl subj
    LEFT JOIN prof_tbl p ON subj.prof_user_id = p.prof_user_id
    ORDER BY subj.subject_name";

$stmt = $conn->prepare($query);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['name'])) {
    $name_parts = explode(' ', $_SESSION['name']);
    $admin_firstname = $name_parts[0];
    $admin_lastname = isset($name_parts[1]) ? $name_parts[1] : 'Unknown';
} else {
    $admin_lastname = 'Unknown'; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/classroomManagement.js" defer></script>
</head>
<body>
<?php include '../sections/nav4.php' ?>
<?php include '../sections/admin_nav.php' ?>
        
<div id="main-container">
            <h2>Subject Management</h2>
            
            <!-- Add Subject Button -->
            <button class="btn btn-success mb-3 mt-2" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add Subject</button>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Assigned Professor</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td> <?= htmlspecialchars($subject ['subject_code']) ?> </td>
                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td><?= $subject['firstname'] ? htmlspecialchars($subject['firstname'] . ' ' . $subject['lastname']) : 'Unassigned' ?></td>
                            <td>
                                <a href="edit_subject.php?id=<?= $subject['subject_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_subject.php?id=<?= $subject['subject_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="add_subject.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSubjectLabel" style="color: black;">Add New Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label style="color: black;">Course Code:</label>
                        <input type="text" name="subject_code" class="form-control" required>

                        <label style="color: black;">Subject Name:</label>
                        <input type="text" name="subject_name" class="form-control" required>

                        <label style="color: black;">Assign Professor:</label>
                        <select name="prof_user_id" class="form-control" style="margin-left: 50px;">
                            <option value="" style="color: black;">-- Select a Professor --</option>
                            <?php
                            $profQuery = "SELECT prof_user_id, firstname, lastname FROM prof_tbl";
                            $profStmt = $conn->prepare($profQuery);
                            $profStmt->execute();
                            while ($prof = $profStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$prof['prof_user_id']}' style='color: black;'>{$prof['firstname']} {$prof['lastname']}</option>";
                            }   
                            ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
