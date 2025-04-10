<?php
session_start();
require_once "db.php";

// Fetch all sections
$query = "SELECT * FROM section_tbl ORDER BY section_level, section_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['name'])) {
    $name_parts = explode(' ', $_SESSION['name']);
    $admin_firstname = $name_parts[0];
    $admin_lastname = isset($name_parts[1]) ? $name_parts[1] : 'Unknown';
} else {
    $admin_lastname = 'Unknown'; 
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sections Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="../js/classroomManagement.js" defer></script>
</head>
<body>
<?php include '../sections/nav4.php' ?>
<?php include '../sections/admin_nav.php' ?>
        
<div id="main-container">
            <h2>Section Management</h2>
            
            <!-- Add Section Button -->
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addSectionModal">Add Section</button>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Section Name</th>
                        <th>Section Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td> <?= htmlspecialchars($section ['section_name']) ?> </td>
                            <td><?= htmlspecialchars($section['section_level']) ?></td>
                            <td>
                                <a href="edit_section.php?id=<?= $section['section_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_section.php?id=<?= $section['section_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="add_section.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSectionLabel">Add New Section</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <label>Section Name:</label>
                        <input type="text" name="section_name" class="form-control" required>

                        <label>Section Level:</label>
                        <input type="text" name="section_level" class="form-control" required>
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
