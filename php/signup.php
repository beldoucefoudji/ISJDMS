<?php
// 1. Affichage forcé des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Inclusion de la base de données
if (!file_exists("database.php")) {
    die("Fichier database.php introuvable !");
}
include("database.php");

// 3. Chargement de PHPMailer (Chemin relatif direct)
// On teste si les fichiers existent individuellement
if (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
    require __DIR__ . '/PHPMailer/Exception.php';
    require __DIR__ . '/PHPMailer/PHPMailer.php';
    require __DIR__ . '/PHPMailer/SMTP.php';
} else {
    // Si ce n'est pas PHPMailer (Majuscules), on teste phpmailer (minuscules)
    if (file_exists(__DIR__ . '/phpmailer/PHPMailer.php')) {
        require __DIR__ . '/phpmailer/Exception.php';
        require __DIR__ . '/phpmailer/PHPMailer.php';
        require __DIR__ . '/phpmailer/SMTP.php';
    } else {
        die("Erreur : Le dossier PHPMailer est introuvable. Vérifiez le nom sur GitHub.");
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    } elseif ($_GET['error'] == 'invalid_code') { 
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
    
    // --- UPDATED SECRET CODE CHECKS ---
    $teacher_auth_code = $_POST['teacher_auth_code'] ?? '';
    $OFFICIAL_TEACHER_CODE = "IUSJC2026"; 
    $OFFICIAL_STAFF_CODE   = "STAFF_ISJ_2026"; 

    if ($role === 'teacher' && $teacher_auth_code !== $OFFICIAL_TEACHER_CODE) {
        header("Location: signup.php?error=invalid_code");
        exit();
    }

    if ($role === 'staff' && $teacher_auth_code !== $OFFICIAL_STAFF_CODE) {
        header("Location: signup.php?error=invalid_code");
        exit();
    }

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

    // Ensure status 'Pending' fits in the column (We fixed this with ALTER TABLE earlier)
    $sql = $conn->prepare("INSERT INTO registration (fullname, email, phone_number, role, username, password, status, otp_code) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");
    $sql->bind_param("sssssss", $fullname, $email, $phone_number, $role, $username, $hashed_password, $otp);

    if ($sql->execute()) {
        $_SESSION['pending_email'] = $email;
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2;

        try {
            // Server settings
            
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_EMAIL');
            $mail->Password   = getenv('SMTP_PASS'); 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients - Using getenv for the 'from' address is safer
            $mail->setFrom(getenv('SMTP_EMAIL'), 'ISJ Docs System');
            $mail->addAddress($email, $fullname);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Identity - ISJ Docs';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;'>
                    <h2 style='color: #061428; text-align: center;'>Welcome to ISJ Docs</h2>
                    <p>Hello <strong>$fullname</strong>,</p>
                    <p>Your verification code is: <strong style='color: #D4AF37; font-size: 20px;'>$otp</strong></p>
                    <p>Role Registered: <strong>" . ucfirst($role) . "</strong></p>
                    <p>If you did not request this, please ignore this email.</p>
                </div>";

            $mail->send();
            
            // Success! Go to OTP page
            header("Location: verify_otp.php");
            exit();

        } catch (Exception $e) {
            // Log the error internally (Optional: error_log($mail->ErrorInfo);)
            header("Location: signup.php?error=mail_fail");
            exit();
        }
    } else {
        header("Location: signup.php?error=db_fail");
        exit();
    }
}
?>