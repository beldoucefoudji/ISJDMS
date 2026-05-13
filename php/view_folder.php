<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$user_role = $_SESSION['role'] ?? ''; 
$displayName = strtolower(explode(' ', $fullname)[0]); 
$initial = substr($displayName, 0, 1);
$folder_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch current folder details
if ($user_role === 'admin') {
    $folder_stmt = $conn->prepare("SELECT name FROM documents WHERE id = ? AND type = 'folder'");
    $folder_stmt->bind_param("i", $folder_id);
} else {
    $folder_stmt = $conn->prepare("SELECT name FROM documents WHERE id = ? AND type = 'folder' AND (FIND_IN_SET(?, viewed_by) OR viewed_by = 'all')");
    $folder_stmt->bind_param("is", $folder_id, $user_role);
}
$folder_stmt->execute();
$folder_data = $folder_stmt->get_result()->fetch_assoc();
if (!$folder_data) {
    http_response_code(403);
    echo "Folder not found or access denied.";
    exit();
}
$current_folder_name = $folder_data['name'] ?? 'Unknown Folder';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_folder_name; ?> — ISJ Docs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/userdashboard.css?v=1.5">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../images/image.png" alt="ISJ Logo">
        </div>
        <nav class="sidebar-nav">
            <p class="nav-label">DOCUMENTATION</p>
            <a href="userdashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i> Back to Dashboard
            </a>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php" class="nav-item logout-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header-top">
            <div class="breadcrumb">
                <a href="userdashboard.php" style="text-decoration:none; color: var(--isj-gold); font-weight:bold;">All Files</a> 
                <span style="color: #888; margin: 0 10px;">/</span> 
                <span><?php echo htmlspecialchars($current_folder_name); ?></span>
            </div>
            
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars(ucfirst($displayName)); ?></span>
                <div class="user-avatar"><?php echo htmlspecialchars($initial); ?></div>
            </div>
        </header>

        <div class="section-title">
            <h2><i class="fas fa-folder-open" style="color: var(--isj-gold);"></i> <?php echo htmlspecialchars($current_folder_name); ?></h2>
        </div>

        <div class="folder-grid">
            <?php
            $sub_sql = "SELECT * FROM documents WHERE parent_id = $folder_id AND type = 'folder' 
                        AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";
            $sub_res = $conn->query($sub_sql);
            while($sub = $sub_res->fetch_assoc()): ?>
                <div class='folder-card' onclick="window.location.href='view_folder.php?id=<?php echo $sub['id']; ?>'">
                    <i class='fas fa-folder fa-2x' style='color: var(--isj-blue);'></i>
                    <div class='folder-info'>
                        <h4><?php echo htmlspecialchars($sub['name']); ?></h4>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php
        $file_sql = "SELECT * FROM documents WHERE parent_id = $folder_id AND type = 'file' 
                     AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";
        $file_res = $conn->query($file_sql);

        if ($file_res->num_rows > 0): ?>
            <div class="table-card" style="margin-top: 30px;">
                <table class="isj-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date Inserted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($file = $file_res->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <i class="fas fa-file-pdf pdf-red"></i> 
                                <?php echo htmlspecialchars($file['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($file['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon" title="View">
                                    <i class="fas fa-eye" style="color: var(--isj-blue);"></i> 
                                </a>
                                <a href="admin_roles/share_doc.php?id=<?php echo $file['id']; ?>" class="btn-icon" style="color: var(--isj-gold);" title="Share">
                                    <i class="fas fa-paper-plane"></i>
                                </a>
                                <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($sub_res->num_rows == 0): ?>
            <p style="text-align: center; color: #888; margin-top: 50px;">This folder is empty.</p>
        <?php endif; ?>
    </main>
</div>
<script src="../js/responsive.js"></script>
</body>
</html>
