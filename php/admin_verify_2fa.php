<?php
session_start();
// Adjust path to your phpGangsta/GoogleAuthenticator.php file
require_once 'phpGangsta/GoogleAuthenticator.php';
include("database.php");

$ga = new PHPGangsta_GoogleAuthenticator();
$error = "";

// 1. SECURITY CHECK: If they didn't come from login.php, send them back
if (!isset($_SESSION['temp_admin_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_code = $_POST['auth_code'];
    $admin_id = $_SESSION['temp_admin_id'];

    // 2. Fetch the Secret Key for this specific Admin from the DB
    // $stmt = $conn->prepare("SELECT google_auth_secret FROM registration WHERE id = ?");
    // $stmt->bind_param("i", $admin_id);
    // $stmt->execute();
    // $result = $stmt->get_result();
    // $row = $result->fetch_assoc();
    // $secret = $row['google_auth_secret'];

    // // 3. Verify the 6-digit code from their phone
    // $checkResult = $ga->verifyCode($secret, $user_code, 2); 

    // if ($checkResult) {
    //     // SUCCESS! Promote them to a full session
    //     $_SESSION['user_id'] = $admin_id;
    //     $_SESSION['role'] = 'admin';
    //     // Inside admin_verify_2fa.php after the code is verified:
    //     $_SESSION['user_id'] = $user['id'];
    //     $_SESSION['role'] = 'admin';
    //     $_SESSION['fullname'] = $user['fullname']; // THIS IS THE IMPORTANT ONE
        
    //     // Clean up the temporary ID
    //     unset($_SESSION['temp_admin_id']); 
        
    //     header("Location: admindashboard.php");
    //     exit();
    // } 
    // 2. Fetch the Secret AND Fullname for this specific Admin
    $stmt = $conn->prepare("SELECT google_auth_secret, fullname FROM registration WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc(); // Now $user is defined!
    
    $secret = $user['google_auth_secret'];

    // 3. Verify the 6-digit code from their phone
    $checkResult = $ga->verifyCode($secret, $user_code, 2); 

    if ($checkResult) {
        // SUCCESS! Promote them to a full session
        $_SESSION['user_id'] = $admin_id;
        $_SESSION['role'] = 'admin';
        $_SESSION['fullname'] = $user['fullname']; // Now this works perfectly!
        
        // Clean up the temporary ID
        unset($_SESSION['temp_admin_id']); 
        
        header("Location: admindashboard.php");
        exit();
    }
    else {
        $error = "Invalid code. Please check your app and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Admin | ISJ Docs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/responsive.css">
    
    <style>
        /* Base Styling for Dark Blue Theme */
        body, html {
            height: 100%;
            margin: 0;
            background-color: #061428; /* Dark Blue Background */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff; 
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* The main centered box */
        .auth-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .auth-card {
            background-color: #ffffff; /* Card remains White for contrast */
            padding: 50px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            border-top: 6px solid #D4AF37; /* Gold accent */
        }

        /* Icon & Title styling */
        .card-icon {
            font-size: 3rem;
            color: #061428; /* Dark Blue Icon */
            margin-bottom: 20px;
        }

        h2 {
            margin: 0 0 10px 0;
            color: #061428; /* Dark Blue Title */
            font-size: 2rem;
            font-weight: 700;
        }

        p {
            margin: 0 0 30px 0;
            color: #555;
            font-size: 0.95rem;
        }

        /* Error Message Styling */
        .error-msg {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
            padding: 12px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* 6-Digit Code Input Styling */
        input[type="text"] {
            width: 100%;
            padding: 15px;
            font-size: 2.5rem;
            text-align: center;
            letter-spacing: 12px; 
            border: 2px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            background-color: #f9f9f9;
            color: #061428;
            margin-bottom: 30px;
            font-family: 'Courier New', Courier, monospace;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #061428; 
            background-color: #fff;
        }

        /* Submit Button Styling - DARK BLUE BUTTON */
        .btn-verify {
            width: 100%;
            background-color: #061428; /* Dark Blue Button */
            color: #ffffff;
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-verify:hover {
            background-color: #0a2245; /* Slightly lighter blue hover */
            transform: translateY(-2px);
        }

        /* Cancel Link Styling */
        .cancel-link {
            display: inline-block;
            margin-top: 25px;
            color: #888;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .cancel-link:hover {
            color: #061428;
            text-decoration: underline;
        }

    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="card-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            
            <h2>2-Step Verification</h2>
            <p>Please enter the 6-digit security code from your phone application.</p>
            
            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="auth_code" placeholder="000000" maxlength="6" required autocomplete="off" autofocus>
                
                <button type="submit" class="btn-verify">Verify & Login</button>
            </form>
            
            <a href="logout.php" class="cancel-link">Cancel Login Request</a>
        </div>
    </div>
    <script src="../js/responsive.js"></script>
</body>
</html>