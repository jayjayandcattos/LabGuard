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
                        header("Location: backend/admin_dashboard.php");
                    } elseif ($role == "faculty") {
                        header("Location: backend/faculty_overview.php");
                    } elseif ($role == "professor") {
                        header("Location: backend/prof_dashboard.php");
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
                        header("Location: backend/admin_dashboard.php");
                    } elseif ($role == "faculty") {
                        header("Location: backend/faculty_overview.php");
                    } elseif ($role == "professor") {
                        header("Location: backend/prof_dashboard.php");
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LABGUARD</title>
    <link rel="stylesheet" href="your-existing-stylesheet.css">
    <style>
        .modal-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-container.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(3px);
            transition: opacity 0.3s ease;
        }

        .modal-content {
            background: white;
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: translateY(20px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            opacity: 0;
        }

        .modal-container.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            background: linear-gradient(45deg, #1a2a6c, #3f4b7f, #7287e5, #7991fc, #3f4b7f, #1a2a6c);
            color: white;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .close-button {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            transition: background-color 0.2s, color 0.2s;
        }

        .close-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .close-button svg {
            width: 20px;
            height: 20px;
        }

        .modal-body {
            padding: 24px;
        }

        .error-content {
            display: flex;
            align-items: flex-start;
        }

        .error-icon {
            flex-shrink: 0;
        }

        .error-icon svg {
            width: 40px;
            height: 40px;
            color: #e53e3e;
        }

        .error-message {
            margin-left: 16px;
            color: #4a5568;
            font-size: 16px;
            line-height: 1.5;
        }

        .modal-footer {
            background-color: #f9fafb;
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid #e2e8f0;
        }

        .close-modal-btn {
            padding: 10px 20px;
            background: linear-gradient(45deg, #1a2a6c, #3f4b7f);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .close-modal-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .close-modal-btn:active {
            transform: translateY(0);
        }
    </style>
</head>

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
                    <form action="index.php" method="POST">
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
                    <p class="short-description" style="text-align: center;">A secure and efficient platform for managing laboratory resources.</p>
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
                    <!-- TEST -->
                    <!-- Right Section -->
                    <div class="right-section">
                        <h2 class="smaller-h2">FEATURES</h2>
                        <p class="lorem-ipsum">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

                        <h2 class="smaller-h2">INSTRUCTIONS</h2>
                        <p class="lorem-ipsum">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

                        <h2 class="smaller-h2">FAQs</h2>
                        <p class="lorem-ipsum">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

                        <h2 class="smaller-h2">DEVELOPERS</h2>
                        <p class="lorem-ipsum">
                            <strong>Back-End Developers:</strong> Samantha Jumuad & JhonRay Lorẽo<br>
                            <strong>Front-End Developers:</strong> Justin Rivera, Krish Detalla, & Yzabella Golfo<br>
                            <strong>UI Designer:</strong> Heila Longaquit<br>
                            <strong>Researchers:</strong> Axel Lomigo & Shanalyn Lanza
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal-container">
        <div class="modal-overlay" onclick="closeErrorModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Login Error</h3>
                <button type="button" class="close-button" onclick="closeErrorModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="error-content">
                    <div class="error-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="error-message" id="errorMessage">
                        <?php echo isset($error) ? $error : ""; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="close-modal-btn" onclick="closeErrorModal()">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div class="termsmodal" id="termsModal">
        <div class="scrolldown" onclick="scrollToTermsButtons()">
            <div class="termsmodal-content">

                <span class="close" onclick="closeTermsModal()">&times;</span>
                <div class="container">
                    <div class="top-section">
                        <h2>TERMS AND CONDITIONS</h2>

                        <h3>1. AGREEMENT TO TERMS</h3>
                        <p>The Terms of Service of the LABGUARD™ establishes a legally binding agreement between parties involved in the usage of the LABGUARD™ Attendance Monitoring System whether personally ("you") or done behalf of a party's knowledge including LABGUARD™ ("Company", "we", "us", and "our") whether through web-application, or physical usage of the system. You agree that by using, operating, and/or utilizing the LABGUARD™ you adhere to the written Terms of Service, you have read, understood, and agree upon the usage of our system. If you do not agree with the terms written within the Terms of Service you are effectively prohibited and are obligated to cease the use or your participation with the system by contacting proper authorities within your academic institution.</p>

                        <!-- Terms content omitted for brevity -->
                        <!-- Terms sections 2-15 would go here -->

                        <section id="terms-buttons-section" class="terms-buttons">
                            <button class="accept-button" onclick="acceptTerms()">Accept</button>
                            <!-- <button class="decline-button" onclick="declineTerms()">Decline</button> -->
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>© 2025 LABGUARD. All rights reserved.</p>
            <div class="overflow-hidden w-full md:w-auto">
                <p onclick="openTermsModal()">
                    Using this platform means you agree to all Terms and our Privacy Policy. Click here to learn more.
                </p>
            </div>
        </div>
    </footer>   

    <script src="js/about.js"></script>
    <script src="js/login.js"></script>
    <script src="js/cardhover.js"></script>
    <script src="js/termsandconditions.js"></script>
    <script>
        // Error Modal Functions
        function openErrorModal(message) {
            if (message) {
                document.getElementById('errorMessage').textContent = message;
            }
            document.getElementById('errorModal').classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeErrorModal();
            }
        });

        // Check for PHP errors and show modal
        <?php if(isset($error)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openErrorModal("<?php echo addslashes($error); ?>");
        });
        <?php endif; ?>
    </script>
</body>
</html>