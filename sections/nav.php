<?php 
require_once "backend/db.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role']; 

    if (!empty($email) && !empty($password) && !empty($role)) {

        $table = "";
        $id_field = "";
        switch ($role) {
            case "admin":
                $table = "admin_tbl";
                $id_field = "admin_id";
                break;
            case "faculty":
                $table = "faculty_tbl";
                $id_field = "employee_id";
                break;
            case "professor":
                $table = "prof_tbl";
                $id_field = "prof_user_id";
                break;
            default:
                $error = "Invalid role selected!";
        }

        if ($table !== "") {
  
            $stmt = $conn->prepare("SELECT $id_field AS user_id, firstname, lastname, password FROM $table WHERE email = ?");
            $stmt->execute([$email]); 
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if (password_verify($password, $row['password'])) {
        
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['name'] = $row['firstname'] . " " . $row['lastname'];
                    $_SESSION['role'] = $role;

     
                    if ($role == "admin") {
                        header("Location: admin_dashboard.php");
                    } elseif ($role == "faculty") {
                        header("Location: faculty_dashboard.php");
                    } elseif ($role == "professor") {
                        header("Location: prof_dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid password!";
                }
            } else {
                $error = "Invalid email!";
            }
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="logo-brand">
            <div class="back-button">
                <div class="circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </div>
            </div>
            <img src="assets/logo.png" alt="Logo" class="logo">
            <h1 class="brand-name">LABGUARD</h1>
        </div>
        <div class="rectangle">
            <div class="nav-options">
                <div class="nav-option">HOME</div>
                <div class="nav-option">ABOUT</div>
                <div class="nav-option" onclick="openModal()">LOGIN</div>
            </div>
        </div>
        <div class="time-container">
            <div class="text-wrapper">
                <div class="time-text" id="time">--:-- --</div>
                <div class="date-text" id="date">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal" id="loginModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="container">
            <div class="image-section">
                <img src="assets/Card.png" alt="Card Image">
            </div>
            <div class="form-section">
                <h2>USER LOGIN</h2>
                <form action="backend/login.php" method="POST">
                    <div class="form-group">
                        <label for="email">EMAIL:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">PASSWORD:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">SELECT ROLE:</label>
                        <select id="role" name="role" required>
                            <option value="">Select</option>
                            <option value="admin">Admin</option>
                            <option value="faculty">Faculty</option>
                            <option value="professor">Professor</option>
                        </select>
                    </div>
                    <button type="submit">LOGIN</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="js/login.js"></script>
    <script>
       function openModal() {
  const modal = document.getElementById('loginModal');
  modal.classList.add('active'); 
}

function closeModal() {
  const modal = document.getElementById('loginModal');
  modal.classList.remove('active'); 
}
    </script>
</body>
</html>