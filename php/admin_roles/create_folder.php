<?php
session_start();
include("../database.php");

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch existing folders
$folder_query = "SELECT id, name FROM documents WHERE type = 'folder' ORDER BY name ASC";
$folders = $conn->query($folder_query);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : "NULL";
    $author = mysqli_real_escape_string($conn, $_SESSION['fullname']);

    // --- MULTIPLE ROLES LOGIC FOR FOLDERS ---
    if (isset($_POST['viewed_by'])) {
        $roles_array = $_POST['viewed_by'];
        
        if (in_array('all', $roles_array)) {
            $viewed_by_str = 'all';
        } else {
            // Sanitize each role
            $sanitized_roles = array_map(function($role) use ($conn) {
                return mysqli_real_escape_string($conn, $role);
            }, $roles_array);
            $viewed_by_str = implode(',', $sanitized_roles);
        }
    } else {
        $viewed_by_str = 'all'; 
    }
    // ----------------------------------------

    $sql = "INSERT INTO documents (name, description, author, type, parent_id, viewed_by) 
            VALUES ('$name', '$description', '$author', 'folder', $parent_id, '$viewed_by_str')";

    if ($conn->query($sql)) {
        header("Location: ../admindashboard.php?tab=plan&success=folder_created");
        exit();
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Folder - ISJ</title>
    <link rel="stylesheet" href="../../css/admindashboard.css"> 
    <link rel="stylesheet" href="../../css/responsive.css">
    <style>
        .form-container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 5px solid #D4AF37; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #001f3f; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-group { display: flex; gap: 10px; }
        .btn-create { background: #001f3f; color: #D4AF37; border: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body style="background: #f1f4f9; padding: 20px;">

<div class="form-container">
    <h2 style="color: #001f3f; margin-bottom: 20px; border-bottom: 2px solid #D4AF37; padding-bottom: 10px;">
        📁 Create New Folder
    </h2>

    <?php if($message) echo "<p style='color: red;'>$message</p>"; ?>

    <form method="POST">
        <div class="form-group">
            <label>Folder Name</label>
            <input type="text" name="name" placeholder="e.g., Financial Reports" required>
        </div>

        <div class="form-group">
            <label>Description / Keywords</label>
            <textarea name="description" placeholder="Keywords to help find this folder..." rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>Placement (Parent Folder)</label>
            <select name="parent_id">
                <option value="">-- Root Level (No Parent) --</option>
                <?php while($f = $folders->fetch_assoc()): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo $f['name']; ?></option>
                <?php endwhile; ?>
            </select>
            <small style="color: #666;">Select a parent folder if this is a sub-folder.</small>
        </div>

        <div class="form-group">
            <label>Who can view this folder? (Hold Ctrl/Cmd to select multiple)</label>
            <select name="viewed_by[]" multiple required style="height: 120px;">
                <option value="all">Everyone</option>
                <option value="admin">Admins Only</option>
                <option value="teacher">Teachers</option>
                <option value="staff">Staff Members</option>
                <option value="student">Students</option>
                <option value="parent">Parents</option>
            </select>
            <small style="color: #666;">Multi-select enabled. "Everyone" overrides specific roles.</small>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn-create">Create Folder</button>
            <button type="button" class="btn-create" onclick="location.href='../admindashboard.php?tab=docs'" style="background: #ccc; color: #333;">Cancel</button>
        </div>
    </form>
</div>
 
<script src="../../js/responsive.js"></script>
</body>
</html>
