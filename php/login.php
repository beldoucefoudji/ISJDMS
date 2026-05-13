<?php
session_start();
include("database.php"); 

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Updated query to include all necessary fields
    $sql = "SELECT id, fullname, email, phone_number, username, password, role, status, otp_code FROM registration WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            
            // 1. Check if account is Disabled
            if ($user['status'] === 'disabled') { 
                $error_msg = "Your account is currently disabled. Please contact the Admin.";
            } 
            // 2. Check for pending OTP (For Admin-created accounts)
            elseif (!empty($user['otp_code'])) {
                $_SESSION['pending_email'] = $user['email'];
                $otp = $user['otp_code']; 
                $fullname = $user['fullname'];

                require 'PHPMailer/Exception.php';
                require 'PHPMailer/PHPMailer.php';
                require 'PHPMailer/SMTP.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'elmine0520@gmail.com'; 
                    $mail->Password   = 'vmqd vkuc aqer rrns'; 
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('elmine0520@gmail.com', 'ISJ Docs System');
                    $mail->addAddress($user['email'], $fullname);

                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Admin-Created Account';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee;'>
                            <h2 style='color: #061428;'>Welcome to ISJ Docs</h2>
                            <p>Hello <strong>$fullname</strong>,</p>
                            <p>Your account has been created by the Admin. Please use the code below to log in:</p>
                            <div style='background: #f4f4f4; padding: 15px; font-size: 24px; font-weight: bold; color: #000; text-align: center;'>
                                $otp
                            </div>
                        </div>";

                    $mail->send();
                    header("Location: verify_otp.php");
                    exit();

                } catch (Exception $e) {
                    $error_msg = "Mail error: Code could not be sent.";
                }
            }
            // 3. Admin Login -> Redirect to 2FA
            elseif ($user['role'] === 'admin') {
                $_SESSION['temp_admin_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];      
                header("Location: admin_verify_2fa.php");
                exit();
            }
            // 4. Standard User Login
            else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['phone_number'] = $user['phone_number']; 
                $_SESSION['role'] = $user['role'];

                header("Location: userdashboard.php");
                exit();
            }
        } else {
            $error_msg = "Incorrect password. Please try again.";
        }
    } else {
        $error_msg = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ISJ Docs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        .password-field-container { position: relative; width: 100%; margin-bottom: 15px; }
        .password-field-container input { width: 100%; padding-right: 40px; box-sizing: border-box; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; }
    </style>
</head>
<body>
    <div class="container">      
        <div class="left-panel">
            <div class="brand"> 
                <a href="welcome.php" class="back-link">Back to Welcome</a>
            </div>
            <div class="illustration">
                <img src="../images/applogo.png" class="main-logo" alt="ISJ Logo">
            </div>
            <p class="description">Secure Document Management System.</p>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <h2>Log in</h2>
                <?php if($error_msg): ?>
                    <p style="color: #e74c3c; font-size: 0.9rem; margin-bottom: 15px; text-align: center; font-weight: bold;">
                        <?php echo $error_msg; ?>
                    </p>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <input type="text" name="username" placeholder="Username" required>
                    <div class="password-field-container">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                    <button type="submit" class="login-btn">Log in</button>
                </form>
                
                <a href="password_control/forgot_password.php" class="forgot-pass">Forgotten password?</a>
                <p class="signup-link">Need an account? <a href="signup.php">Sign up</a></p>
            </div>
        </div>
    </div>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#password');
        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
    <script src="../js/responsive.js"></script>
</body>
</html>
