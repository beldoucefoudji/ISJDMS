<?php
session_start();
include("../database.php");

// Security check: must have come from forgot_password.php
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$msg = "";
$msg_type = "red";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_SESSION['reset_email'];
    $otp_input = mysqli_real_escape_string($conn, $_POST['otp']);
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $msg = "Passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $msg = "Password must be at least 6 characters.";
    } else {
        // 1. Verify if OTP matches for this email
        $check = $conn->prepare("SELECT id FROM registration WHERE email = ? AND otp_code = ?");
        $check->bind_param("ss", $email, $otp_input);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            // 2. Hash the new password
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // 3. Update PASSWORD and clear OTP only. Fullname is preserved.
            $update = $conn->prepare("UPDATE registration SET password = ?, otp_code = NULL WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);
            
            if ($update->execute()) {
                unset($_SESSION['reset_email']); // Clear session
                // Redirect to login (assuming login.php is in the parent folder)
                header("Location: ../login.php?msg=reset_success");
                exit();
            } else {
                $msg = "Error updating database.";
            }
        } else {
            $msg = "Invalid or expired recovery code.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | ISJ Docs</title>
    <link rel="stylesheet" href="../../css/reset_password.css">
    <link rel="stylesheet" href="../../css/responsive.css">
</head>
<body>
    <div class="login-card"> 
        <h2>New Password</h2>
        <p style="font-size: 0.9rem; color: #555; margin-bottom: 20px;">
            Enter the 6-digit code and your new password.
        </p>

        <?php if($msg): ?>
            <p style="color: <?php echo $msg_type; ?>; text-align: center; font-weight: bold;">
                <?php echo $msg; ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="otp" placeholder="6-digit code" maxlength="6" required 
                   style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc;">
            
            <input type="password" name="password" placeholder="New Password" required 
                   style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc;">
            
            <input type="password" name="confirm_password" placeholder="Confirm Password" required 
                   style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc;">
            
            <button type="submit" class="login-btn" 
                    style="width: 100%; padding: 12px; background: #000; color: #fff; border: none; cursor: pointer;">
                Update Password
            </button>
        </form>
    </div>
    <script src="../../js/responsive.js"></script>
</body>
</html>
