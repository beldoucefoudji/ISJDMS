<?php
session_start();
include("database.php");

// 1. Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$error_display = "";

// Check for URL error parameters to display messages
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'taken') {
        $error_display = "Email or Username is already in use.";
    } elseif ($_GET['error'] == 'mail_fail') {
        $error_display = "Account created but failed to send verification email.";
    } elseif ($_GET['error'] == 'db_fail') {
        $error_display = "Registration failed. Please try again later.";
    } elseif ($_GET['error'] == 'mismatch') {
        $error_display = "Passwords do not match!";
    } elseif ($_GET['error'] == 'invalid_code') { // Removed the extra } here
        $error_display = "Invalid Authorization Code!";
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname     = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $role         = mysqli_real_escape_string($conn, $_POST['role']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $password     = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // --- TEACHER SECRET CODE CHECK ---
   // --- UPDATED SECRET CODE CHECKS ---
$teacher_auth_code = $_POST['teacher_auth_code'] ?? '';
$OFFICIAL_TEACHER_CODE = "IUSJC2026"; 
$OFFICIAL_STAFF_CODE   = "STAFF_ISJ_2026"; // Choose your Staff-specific code

// Check Teacher Code
if ($role === 'teacher' && $teacher_auth_code !== $OFFICIAL_TEACHER_CODE) {
    header("Location: signup.php?error=invalid_code");
    exit();
}

// NEW: Check Staff Code
if ($role === 'staff' && $teacher_auth_code !== $OFFICIAL_STAFF_CODE) {
    header("Location: signup.php?error=invalid_code");
    exit();
}
    // ---------------------------------

    if ($password !== $confirm_password) {
        header("Location: signup.php?error=mismatch");
        exit();
    }

    $otp = rand(100000, 999999);

    $checkUser = $conn->prepare("SELECT id FROM registration WHERE email = ? OR username = ?");
    $checkUser->bind_param("ss", $email, $username);
    $checkUser->execute();
    
    if ($checkUser->get_result()->num_rows > 0) {
        header("Location: signup.php?error=taken");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = $conn->prepare("INSERT INTO registration (fullname, email, phone_number, role, username, password, status, otp_code) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");
    $sql->bind_param("sssssss", $fullname, $email, $phone_number, $role, $username, $hashed_password, $otp);

    if ($sql->execute()) {
        $_SESSION['pending_email'] = $email;
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username = getenv('SMTP_EMAIL');
            $mail->Password = getenv('SMTP_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;


            $mail->setFrom('elmine0520@gmail.com', 'ISJ Docs System');
            $mail->addAddress($email, $fullname);

            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Identity - ISJ Docs';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;'>
                    <h2 style='color: #061428; text-align: center;'>Welcome to ISJ Docs</h2>
                    <p>Hello <strong>$fullname</strong>,</p>
                    <p>Your verification code is: <strong style='color: #D4AF37;'>$otp</strong></p>
                    <p>Role Registered: <strong>" . ucfirst($role) . "</strong></p>
                </div>";

            if($mail->send()){
                header("Location: verify_otp.php");
                exit();
            }

        } catch (Exception $e) {
            header("Location: signup.php?error=mail_fail");
            exit();
        }
    } else {
        header("Location: signup.php?error=db_fail");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ Docs — Create Account</title>
    <link rel="stylesheet" href="../css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        .password-container { position: relative; width: 100%; }
        .password-container input { width: 100%; padding-right: 40px; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; }
        .back-link-container { position: absolute; top: 20px; left: 20px; }
        .back-link { color: #ffffff; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; transition: color 0.3s ease; }
        .back-link i { margin-right: 8px; }
        .back-link:hover { color: #D4AF37; }
        body { position: relative; min-height: 100vh; }
        .error-banner { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 0.9rem; border: 1px solid #f5c6cb; }
        
        /* Teacher Code Styling */
        #teacherCodeGroup { 
            background: #eef2f7; 
            padding: 10px; 
            border-radius: 8px; 
            border-left: 4px solid #061428; 
            margin-bottom: 15px; 
        }
    </style>
</head>
<body>
    <div class="back-link-container"> 
        <a href="../php/welcome.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Welcome Page
        </a>
    </div>
    <div class="signup-container">
        <div class="form-header">
            <h2>Join ISJ Docs</h2>
            <p>Create your account and verify your email.</p>
        </div>

        <?php if ($error_display): ?>
            <div class="error-banner"><?php echo $error_display; ?></div>
        <?php endif; ?>

        <form action="signup.php" method="POST" id="signupForm">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="Your name" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" placeholder="+237 600 000 000">
                </div>

                <div class="form-group">
                    <label>User Role</label>
                    <select name="role" id="roleSelect" onchange="toggleTeacherCode()" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="staff">Staff Member</option>
                    </select>
                </div>

             <div class="form-group" id="teacherCodeGroup" style="display: none;">
                <label id="authCodeLabel" style="color: #061428; font-weight: bold;">Authorization Code</label>
                 <div class="password-container">
                  <input type="password" name="teacher_auth_code" id="teacher_auth_code" placeholder="Enter Authorization Code">
                  <i class="fas fa-eye toggle-password" id="toggleTeacherCode"></i>
                 </div>
                  <small id="authCodeHelp" style="color: #666;">This code is required for your account type.</small>
                 </div>
                <!-- </div> -->

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Choose a username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-container">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required>
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                    </div>
                    <small id="error-text" style="color: #e74c3c; visibility: hidden; font-weight: bold;">Passwords do not match!</small>
                </div>
            </div>

            <button type="submit" class="signup-btn" id="submitBtn">Create Account</button>
        </form>

        <div class="form-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        // Logic to show/hide the Teacher Code field
        
       const roleSelect = document.querySelector('#roleSelect');
const teacherGroup = document.querySelector('#teacherCodeGroup');
const teacherInput = document.querySelector('#teacher_auth_code');
const authLabel = document.querySelector('#authCodeLabel'); // Added this

function toggleTeacherCode() {
    const selectedRole = roleSelect.value;
    
    if (selectedRole === 'teacher' || selectedRole === 'staff') {
        teacherGroup.style.display = 'block';
        teacherInput.required = true;
        
        // Dynamic labels to guide the user
        if (selectedRole === 'teacher') {
            authLabel.innerText = "Teacher Authorization Code";
            teacherInput.placeholder = "Enter IUSJC Teacher Code";
        } else {
            authLabel.innerText = "Staff Authorization Code";
            teacherInput.placeholder = "Enter ISJ Staff Code";
        }
        
    } else {
        teacherGroup.style.display = 'none';
        teacherInput.required = false;
        teacherInput.value = ''; 
    }
}

        // Password visibility and real-time validation
        const togglePassword = document.querySelector('#togglePassword');
        const toggleConfirm = document.querySelector('#toggleConfirmPassword');
        const passwordField = document.querySelector('#password');
        const confirmField = document.querySelector('#confirm_password');
        const errorText = document.querySelector('#error-text');
        const submitBtn = document.querySelector('#submitBtn');

        togglePassword.addEventListener('click', () => {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            togglePassword.classList.toggle('fa-eye-slash');
        });

        toggleConfirm.addEventListener('click', () => {
            const type = confirmField.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmField.setAttribute('type', type);
            toggleConfirm.classList.toggle('fa-eye-slash');
        });
         
        // --- ADD THIS BLOCK HERE ---
          const toggleTeacher = document.querySelector('#toggleTeacherCode');

     toggleTeacher.addEventListener('click', function () {
    const type = teacherInput.getAttribute('type') === 'password' ? 'text' : 'password';
    teacherInput.setAttribute('type', type);
    this.classList.toggle('fa-eye-slash');
});
// ----------------------------

        function validate() {
            if (confirmField.value.length > 0 && passwordField.value !== confirmField.value) {
                errorText.style.visibility = 'visible';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
            } else {
                errorText.style.visibility = 'hidden';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        }

        passwordField.addEventListener('input', validate);
        confirmField.addEventListener('input', validate);
    </script>
    <script src="../js/responsive.js"></script>
</body>
</html>