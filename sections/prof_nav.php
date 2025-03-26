<link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/prof.css">
<nav class="text-white p-3 vh-100" style="position: fixed; left: 3.7rem; top: calc(110px + 2rem); width: 350px; height: 100vh; overflow-y: auto;">
  <ul class="nav flex-column" style="width:100%;">
    <li class="nav-item"><a href="prof_dashboard.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_dashboard.php' ? 'active' : '' ?>">Classrooms</a></li>
    <li class="nav-item"><a href="prof_students.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_students.php' ? 'active' : '' ?>">Students Profile</a></li>
    <li class="nav-item"><a href="prof_schedule.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_schedule.php' ? 'active' : '' ?>">My Schedule</a></li>
    <li class="nav-item"><a href="prof_attendance.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_attendance.php' ? 'active' : '' ?>">Attendance</a></li>
    <li class="nav-item"><a href="prof_profile.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_profile.php' ? 'active' : '' ?>">My Profile</a></li>
    <li class="nav-item"><a href="logout.php" class="nav-link text-white" style="margin-top: calc(175px + 6rem); overflow-y: auto;">Logout</a></li>
  </ul>
</nav>