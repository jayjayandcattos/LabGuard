<?php
session_start();
require_once "db.php";

// Ensure user is logged in and has the correct role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

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
    <title>Faculty Panel</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Global Styles (if needed) -->
    <link rel="stylesheet" href="styles.css">

    <link rel="stylesheet" href="../css/view_professor.css">

    <style>
        /* Time display styling */
        #current-time {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            position: fixed;
            right: 20px;
            /* Position it at the top-right of the screen */
            top: 20px;
            /* Adjust position from the top */
            z-index: 10;
            /* Ensure it is always above other elements */
            padding: 5px;
            /* Adds space around the text */
            border: 2px solid white;
            /* White border around the time */
            border-radius: 5px;
            /* Round the corners of the border */
        }

        /* Responsive Design for smaller screens */
        @media (max-width: 768px) {
            #current-time {
                font-size: 1rem;
                /* Decrease font size for smaller screens */
                right: 10px;
                /* Adjust positioning */
                top: 10px;
                /* Adjust positioning */
            }
        }

        /* Welcome message styling */
        .welcomemsg {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            font-weight: normal;
            color: white;
            text-align: center;
            margin-top: 10px;
        }

        /* Faculty panel title styling */
        .faculty-panel-title {
            text-align: center;
            /* Ensures the title is centered */
            font-family: 'Orbitron', sans-serif;
            /* Apply the Orbitron font */
            font-size: 2rem;
            /* Set the font size */
            font-weight: bold;
            /* Make the font bold */
            margin-bottom: 20px;
            /* Adds space between title and content */
            border-bottom: 2px solid white;
            /* Adds a line under the title */
            padding-bottom: 10px;
            /* Adds padding below the text to give space before the line */
            display: block;
            /* Ensures it takes the full width for centering */
            width: 100%;
            /* Full width to make sure it's centered */
        }
    </style>
</head>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let currentLocation = window.location.href;
        let menuItems = document.querySelectorAll(".nav-item .nav-link");

        menuItems.forEach(item => {
            if (item.href === currentLocation) {
                item.classList.add("active");
            }
        });

        // Time update function
        function updateTime() {
            const currentTimeElement = document.getElementById("current-time");
            const now = new Date();

            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');

            // Convert to 12-hour format and add AM/PM
            const period = hours >= 12 ? "PM" : "AM";
            hours = hours % 12 || 12; // Convert to 12-hour format, handle 12:00 PM as 12

            // Format the time string
            const timeString = `${hours}:${minutes} ${period}`;
            currentTimeElement.textContent = timeString;
        }

        setInterval(updateTime, 60000); // Update every minute
        updateTime(); // Initial call to set the time immediately when the page loads
    });
</script>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="text-white p-3 vh-100" style="width: 250px; background-color: transparent;">
            <!-- Add LABGUARD at the top of the sidebar -->
            <div class="sidebar-header text-center mb-4">
                <h3 class="text-white">LABGUARD</h3> <!-- Add LABGUARD -->
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="faculty_dashboard.php" class="nav-link text-white">Classrooms</a></li>
                <li class="nav-item"><a href="view_students.php" class="nav-link text-white">Students Profile</a></li>
                <li class="nav-item"><a href="view_professors.php" class="nav-link text-white">Professors Profile</a></li>
                <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white">Schedule Management</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid p-4">
            <h2 class="faculty-panel-title">Faculty Panel</h2>
            <div class="welcome-message">
                <h6 class="welcomemsg">Welcome, Faculty Name!</h6>
            </div>

            <table class="table table-bordered">
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

    <!-- Current Time Display -->
    <div id="current-time"></div>
</body>

</html>