<?php
session_start();
include("../database.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs to prevent SQL Injection
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $admin_otp = rand(100000, 999999); 
    $status = 'Enabled'; 

    // UNIQUE CHECK: Verify if Email OR Username already exists in the system
    $check = $conn->prepare("SELECT id FROM registration WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $message = "<p style='color: #e74c3c; text-align:center; font-weight:bold;'>Error: This Email or Username is already taken.</p>";
    } else {
        // Proceed with insertion if both are unique
        $stmt = $conn->prepare("INSERT INTO registration (fullname, email, phone_number, username, role, password, status, otp_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $fullname, $email, $phone, $username, $role, $password, $status, $admin_otp);

        if ($stmt->execute()) {
            header("Location: ../admindashboard.php?tab=users&msg=user_added");
            exit();
        } else {
            $message = "<p style='color: #e74c3c; text-align:center;'>Error: Database insertion failed.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New User - ISJ Admin</title>
    <link rel="stylesheet" href="../../css/admindashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <style>
        .add-container { max-width: 500px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 12px; position: relative; }
        .form-group label { display: block; margin-bottom: 4px; color: #001f3f; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; background: #f9f9f9; box-sizing: border-box; }
        
        .password-wrapper { position: relative; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #777; }
        
        .btn-create { background: #D4AF37; color: #001f3f; border: none; width: 100%; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; font-size: 16px; }
        .btn-create:hover { background: #B8860B; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #777; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body style="background: #f1f4f9;">
    <div class="add-container">
        <h2 style="color: #001f3f; text-align: center; margin-bottom: 20px;">Add New User</h2>
        
        <?php echo $message; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" placeholder="Full name" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Email address" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" placeholder="Phone number" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="form-group">
                <label>User Role</label>
                <select name="role">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="parent">Parent</option>
                </select>
            </div>

            <div class="form-group">
                <label>Temporary Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="temp_password" placeholder="••••••••" required>
                    <i class="fas fa-eye toggle-password" id="toggleEye"></i>
                </div>
            </div>

            <button type="submit" class="btn-create">CREATE ACCOUNT</button>
            <a href="../admindashboard.php?tab=users" class="back-link">Cancel and Go Back</a>
        </form>
    </div>

    <script>
        const toggleEye = document.querySelector('#toggleEye');
        const passwordInput = document.querySelector('#temp_password');

        toggleEye.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>

    <script src="../../js/responsive.js"></script>
</body>
</html>