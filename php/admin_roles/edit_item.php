<?php
session_start();
include("../database.php");

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

if (!$id) {
    header("Location: ../admindashboard.php?tab=docs");
    exit();
}

// 2. Fetch current data for the item
$stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    die("Item not found.");
}

// 3. Handle the Update Logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Process multiple roles
    if (isset($_POST['viewed_by'])) {
        $roles_array = $_POST['viewed_by'];
        if (in_array('all', $roles_array)) {
            $viewed_by_str = 'all';
        } else {
            $viewed_by_str = implode(',', $roles_array);
        }
    } else {
        $viewed_by_str = 'all';
    }

    $update_sql = "UPDATE documents SET name = '$name', description = '$description', viewed_by = '$viewed_by_str' WHERE id = $id";

    if ($conn->query($update_sql)) {
        header("Location: ../admindashboard.php?tab=docs&status=updated");
        exit();
    } else {
        $message = "Error updating: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit <?php echo ucfirst($item['type']); ?> - ISJ</title>
    <link rel="stylesheet" href="../../css/admindashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .edit-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); border-top: 5px solid #3498db; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-update { background: #3498db; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-update:hover { background: #2980b9; transform: translateY(-2px); }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<div class="edit-container">
    <h2 style="color: #061428; margin-bottom: 25px;"><i class="fas fa-edit"></i> Modify <?php echo ucfirst($item['type']); ?></h2>
    
    <?php if($message): ?>
        <p style="color: #e74c3c; background: #fdf2f2; padding: 10px; border-radius: 5px;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Title / Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"><?php echo htmlspecialchars($item['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Who can view this? (Hold Ctrl to select multiple)</label>
            <select name="viewed_by[]" multiple required style="height: 120px;">
                <?php 
                $current_roles = explode(',', $item['viewed_by']);
                $options = [
                    'all' => 'Everyone',
                    'teacher' => 'Teachers',
                    'staff' => 'Staff Members',
                    'student' => 'Students',
                    'parent' => 'Parents'
                ];
                foreach($options as $val => $label): 
                    $selected = in_array($val, $current_roles) ? "selected" : "";
                ?>
                    <option value="<?php echo $val; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-update">Save Changes</button>
        <a href="../admindashboard.php?tab=docs" class="cancel-link">Cancel and Go Back</a>
    </form>
</div>

 <script src="../../js/responsive.js"></script>
</body>
</html>