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

// Get all subjects for the filter dropdown
$subject_query = "SELECT subject_id, subject_code, subject_name FROM subject_tbl ORDER BY subject_name";
$subject_stmt = $conn->prepare($subject_query);
$subject_stmt->execute();
$subjects = $subject_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if a subject filter is applied
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : 'all';

// Fetch professors data based on filter
if ($subject_filter != 'all') {
    $query = "SELECT p.*, CONCAT(p.lastname, ', ', p.firstname, ' ', p.mi) AS fullname, 
             s.subject_code, s.subject_name 
             FROM prof_tbl p
             JOIN subject_tbl s ON p.prof_user_id = s.prof_user_id
             WHERE s.subject_id = :subject_id
             ORDER BY p.lastname, p.firstname";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':subject_id', $subject_filter, PDO::PARAM_INT);
} else {
    $query = "SELECT p.*, CONCAT(p.lastname, ', ', p.firstname, ' ', p.mi) AS fullname,
             GROUP_CONCAT(DISTINCT s.subject_code SEPARATOR ', ') as subjects
             FROM prof_tbl p
             LEFT JOIN subject_tbl s ON p.prof_user_id = s.prof_user_id
             GROUP BY p.prof_user_id
             ORDER BY p.lastname, p.firstname";
    $stmt = $conn->prepare($query);
}

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
    <style>
        .filter-section {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
/* 
        .filter-form select {
            padding: 8px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid #ccc;
        } */

        .filter-form button,
        .filter-form a {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .filter-form button {
            background-color: #4CAF50;
            color: white;
        }

        .filter-form a {
            background-color: #f44336;
            color: white;
            display: inline-block;
        }

        .filter-form button:hover {
            background-color: #45a049;
        }

        .filter-form a:hover {
            background-color: #d32f2f;
        }
    </style>
</head>

<body>
    <?php include '../sections/nav3.php'; ?>
    <?php include '../sections/fac_nav.php'; ?>
    <div id="main-container">
        <h2>PROFESSOR PROFILES</h2>

        <!-- Subject Filter -->
        <div class="block">

            <div class="filter-section">
                <h3>Filter by Subject</h3>
                <form method="GET" action="" class="filter-form">
                    <select name="subject" id="subject" style="width: 700px;">
                        <option value="all" <?php echo $subject_filter == 'all' ? 'selected' : ''; ?>>All Subjects
                        </option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>" <?php echo $subject_filter == $subject['subject_id'] ? 'selected' : ''; ?>>
                                <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Filter</button>
                    <?php if ($subject_filter != 'all'): ?>
                        <a href="view_professors.php">Clear Filter</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <br>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject(s)</th>
                    <th>Photo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($professors) > 0): ?>
                    <?php foreach ($professors as $professor): ?>
                        <tr>
                            <td><?= htmlspecialchars($professor['employee_id']); ?></td>
                            <td><?= htmlspecialchars($professor['fullname']); ?></td>
                            <td><?= htmlspecialchars($professor['email']); ?></td>
                            <td>
                                <?php if ($subject_filter != 'all'): ?>
                                    <?= htmlspecialchars($professor['subject_code'] . ' - ' . $professor['subject_name']); ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($professor['subjects'] ?? 'No subjects assigned'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($professor['photo']); ?>" width="50" height="50"
                                    alt="Professor Photo" class="rounded-circle">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No professors found for the selected subject</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>