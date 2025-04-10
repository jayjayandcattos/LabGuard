<?php 
session_start();
require_once "db.php"; 

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
                        header("Location: faculty_overview.php");
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

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Unified Login</title>
</head>
<body>
  <!-- Login Modal -->
<div id="loginModal" class="modal">
  <div class="modal-content">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <h2>Login</h2>
    <form action="login.php" method="POST">
      <label>Email:</label>
      <input type="email" name="email" required>

      <label>Password:</label>
      <input type="password" name="password" required>

      <label>Select Role:</label>
      <select name="role" required>
        <option value="">-- Select Role --</option>
        <option value="admin">Admin</option>
        <option value="faculty">Faculty</option>
        <option value="professor">Professor</option>
      </select>

      <button type="submit">Login</button>
    </form>

    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
  </div>
</div>
</body>
</html>

<script>
  // Function to open the modal
  function openModal() {
    document.getElementById('loginModal').style.display = 'flex';
  }

  // Function to close the modal
  function closeModal() {
    document.getElementById('loginModal').style.display = 'none';
  }

  // Close the modal if the user clicks outside of it
  window.onclick = function(event) {
    const modal = document.getElementById('loginModal');
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  };
</script>