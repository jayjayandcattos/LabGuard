<nav class="bg-dark text-white p-3 vh-100" style="width: 250px;">
    <h4>Professor Panel</h4>
    <ul class="nav flex-column">
        <li class="nav-item"><a href="prof_dashboard.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_dashboard.php' ? 'active' : '' ?>">Classrooms</a></li>
        <li class="nav-item"><a href="prof_students.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_students.php' ? 'active' : '' ?>">Students Profile</a></li>
        <li class="nav-item"><a href="prof_schedule.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_schedule.php' ? 'active' : '' ?>">My Schedule</a></li>
        <li class="nav-item"><a href="prof_attendance.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_attendance.php' ? 'active' : '' ?>">Attendance</a></li>
        <li class="nav-item"><a href="prof_profile.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'prof_profile.php' ? 'active' : '' ?>">My Profile</a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link text-white">Logout</a></li>
    </ul>
</nav> 