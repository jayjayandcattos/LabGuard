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
                        <p class="lorem-ipsum"> It features tailored modules for Students, Faculty, and Professors, with RFID integration for fast access and tracking. A central authentication layer secures all access, while the modular design separates roles, admin tasks, and reporting.
                        </p>

                        <h2 class="smaller-h2">INSTRUCTIONS</h2>
                        <p class="lorem-ipsum">Scan your RFID to check in or out. Make sure a professor is present before scanning.</p>

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
    <div id="errorModal" class="custom-modal-container">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h3 class="custom-modal-title">Login Error</h3>
            </div>
            <div class="custom-modal-body">
                <div class="custom-error-content">
                    <div class="custom-error-message" id="errorMessage">
                        <?php echo isset($error) ? $error : ""; ?>
                    </div>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="custom-close-modal-btn" onclick="closeErrorModal()">
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
                        <p>The Terms of Service of the LABGUARD™ establishes a legally binding agreement between parties involved in the usage of the LABGUARD™ Attendance Monitoring System whether personally (“you”) or done behalf of a party’s knowledge including LABGUARD™ (“Company”, “we”, “us”, and “our”) whether through web-application, or physical usage of the system. You agree that by using, operating, and/or utilizing the LABGUARD™ you adhere to the written Terms of Service, you have read, understood, and agree upon the usage of our system. If you do not agree with the terms written within the Terms of Service you are effectively prohibited and are obligated to cease the use or your participation with the system by contacting proper authorities within your academic institution.</p>

                        <h3>2. INTELLECTUAL PROPERTY RIGHTS</h3>
                        <p>Republic Act No. 8293, as amended, is a critical legal instrument for fostering innovation, protecting creators, and aligning Philippine intellectual property law with global standards.</p>

                        <h3>3. USER PRESENTATION</h3>
                        <p>By using LABGUARD, you acknowledge and agree that:

                            (1) all registration information you provide is accurate, complete, and up to date; (2) you will promptly update this information to maintain its accuracy; (3) you have the legal capacity to accept and comply with these Terms of Use; (4) you are not a minor in your jurisdiction; (5) you will not access Lab Guard through automated methods, such as bots or scripts; (6) you will not use Lab Guard for any unlawful or unauthorized activities; and (7) your use of Lab Guard will comply with all applicable laws and regulations.

                            If any information you provide is found to be incorrect, incomplete, or outdated, we reserve the right to suspend or terminate your account and deny future access to the system.
                        </p>

                        <h3>4. USER REGISTRATION</h3>
                        <p>LABGUARD™ revolves around an academic institution that enables professors, faculty, and admin to efficiently record classroom laboratory activities on a daily basis. As such, institutions are given authority to register, list, and include participation on the behalf of students and faculty members. Despite registration with prior notice, participants and users are encouraged and expected to adhere to rules written within the Terms of Service for LABGUARD™.</p>

                        <h3>5. PROHIBITED ACTIVITIES</h3>
                        <p>You are strictly prohibited from engaging in any activity that compromises the integrity, security, or functionality of the LABGUARD™ system. This includes, but is not limited to: unauthorized access or use of accounts; tampering with, bypassing, or attempting to interfere with the system’s operations; inputting false data; distributing malware or harmful code; engaging in any form of hacking or phishing; or using the system for unlawful, harmful, or deceptive purposes. Any malicious behavior or violation of these rules may result in immediate termination of access, reporting to appropriate authorities, and potential legal action.</p>

                        <h3>6. SITE MANAGEMENT</h3>
                        <p>The LABGUARD™ team, along with authorized stakeholders, reserves the right to monitor the system for any violations of these Terms of Service. We are authorized to investigate, restrict, or remove any activity or content that may pose a threat to system integrity, violate policies, or breach applicable laws. When necessary, appropriate disciplinary measures or legal action may be taken against offenders, including suspension of access, account termination, or referral to institutional or legal authorities. This is to ensure the safety, reliability, and proper use of the LABGUARD™ system at all times.</p>

                        <h3>7. DATA PRIVACY POLICY</h3>
                        <p>R.A. No. 10173, the Data Privacy Act of 2012, is a comprehensive legislative measure aimed at protecting individuals' personal data from misuse while ensuring that the free flow of information is not unduly restricted. The law’s extensive provisions on data subject rights, data controller and processor obligations, security measures, and breach notification reflect the country’s commitment to protecting privacy in the digital age. Compliance with this law is vital for both public and private entities that handle personal information, and the enforcement powers granted to the National Privacy Commission ensure that individuals’ rights are adequately protected.</p>

                        <h3>8. TERM AND TERMINATION</h3>
                        <p>These Terms of Service shall remain in full effect while you use the LABGUARD™ system. Without limiting any other provision of these Terms, we reserve the right, at our sole discretion and without notice or liability, to deny access to and use of the system (including restricting or blocking user accounts) to any individual for any reason or no reason at all. This includes, but is not limited to, violations of any term, condition, or policy outlined in these Terms of Service or any applicable law or institutional regulation. We may terminate your access or delete your account and any associated data at any time, without prior warning.

                            If your account is terminated or suspended, you are prohibited from registering and creating a new account under your name, a false identity, or on behalf of another party. In addition to termination or suspension, we reserve the right to take appropriate legal action, including but not limited to civil, criminal, or injunctive remedies.
                        </p>

                        <h3>9. MODIFICATION AND INTERRUPTIONS</h3>
                        <p>Developers and specific user-type (Faculties and System Administrators), can modify the website anytime the system requires to do so. However, these changes might cause some minor interruption on the website's presentation. Users other than those who have modified the system might bare some inconveniencies due to these changes:

                            Integration of New Functionalities:
                            Over time some functionalities will be needed in order to assist and enhance existing features of the system. This could cause some interruption on the website's behavior.


                            Compatibility Updates:
                            Updates that are made in order to ensure the system's connectivity with hardware, and software specifications. These updates can affect the website's performance for a period of time.


                            Bug fixes (Corrective Updates):
                            To resolve specific bugs and errors, developers have to update parts of the website, which may cause some unexpected negative behavior of the website.
                        </p>

                        <h3>10. BINDING ARBITRATION</h3>
                        <p>In the event of any dispute, claim, or controversy arising out of or relating to the use of the LABGUARD™ system or these Terms of Service, the parties agree to resolve the matter through binding arbitration. This process will take place in accordance with applicable laws of the Republic of the Philippines and be conducted in Metro Manila. Both parties waive the right to bring claims before a court, except where such waiver is not permitted by law. Arbitration shall be the exclusive means of resolving any such disputes.</p>

                        <h3>11. RESTRICTION</h3>
                        <p>All disputes arising from the use of the LABGUARD™ system shall be settled exclusively through closed council arbitration. By agreeing to these Terms, users waive their right to a trial by court or jury. Instead, all proceedings will be conducted privately, without public disclosure, in a confidential setting. This ensures a faster, more efficient resolution process while maintaining the privacy and integrity of all parties involved.</p>

                        <h3>12. CORRECTIONS</h3>
                        <p>The LABGUARD™ system may contain typographical errors, inaccuracies, or omissions, including but not limited to data entries, descriptions, or technical information. While we strive to ensure accuracy, we do not warrant that all content is complete, reliable, or up-to-date at all times. We reserve the right to correct any errors, inaccuracies, or omissions and to update system content without prior notice. Users are encouraged to report discrepancies to ensure the system remains accurate and reliable.</p>

                        <h3>13. DISCLAIMER</h3>
                        <p>The LABGUARD System is provided to facilitate accurate and efficient tracking of student and professor attendance through ID card tap-in functionality. While the system is designed to maintain reliable records, we do not guarantee the absolute accuracy or availability of data at all times. Users are responsible for verifying that their attendance has been properly logged. The administrators are not liable for any loss, error, or consequence resulting from system misuse, outages, or technical malfunctions. Unauthorized access, tampering, or misuse of the system is prohibited and may lead to disciplinary or legal action.</p>

                        <h3>14. MISCELLANEOUS</h3>
                        <p>This system is intended solely for academic and administrative use within Quezon City University. By using the LABGUARD System, users agree to abide by all relevant institutional policies and applicable laws.
                            The developers reserve the right to modify, suspend, or discontinue any part of the system at any time without prior notice. We also reserve the right to update these Terms and Conditions as needed. Continued use of the system after such changes constitutes acceptance of the new terms.
                            In no event shall the institution, its faculty, developers, or administrators be held liable for any indirect, incidental, special, or consequential damages arising out of or in connection with the use or misuse of the system, including but not limited to, loss of data, missed attendance logs, or disciplinary outcomes based on system records.
                            Any legal disputes arising from the use of this system shall be governed by the laws and regulations of the Republic of the Philippines and any proceedings shall be held within the proper courts of Metro Manila.
                            If any part of these Terms and Conditions is deemed invalid or unenforceable, the remaining sections shall remain in full force and effect.</p>

                        <h3>15. CONTACT US</h3>
                        <p>We value your feedback and are here to assist you with any concerns regarding the LABGUARD System. Whether you're experiencing technical issues, noticing errors in your attendance records, or have general questions about system usage, please don't hesitate to get in touch with us.<br><br>
                            Email: labguardqcu@gmail.com — Send us a detailed message regarding your concern. We aim to respond within 1–2 business days.<br>
                            Phone: 09xxxxxxxxx — For urgent issues, you may call us during office hours.<br>
                            Office Location: TBA<br>
                            Office Hours: TBA<br><br>
                            We are committed to ensuring the system works smoothly and effectively for all users. Please provide relevant details (e.g., date/time of issue, ID number, screenshots if applicable) when reporting a problem to help us resolve it faster.</p>



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


    <script src="js/about.js"></script>
    <script src="js/login.js"></script>
    <script src="js/cardhover.js"></script>
    <script src="js/termsandconditions.js"></script>
    <script>
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


        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeErrorModal();
            }
        });


        <?php if (isset($error)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openErrorModal("<?php echo addslashes($error); ?>");
            });
        <?php endif; ?>
    </script>


</body>

</html>