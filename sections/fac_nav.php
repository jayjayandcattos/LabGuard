<link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/prof.css">
<nav class="text-white p-3 vh-100" style="position: fixed; left: 3.7rem; top: calc(110px + 2rem); width: 350px; height: 100vh; overflow-y: auto;">
  <ul class="nav flex-column" style="width:100%;">
    <li class="nav-item"><a href="faculty_overview.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'faculty_overview.php' ? 'active' : '' ?>">Overview</a></li>
    <li class="nav-item"><a href="faculty_dashboard.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'faculty_dashboard.php' ? 'active' : '' ?>">Classrooms</a></li>
    <li class="nav-item"><a href="view_students.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'view_students.php' ? 'active' : '' ?>">Student Profiles</a></li>
    <li class="nav-item"><a href="view_professors.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'view_professors.php' ? 'active' : '' ?>">Professor Profiles</a></li>
    <li class="nav-item"><a href="faculty_schedule.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'faculty_schedule.php' ? 'active' : '' ?>">Schedule Management</a></li>
    <li class="nav-item"><a href="logout.php" class="nav-link text-white" style="margin-top: calc(175px + 6rem); overflow-y: auto;">Logout</a></li>

  </ul>
</nav>