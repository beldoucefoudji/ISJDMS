<?php
session_start();
include("database.php");

// 1. Protection & Identity
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$displayName = ucfirst(strtolower(explode(' ', $fullname)[0]));
$initial = substr($displayName, 0, 1);
$folder_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. Fetch Folder Details
$folder_query = "SELECT name FROM documents WHERE id = $folder_id AND type = 'folder'";
$folder_data = $conn->query($folder_query)->fetch_assoc();
$current_folder_name = $folder_data['name'] ?? 'Unknown Folder';

// 3. Fetch Files
$file_sql = "SELECT * FROM documents WHERE parent_id = $folder_id AND type = 'file' ORDER BY name ASC";
$file_res = $conn->query($file_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_folder_name; ?> — Admin View</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_view_folder.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="admin-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../images/image.png" alt="ISJ Logo">
        </div>
        <nav class="sidebar-nav">
            <p class="nav-label">ADMINISTRATION</p>
            <a href="admindashboard.php?tab=plan" class="nav-item active">
                <i class="fas fa-arrow-left"></i> Back to Plan
            </a>
            <a href="admindashboard.php?tab=docs" class="nav-item">
                <i class="fas fa-file-alt"></i> Manage Docs
            </a>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php" class="nav-item logout-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="header-top">
            <div class="breadcrumb">
                <a href="admindashboard.php?tab=plan">Heading Plan</a> 
                <span>/</span> 
                <strong><?php echo htmlspecialchars($current_folder_name); ?></strong>
            </div>
            <div class="user-info">
                <span class="user-name">Admin: <?php echo htmlspecialchars(ucfirst($displayName)); ?></span>
                <div class="user-avatar"><?php echo htmlspecialchars($initial); ?></div>
            </div>
        </header>

        <div class="content-body">
            <div class="section-header">
                <h2><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($current_folder_name); ?></h2>
                <button class="btn-add" onclick="location.href='admin_roles/upload_doc.php?parent_id=<?php echo $folder_id; ?>'">
                    <i class="fas fa-plus"></i> Upload to this Folder
                </button>
            </div>

            <?php if ($file_res->num_rows > 0): ?>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Author</th>
                                <th>Upload Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($file = $file_res->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-pdf icon-pdf"></i> 
                                    <?php echo htmlspecialchars($file['name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($file['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="view_doc.php?id=<?php echo $file['id']; ?>" target="_blank" class="btn-icon">
            <i class="fas fa-eye" style="color: var(--isj-blue);"></i> 
        </a>
                                    <a href="admin_roles/edit_doc.php?id=<?php echo $file['id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="admin_roles/delete_item.php?id=<?php echo $file['id']; ?>" onclick="return confirm('Delete document?')" title="Delete" class="delete-link"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-circle-exclamation"></i>
                    <p>No documents found in this folder.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="../js/responsive.js"></script>
</body>
</html>
