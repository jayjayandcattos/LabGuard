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
            <img src="assets/IDTap.svg" alt="Logo" class="logo">
            <h1 class="brand-name">LABGUARD</h1>
        </div>
        <div class="rectangle">
            <div class="nav-options">
                <div class="nav-option" onclick="window.location.reload();">HOME</div>
                    <div class="nav-option" id="aboutBtn" onclick="openAboutModal()">ABOUT</div>
                <div class="nav-option" id="loginBtn">LOGIN</div>
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
    <div class="loginmodal" id="loginModal">
        <div class="modal-overlay"></div>
        <div class="loginmodal-content">
            <div class="container">
                <div class="image-section">
                    <img src="assets/Card.png" alt="Card Image" class="primary-image" id="primaryImage">
                    <img src="assets/Card2.png" alt="Card Image Hover" class="hover-image" id="hoverImage">
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
                        <button class="type=" submit" id="hoverButton">LOGIN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- About Modal -->
    <div class="aboutmodal" id="aboutModal">
        <div class="modal-overlay"></div>
        <div class="aboutmodal-content">
            <span class="close" onclick="closeAboutModal()">&times;</span>
            <div class="container">
                <!-- Top Section -->
                <div class="top-section">
                    <h2>LABGUARD</h2>
                    <div class="white-line"></div>
                    <p class="short-description">A secure and efficient platform for managing laboratory resources.</p>
                </div>
                <!-- Split Section -->
                <div class="split-section">
                    <!-- Left Section -->
                    <div class="left-section">
                        <div class="image-container">
                            <img src="assets/About1.svg" alt="Card Image" class="primary-image">
                            <img src="assets/About2.svg" alt="Card Image Hover" class="hover-image">
                        </div>
                    </div>
                    <!-- Right Section -->s
                    <div class="right-section">
                        <h2 class="smaller-h2">FEATURES</h2>
                        <p class="lorem-ipsum">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

                        <h2 class="smaller-h2">INSTRUCTIONS</h2>
                        <p class="lorem-ipsum">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

                        <h2 class="smaller-h2">FAQs</h2>
                        <p class="lorem-ipsum">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

                        <h2 class="smaller-h2">DEVELOPERS</h2>
                        <p class="lorem-ipsum">jeAr & saM - backend HEILAAAA - ui jayzeEe, yza, h3alEr - frontend</p>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="js/about.js"></script>
    <script src="js/login.js"></script>
    <script src="js/cardhover.js"></script>


</body>

</html>