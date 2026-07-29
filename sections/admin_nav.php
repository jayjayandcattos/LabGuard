<link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/prof.css">

<nav class="text-white p-3 vh-100" style="position: fixed; left: 3.7rem; top: calc(110px + 2rem); width: 350px; height: 100vh; overflow-y: auto;">
  <ul class="nav flex-column" style="width:100%;">
  <li class="nav-item">
  <a href="admin_dashboard.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>">Classroom</a>
    </li>
    <li class="nav-item">
        <a href="schedule.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : '' ?>">Schedule</a>
    </li>
    <li class="nav-item">
        <a href="professors.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'professors.php' ? 'active' : '' ?>">Professors</a>
    </li>
    <li class="nav-item">
        <a href="faculty.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'faculty.php' ? 'active' : '' ?>">Lab Technician</a>
    </li>
    <li class="nav-item">
        <a href="students.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>">Students</a>
    </li>
    <li class="nav-item">
        <a href="student_subs.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'student_subs.php' ? 'active' : '' ?>">Student Subjects</a>
    </li>
    <li class="nav-item">
        <a href="student_secs.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'student_secs.php' ? 'active' : '' ?>">Student Sections</a>
    </li>
    <li class="nav-item">
        <a href="admin.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>">Admin</a>
    </li>
    <li class="nav-item">
        <a href="admin_profile.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'admin_profile.php' ? 'active' : '' ?>">My Profile</a>
    </li>
    <li class="nav-item">
        <a href="login_logs.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'login_logs.php' ? 'active' : '' ?>">Login Logs</a>
    </li>
    <li class="nav-item">
        <a href="logout.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : '' ?>">Logout</a>
        </li>
  </ul>
</nav>
