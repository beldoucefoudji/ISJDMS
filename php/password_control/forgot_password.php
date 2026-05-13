<?php
session_start();
include("../database.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $check = $conn->prepare("SELECT id, fullname FROM registration WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $otp = rand(100000, 999999);
        
        // Save OTP to database temporarily
        $update = $conn->prepare("UPDATE registration SET otp_code = ? WHERE email = ?");
        $update->bind_param("ss", $otp, $email);
        
        if ($update->execute()) {
            $_SESSION['reset_email'] = $email;
            
            // Send Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'elmine0520@gmail.com'; 
                $mail->Password = 'vmqd vkuc aqer rrns'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('elmine0520@gmail.com', 'ISJ Docs Recovery');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Code';
                $mail->Body = "<h3>Password Reset</h3><p>Your code is: <b>$otp</b></p>";
                
                $mail->send();
                header("Location: reset_password.php");
                exit();
            } catch (Exception $e) {
                $msg = "Error sending email.";
            }
        }
    } else {
        $msg = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | ISJ Docs</title>
    <link rel="stylesheet" href="../../css/forgot_password.css">
    <link rel="stylesheet" href="../../css/responsive.css">
</head>
<body>
    <div class="recovery-container">
        <h2>Reset Password</h2>
        <p>Enter your email to receive a recovery code.</p>
        <?php if($msg) echo "<p style='color:red;'>$msg</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required style="width:100%; margin-bottom:15px; padding:10px;">
            <button type="submit" class="login-btn">Send Code</button>
        </form>
        <p><a href="../../php/login.php">Back to Login</a></p>
    </div>
    <script src="../../js/responsive.js"></script>
</body>
</html>
