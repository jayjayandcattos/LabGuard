<?php
session_start();
require_once "db.php";

// Default filter option - show all by default
$filter_level = isset($_GET['level']) ? $_GET['level'] : 'all';

// Fetch all sections
$query = "SELECT * FROM section_tbl";

// Add filter condition if a specific level is selected
if ($filter_level != 'all') {
    $query .= " WHERE section_level = :level";
}

$query .= " ORDER BY section_level, section_name";

$stmt = $conn->prepare($query);

// Bind the level parameter if filtering
if ($filter_level != 'all') {
    $stmt->bindValue(':level', $filter_level, PDO::PARAM_STR);
}

$stmt->execute();
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct year levels for the dropdown
$levelQuery = "SELECT DISTINCT section_level FROM section_tbl ORDER BY section_level";
$levelStmt = $conn->prepare($levelQuery);
$levelStmt->execute();
$levels = $levelStmt->fetchAll(PDO::FETCH_COLUMN);

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
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/classroomManagement.js" defer></script>
    <style>
        /* .sort-section {
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        } */
        .sort-dropdown {
            width: 200px;
            display: inline-block;
        }

        .sort-dropdown .dropdown-toggle {
            background-color: #fff;
            color: #000;
            border: 1px solid #ccc;
            width: 100%;
            text-align: left;
        }

        .sort-dropdown .dropdown-menu {
            width: 100%;
        }

        .sort-dropdown .dropdown-item.active {
            background-color: #4169E1;
            color: white;
        }

        .dropdown-item {
            color: #000;
        }
    </style>
</head>

<body>
    <?php include '../sections/nav4.php' ?>
    <?php include '../sections/admin_nav.php' ?>

    <div id="main-container">
        <h2>Section Management</h2>

        <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
            <!-- Add Section Button -->
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSectionModal">Add Section</button>

            <!-- Year Level Filtering Dropdown -->
            <div class="sort-section">
                <div class="dropdown sort-dropdown">
                    <button type="button" id="levelDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width:200px; height:50px;">
                        <?= $filter_level == 'all' ? 'LEVEL' : $filter_level ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="levelDropdown">
                        <li><a class="dropdown-item <?= $filter_level == 'all' ? 'active' : '' ?>" href="?level=all">ALL LEVELS</a></li>
                        <?php foreach ($levels as $level): ?>
                            <?php if (!empty($level)): ?>
                                <li><a class="dropdown-item <?= $filter_level == $level ? 'active' : '' ?>" href="?level=<?= $level ?>"><?= $level ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

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
                        <td><?= htmlspecialchars($section['section_name']) ?></td>
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
                        <h5 class="modal-title" id="addSectionLabel" style="color: black;">Add New Section</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="color: black;">

                        <label style="color: black;">Section Name:</label>
                        <input type="text" name="section_name" class="form-control" required>

                        <label style="color: black;">Section Level:</label>
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