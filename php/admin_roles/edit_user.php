<?php
session_start();
include("../database.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";

// 1. Fetch current user data (phone_number)
$query = $conn->prepare("SELECT * FROM registration WHERE id = ? AND role != 'admin'");
$query->bind_param("i", $id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../admindashboard.php?tab=users&error=not_found");
    exit();
}

// 2. Handle the Update (Status removed from here as requested)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone_number'];
    $role = $_POST['role'];

    // SQL updated to exclude the status column
    $update = $conn->prepare("UPDATE registration SET fullname = ?, email = ?, phone_number = ?, role = ? WHERE id = ?");
    $update->bind_param("ssssi", $fullname, $email, $phone, $role, $id);

    if ($update->execute()) {
        header("Location: ../admindashboard.php?tab=users&msg=updated");
        exit();
    } else {
        $message = "<p style='color: #e74c3c; text-align:center;'>Error updating record.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - ISJ Admin</title>
    <link rel="stylesheet" href="../../css/admindashboard.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <style>
        .edit-container { max-width: 500px; margin: 30px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #001f3f; font-weight: bold; font-size: 13px; text-transform: uppercase; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; background: #f9f9f9; }
        .btn-update { background: #D4AF37; color: #001f3f; border: none; width: 100%; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; font-size: 16px; }
        .btn-update:hover { background: #B8860B; }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #777; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body style="background: #f1f4f9;">
    <div class="edit-container">
        <h2 style="color: #001f3f; text-align: center; margin-bottom: 25px;">Update User Information</h2>
        <?php echo $message; ?>
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" placeholder="e.g. Jean Dupont" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="+237 600 000 000">
            </div>

            <div class="form-group">
                <label>User Role</label>
                <select name="role">
                    <option value="student" <?php if($user['role'] == 'student') echo 'selected'; ?>>Student</option>
                    <option value="teacher" <?php if($user['role'] == 'teacher') echo 'selected'; ?>>Teacher</option>
                    <option value="parent" <?php if($user['role'] == 'parent') echo 'selected'; ?>>Parent</option>
                </select>
            </div>

            <button type="submit" class="btn-update">SAVE CHANGES</button>
            <a href="../admindashboard.php?tab=users" class="cancel-link">Cancel and Go Back</a>
        </form>
    </div>
    <script src="../../js/responsive.js"></script>
</body>
</html>