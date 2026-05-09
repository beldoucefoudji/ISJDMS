<?php

session_start();
include("database.php");

// --- INTERNAL ACTION HANDLER ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $tab = $_GET['tab'] ?? 'home';
    $search = $_GET['search_type'] ?? '';

    // Handle the Status Toggle internally
    if ($_GET['action'] === 'toggle_status') {
        $conn->query("UPDATE registration SET status = IF(status='enabled', 'disabled', 'enabled') WHERE id = $id");
    }

    if ($_GET['action'] === 'delete_item') {
    $conn->query("DELETE FROM documents WHERE id = $id");
    
}
    // // Handle the Edit (If you just want to redirect to a specific edit page for now)
    // Inside your action handler block in admindashboard.php
    if ($_GET['action'] === 'edit_user') {
        $id = (int)$_GET['id'];
        
        // Updated path to point into the subfolder
        $target_file = "admin_roles/edit_user.php";
        
        if (file_exists($target_file)) {
            header("Location: " . $target_file . "?id=$id");
        } else {
            // This will help you debug if the path is still slightly off
            die("Error: File not found at " . realpath($target_file));
        }
        exit();
    }

    // Stay on the same tab and keep search results visible
    $url = "admindashboard.php?tab=$tab";
    if (!empty($search)) $url .= "&search_type=$search";
    if (isset($_GET['user_keyword'])) $url .= "&user_keyword=" . urlencode($_GET['user_keyword']);
    
    header("Location: $url");
    exit();
}
// Fetch unique folders from the database for the category dropdown
$folder_query = "SELECT DISTINCT name FROM documents WHERE type = 'folder'";
// Update this line at the top of your script
$folders_result = $conn->query("SELECT id, name FROM documents WHERE type = 'folder'");

// Fetch unique roles for the user filter
$role_query = "SELECT DISTINCT role FROM registration";
$roles_result = $conn->query($role_query);

// Get all currently "open" folders from the URL (e.g., ?open=1,5,12)
$open_folders = isset($_GET['open']) ? explode(',', $_GET['open']) : [];

function renderAccordionTree($conn, $parent_id = NULL, $open_folders = []) {
    $sql = ($parent_id === NULL) 
            ? "SELECT * FROM documents WHERE parent_id IS NULL AND type = 'folder' ORDER BY name ASC" 
            : "SELECT * FROM documents WHERE parent_id = $parent_id AND type = 'folder' ORDER BY name ASC";
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo '<ul style="list-style: none; padding-left: 20px; border-left: 1px dashed #061428; margin-top: 5px;">';
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $isOpen = in_array($id, $open_folders);
            
            // Build the toggle URL for the icon
            if ($isOpen) {
                $new_open = array_diff($open_folders, [$id]);
            } else {
                $new_open = array_merge($open_folders, [$id]);
            }
            $toggle_url = "?tab=plan&open=" . implode(',', $new_open);

            // INTELLIGENT CHECK: Does this folder have sub-folders?
            $check_sub = $conn->query("SELECT id FROM documents WHERE parent_id = $id AND type = 'folder' LIMIT 1");
            $hasSubFolders = ($check_sub->num_rows > 0);

            // Determine if clicking the name opens a page or expands the tree
            $name_link = $hasSubFolders ? $toggle_url : "admin_view_folder.php?id=" . $id;

            echo '<li style="margin: 8px 0;">';
            echo '<div style="display: flex; align-items: center; justify-content: space-between; max-width: 550px;">';
                
                echo '<div style="display: flex; align-items: center;">';
                    // The icon ALWAYS toggles the accordion
                    echo '<a href="'.$toggle_url.'" style="text-decoration: none; margin-right: 10px;">';
                        echo '<i class="fas '.($isOpen ? 'fa-folder-open' : 'fa-folder').'" style="color: #D4AF37;"></i>';
                    echo '</a>';

                    // The name opens the table ONLY if it's a leaf folder
                    echo '<a href="'.$name_link.'" style="text-decoration: none; color: #061428; font-weight: 500;">';
                        echo htmlspecialchars($row['name']);
                    echo '</a>';
                echo '</div>';

                // Action Buttons (Edit/Delete)
                echo '<div style="display: flex; gap: 10px; opacity: 0.5;">';
                    echo '<a href="admin_roles/edit_item.php?id='.$id.'" style="color: #3498db;"><i class="fas fa-edit fa-xs"></i></a>';
                    echo '<a href="admin_roles/delete_item.php?id='.$id.'" onclick="return confirm(\'Delete?\')" style="color: #e74c3c;"><i class="fas fa-trash fa-xs"></i></a>';
                echo '</div>';
            echo '</div>';

            // Only show sub-folders recursively; the table is now in admin_view_folder.php
            if ($isOpen) {
                renderAccordionTree($conn, $id, $open_folders);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// 1. Fetch Counts for the Overview Cards
// Total Users
$userCount = $conn->query("SELECT COUNT(*) as total FROM registration WHERE role != 'admin'")->fetch_assoc()['total'];

// NEW: Counts for the User Management Tab
$enabledCount = $conn->query("SELECT COUNT(*) as total FROM registration WHERE role != 'admin' AND status = 'enabled'")->fetch_assoc()['total'];
$disabledCount = $conn->query("SELECT COUNT(*) as total FROM registration WHERE role != 'admin' AND status = 'disabled'")->fetch_assoc()['total'];


// Total Files only (for the Home overview)
$fileCountQuery = $conn->query("SELECT COUNT(*) as total FROM documents WHERE type = 'file'");
$totalFiles = ($fileCountQuery) ? $fileCountQuery->fetch_assoc()['total'] : 0;

// Detailed counts for the Manage Docs tab
$mainFolderCount = $conn->query("SELECT COUNT(*) as total FROM documents WHERE type = 'folder' AND parent_id IS NULL")->fetch_assoc()['total'];
$subFolderCount = $conn->query("SELECT COUNT(*) as total FROM documents WHERE type = 'folder' AND parent_id IS NOT NULL")->fetch_assoc()['total'];
$pendingReview = 0; // Placeholder for future logic

// 2. Fetch Users for the table
$query = "SELECT id, fullname, role, status FROM registration WHERE role != 'admin' ORDER BY id DESC";
$result = $conn->query($query);

// 3. Fetch Documents/Folders for the table
// Update this query in your PHP section
$docs_query = "SELECT * FROM documents WHERE type = 'file' ORDER BY created_at DESC";
$docs_result = $conn->query($docs_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ISJ Admin - Dashboard</title>
    <link rel="stylesheet" href="../css/admindashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
    <div class="admin-wrapper">
                <aside class="sidebar">
                    <h2 class="brand">ISJ Admin</h2>

                        <nav class="sidebar-nav">
                            <button class="nav-btn <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'home' ? 'active' : ''; ?>" onclick="location.href='?tab=home'">
                                <span>🏠</span> Home
                            </button>
                            <button class="nav-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'users') ? 'active' : ''; ?>" onclick="location.href='?tab=users'">
                                <span>👥</span> Manage Users
                            </button>
                            <button class="nav-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'docs') ? 'active' : ''; ?>" onclick="location.href='?tab=docs'">
                                <span>📄</span> Manage Docs
                            </button>
                            <button class="nav-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'plan') ? 'active' : ''; ?>" onclick="location.href='?tab=plan'">
                                <span>🌲</span> Heading Plan
                            </button>
                        </nav>

                    <div class="sidebar-footer">
                        <a href="logout.php" class="logout-link exit-btn">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                        </a>
                    </div>
               </aside>

            <div class="main-layout">
                        <header class="admin-header">
                            <!-- <div class="header-search"><input type="text" placeholder="Search system..."></div> -->
                            <div class="admin-profile">
                                <span class="role-badge">ADMIN</span>
                                <div class="avatar">AD</div>
                            </div>
                        </header>
                    <main class="content-body">
                        <?php if(!isset($_GET['tab']) || $_GET['tab'] == 'home'): ?>
                                                    <h2 class="section-title">Global Search & Filters</h2>

                                                        <!-- 1. Stats Grid (2 Cards) -->
                                                    <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                                                        <div class="stat-card">
                                                            <div class="stat-info">
                                                                <h3><?php echo $userCount; ?></h3>
                                                                <p>Total Registered Users</p>
                                                            </div>
                                                            <i class="fas fa-user-shield"></i>
                                                        </div>
                                                        <div class="stat-card">
                                                            <div class="stat-info">
                                                                <h3><?php echo $totalFiles; ?></h3>
                                                                <p>Total Indexed Documents</p>
                                                            </div>
                                                            <i class="fas fa-database"></i>
                                                        </div>
                                                    </div>

                                                <!-- 2. The Filter Hub -->
                                            <div class="filter-container" style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                                    
                                                            <!-- DOCUMENT SEARCH SECTION -->
                                                            <div class="filter-column" style="border-right: 1px solid #eee; padding-right: 20px;">
                                                                <h4 style="margin-bottom: 15px;"><i class="fas fa-file-search"></i> Search Documents</h4>
                                                                        <form action="admindashboard.php" method="GET">
                                                                                <input type="hidden" name="tab" value="home">
                                                                                <input type="hidden" name="search_type" value="docs">
                                                                                
                                                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                                                                    <input type="text" name="doc_name" placeholder="File Name/Title" value="" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                                                                                    <input type="text" name="doc_author" placeholder="Author Name" value="" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                                                                                    <input type="date" name="doc_date" value="" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                                                                                    <input type="text" name="doc_keyword" placeholder="Keyword" value="" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                                                                                </div>

                                                                                <select name="folder_filter" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 10px;">
                                                                                    <option value="all">All Folders (Categories)</option>
                                                                                    <?php 
                                                                                    if($folders_result) {
                                                                                        $folders_result->data_seek(0); 
                                                                                        while($row = $folders_result->fetch_assoc()): ?>
                                                                                            <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['folder_filter']) && $_GET['folder_filter'] == $row['id']) ? 'selected' : ''; ?>>
                                                                                                <?php echo htmlspecialchars($row['name']); ?>
                                                                                            </option>
                                                                                        <?php endwhile; 
                                                                                    } ?>
                                                                                </select>

                                                                                <button type="submit" style="width: 100%; background: #061428; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer;">Filter Docs</button>
                                                                        </form>
                                                            </div>

                                                            <!-- USER SEARCH SECTION -->
                                                            <div class="filter-column">
                                                                <h4 style="margin-bottom: 15px;"><i class="fas fa-users-cog"></i> Search Users</h4>
                                                                    <form action="admindashboard.php" method="GET">
                                                                            <input type="hidden" name="tab" value="home">
                                                                            <input type="hidden" name="search_type" value="users">

                                                                            <select name="role_filter" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 10px;">
                                                                                <option value="all">All Roles</option>
                                                                                <option value="staff" <?php echo (isset($_GET['role_filter']) && $_GET['role_filter'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                                                                <option value="teacher" <?php echo (isset($_GET['role_filter']) && $_GET['role_filter'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                                                                <option value="parent" <?php echo (isset($_GET['role_filter']) && $_GET['role_filter'] == 'parent') ? 'selected' : ''; ?>>Parent</option>
                                                                                <option value="student" <?php echo (isset($_GET['role_filter']) && $_GET['role_filter'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                                                            </select>

                                                                            <select name="status_filter" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 53px;">
                                                                                <option value="all">All Status</option>
                                                                                <option value="enabled" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'enabled') ? 'selected' : ''; ?>>Enabled</option>
                                                                                <option value="disabled" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                                                                            </select>

                                                                            <button type="submit" style="width: 100%; background: #cca43b; color: #061428; font-weight: bold; padding: 12px; border: none; border-radius: 5px; cursor: pointer;">Filter Users</button>
                                                                    </form>
                                                            </div>
                                                </div>
                                            </div>

                                    <!-- 3. Dynamic Results Table -->
                                    <div class="results-area" style="margin-top: 30px;">
                                                
                                                            <?php if(isset($_GET['search_type']) && $_GET['search_type'] == 'docs'): 
                                                                        // 1. Secure and capture inputs
                                                                        $name = $conn->real_escape_string($_GET['doc_name'] ?? '');
                                                                        $author = $conn->real_escape_string($_GET['doc_author'] ?? '');
                                                                        $date = $conn->real_escape_string($_GET['doc_date'] ?? '');
                                                                        $keyword = $conn->real_escape_string($_GET['doc_keyword'] ?? '');
                                                                        $folder_id = $conn->real_escape_string($_GET['folder_filter'] ?? 'all');

                                                                        // 2. Base Query: Use 'file' singular to match your enum definition
                                                                    // Use 'file' singular to match your enum
                                                                    $sql = "SELECT * FROM documents WHERE type = 'file'";

                                                                    if($folder_id != 'all' && !empty($folder_id)) {
                                                                        // We use parent_id because it links files to folders
                                                                        $sql .= " AND (parent_id = '$folder_id' 
                                                                                OR parent_id IN (SELECT id FROM documents WHERE parent_id = '$folder_id' AND type = 'folder'))";
                                                                    }

                                                                        // 4. Text & Metadata Filters
                                                                        if(!empty($name))    $sql .= " AND name LIKE '%$name%'";
                                                                        if(!empty($author))  $sql .= " AND author LIKE '%$author%'";
                                                                        if(!empty($date))    $sql .= " AND DATE(created_at) = '$date'";
                                                                        if(!empty($keyword)) $sql .= " AND (name LIKE '%$keyword%' OR description LIKE '%$keyword%')";

                                                                        $sql .= " ORDER BY created_at DESC";
                                                                        
                                                                        // Optional: Uncomment the line below if you still get no results to see the query being run
                                                                        // echo "<!-- Debug SQL: " . $sql . " -->";

                                                                        $res = $conn->query($sql);
                                                            ?>
                                            <div class="table-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                                    <h3 style="margin: 0;">File Search Results</h3>
                                                    <span style="font-size: 0.9em; color: #666;"><?php echo $res ? $res->num_rows : 0; ?> items found</span>
                                                </div>
                                                
                                                <table class="isj-table" style="width: 100%; border-collapse: collapse;">
                                                    <thead>
                                                        <tr style="text-align: left; border-bottom: 2px solid #eee;">
                                                            <th style="padding: 12px;">File Name</th>
                                                            <th>Date</th>
                                                            <th>Author</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if($res && $res->num_rows > 0): while($file = $res->fetch_assoc()): ?>
                                                            <tr style="border-bottom: 1px solid #f5f5f5;">
                                                                <td style="padding: 12px;">
                                                                    <strong><?php echo htmlspecialchars($file['name']); ?></strong>
                                                                    <?php if(!empty($file['description'])): ?>
                                                                        <br><small style="color: #888;"><?php echo htmlspecialchars($file['description']); ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?php echo date('d/m/Y', strtotime($file['created_at'])); ?></td>
                                                                <td><?php echo htmlspecialchars($file['author']); ?></td>
                                                                <td>
                                                                    <div style="display: flex; gap: 10px;">
                                                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                                                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;"><i class="fas fa-download"></i></a>
                                                                        <a href="admin_roles/edit_item.php?id=<?php echo $file['id']; ?>" class="btn-icon" style="color: #3498db;" title="Modify"><i class="fas fa-edit"></i></a>
                                                                        <a href="admin_roles/delete_item.php?id=<?php echo $file['id']; ?>" class="btn-icon delete" onclick="return confirm('Delete this item?')" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; else: ?>
                                                            <tr>
                                                                <td colspan="4" style="text-align: center; padding: 40px; color: #888;">
                                                                    <i class="fas fa-folder-open" style="font-size: 2em; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                                                    No files found matching your search.
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>


                                                                <?php elseif(isset($_GET['search_type']) && $_GET['search_type'] == 'users'): 
                                                                                // FETCH USERS LOGIC (registration table)
                                                                                $role = $conn->real_escape_string($_GET['role_filter'] ?? 'all');
                                                                                $status = $conn->real_escape_string($_GET['status_filter'] ?? 'all');
                                                                                
                                                                                // Base Query: 1=1 is a trick to make adding 'AND' conditions easier
                                                                                $sql = "SELECT * FROM registration WHERE 1=1";
                                                                                
                                                                                // MODIFICATION: Exclude admin from results
                                                                                $sql .= " AND role != 'admin'";

                                                                                if($role != 'all') {
                                                                                    $sql .= " AND role = '$role'";
                                                                                }
                                                                                
                                                                                if($status != 'all') {
                                                                                    $sql .= " AND status = '$status'";
                                                                                }
                                                                                
                                                                                $sql .= " ORDER BY created_at DESC";
                                                                                $res = $conn->query($sql);
                                                                    ?>
                                                <div class="table-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                                                    <h3>User Search Results</h3>
                                                                <table class="isj-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                                                                    <thead>
                                                                        <tr style="text-align: left; border-bottom: 2px solid #eee;">
                                                                            <th style="padding: 12px;">Full Name</th>
                                                                            <th>Role</th>
                                                                            <th>Status</th>
                                                                            <th>Actions</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                                <?php if($res && $res->num_rows > 0): while($user = $res->fetch_assoc()): ?>
                                                                                    <tr style="border-bottom: 1px solid #f5f5f5;">
                                                                                        <td style="padding: 12px;"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                                                                        <td><span class="role-badge"><?php echo ucfirst($user['role']); ?></span></td>
                                                                                        <td><?php echo ucfirst($user['status']); ?></td>
                                                                                        <td>
                                                                                            <!-- 1. Edit User Link (Directs back to same page with action) -->
                                                                                            <!-- Inside your User Search Results while loop -->
                                                                                            <a href="?tab=home&action=edit_user&id=<?php echo $user['id']; ?>&search_type=user" class="btn-icon">
                                                                                                <i class="fas fa-edit" style="color: #061428; margin-right: 10px;"></i>
                                                                                            </a>

                                                                                            <!-- 2. Toggle Status Link -->
                                                                                            <a href="?tab=home&action=toggle_status&id=<?php echo $user['id']; ?>&search_type=user" class="btn-icon">
                                                                                                <?php if(strtolower($user['status']) == 'enabled'): ?>
                                                                                                    <i class="fas fa-ban" style="color: #e74c3c;" title="Disable"></i>
                                                                                                <?php else: ?>
                                                                                                    <i class="fas fa-check-square" style="color: #2ecc71;" title="Enable"></i>
                                                                                                <?php endif; ?>
                                                                                            </a>
                                                                                        </td>
                                                                                    </tr>
                                                                                <?php endwhile; else: ?>
                                                                                    <tr><td colspan="4" style="text-align: center; padding: 30px; color: #888;">No users found with those filters.</td></tr>
                                                                                <?php endif; ?>
                                                                    </tbody>
                                                                </table>
                                                    </div>

                                                    <?php else: ?>
                                                        <!-- This handles the state where no search has been performed yet -->
                                                        <div style="text-align: center; padding: 40px; color: #bbb; border: 2px dashed #eee; border-radius: 15px;">
                                                            <i class="fas fa-filter" style="font-size: 2em; margin-bottom: 10px;"></i>
                                                            <p>Use the filters above to browse specific documents or users.</p>
                                                        </div>
                                                    <?php endif; ?>
                                </div>
                        <?php endif; ?>

                        <?php if(isset($_GET['tab']) && $_GET['tab'] == 'users'): ?>
                                    <section id="tab-users" class="admin-tab">
                                                <div class="page-header-flex" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                                                            <div style="display: flex; align-items: center; gap: 20px;">
                                                                <h2 class="page-title" style="margin: 0;">User Management</h2>
                                                                
                                                                    <div class="user-stats-badges" style="display: flex; gap: 10px;">
                                                                        <span style="background: #f1f3f5; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #495057;">
                                                                            Total: <?php echo $userCount; ?>
                                                                        </span>
                                                                        <span style="background: #e6fffa; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #088f8f; border: 1px solid #b2f5ea;">
                                                                            Enabled: <?php echo $enabledCount; ?>
                                                                        </span>
                                                                        <span style="background: #fff5f5; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #e53e3e; border: 1px solid #feb2b2;">
                                                                            Disabled: <?php echo $disabledCount; ?>
                                                                        </span>
                                                                    </div>
                                                            </div>
                                                    
                                                            <button class="btn-create" onclick="location.href='admin_roles/add_user.php'">+ Create New User</button>
                                                </div>

                                                <div class="data-table-container">
                                                        <table class="isj-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>NAME</th>
                                                                        <th>ROLE</th>
                                                                        <th>STATUS</th>
                                                                        <th>ACTIONS</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php while($user = $result->fetch_assoc()): ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                                                            <td><?php echo ucfirst($user['role']); ?></td>
                                                                            <td>
                                                                                <span class="status-pill <?php echo strtolower($user['status']); ?>">
                                                                                    <?php echo ucfirst($user['status']); ?>
                                                                                </span>
                                                                            </td>
                                                                            <td class="action-cell">
                                                                                <a href="admin_roles/edit_user.php?id=<?php echo $user['id']; ?>" class="btn-icon">
                                                                                    <i class="fas fa-edit"></i>
                                                                                </a>

                                                                                <a href="admin_roles/toggle_status.php?id=<?php echo $user['id']; ?>" class="btn-icon">
                                                                                    <?php if(strtolower($user['status']) == 'enabled'): ?>
                                                                                        <i class="fas fa-ban" style="color: #e74c3c;" title="Disable"></i>
                                                                                    <?php else: ?>
                                                                                        <i class="fas fa-check-square" style="color: #2ecc71;" title="Enable"></i>
                                                                                    <?php endif; ?>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endwhile; ?>
                                                                </tbody>
                                                        </table>
                                                </div>
                                        </section>
                        <?php endif; ?>

                        <?php if(isset($_GET['tab']) && $_GET['tab'] == 'docs'): ?>
                                        <section id="tab-docs" class="admin-tab">


                                                                <?php if(isset($_GET['status'])): ?>
                                                                        <div class="alert-box" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; background: #e6fffa; border: 1px solid #b2f5ea; color: #088f8f;">
                                                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                                                <i class="fas fa-check-circle"></i>
                                                                                <span>Excellent! The action was successful.</span>
                                                                            </div>
                                                                            <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:inherit; cursor:pointer; font-weight:bold;">&times;</button>
                                                                        </div>
                                                                <?php endif; ?>

                                                                <div class="page-header-flex">
                                                                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                                                                <h2 class="page-title" style="margin: 0;">File Management</h2>
                                                                                <div style="display: flex; gap: 10px; margin-top: 5px;">
                                                                                    <span class="count-badge">📂 Main Folders: <?php echo $mainFolderCount; ?></span>
                                                                                    <span class="count-badge">📁 Sub-Folders: <?php echo $subFolderCount; ?></span>
                                                                                    <span class="count-badge">📄 Files: <?php echo $totalFiles; ?></span>
                                                                                </div>
                                                                            </div>
                                                                            <div style="display: flex; gap: 10px;">
                                                                                <button class="btn-create" onclick="location.href='admin_roles/create_folder.php'" style="background: var(--isj-blue); color: var(--isj-gold); border: 1px solid var(--isj-gold);">
                                                                                    <i class="fas fa-folder-plus"></i> New Folder
                                                                                </button>
                                                                                
                                                                                <button class="btn-create" onclick="location.href='admin_roles/upload_doc.php'">
                                                                                    <i class="fas fa-upload"></i> Upload Document
                                                                                </button>
                                                                            </div>
                                                                </div>

                                                            <div class="data-table-container">
                                                                <table class="isj-table">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>NAME</th>
                                                                                    <th>TYPE</th>
                                                                                    <th>VIEWED BY</th>
                                                                                    <th>AUTHOR</th>
                                                                                    <th>DATE</th>
                                                                                    <th>ACTIONS</th>
                                                                                </tr>
                                                                            </thead>
                                                                    <tbody>
                                                                        <?php if ($docs_result && $docs_result->num_rows > 0): ?>
                                                                            <?php while($item = $docs_result->fetch_assoc()): ?>
                                                                                    <tr>
                                                                                            <td>
                                                                                                <?php if($item['type'] == 'folder'): ?>
                                                                                                    <i class="fas fa-folder" style="color: #f1c40f; margin-right: 8px;"></i>
                                                                                                <?php else: ?>
                                                                                                    <i class="fas fa-file-pdf" style="color: #e74c3c; margin-right: 8px;"></i>
                                                                                                <?php endif; ?>
                                                                                                <?php echo htmlspecialchars($item['name']); ?>
                                                                                            </td>
                                                                                            <td><span class="type-badge <?php echo $item['type']; ?>"><?php echo strtoupper($item['type']); ?></span></td>
                                                                                            <td><span class="role-pill"><?php echo strtoupper($item['viewed_by']); ?></span></td>
                                                                                            <td><?php echo htmlspecialchars($item['author']); ?></td>
                                                                                            <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                                                                                            <td class="action-cell">
                                                                                                <?php if($item['type'] == 'file'): ?>
                                                                                                    <a href="../<?php echo $item['file_path']; ?>" class="btn-icon" target="_blank" title="View">
                                                                                                        <i class="fas fa-eye"></i> 
                                                                                                    </a>
                                                                                                    <a href="admin_roles/share_doc.php?id=<?php echo $item['id']; ?>" class="btn-icon" style="color: #D4AF37;" title="Share">
                                                                                                        <i class="fas fa-paper-plane"></i>
                                                                                                    </a>
                                                                                                <?php endif; ?>

                                                                                                <a href="admin_roles/edit_item.php?id=<?php echo $item['id']; ?>" class="btn-icon" style="color: #3498db;" title="Modify">
                                                                                                    <i class="fas fa-edit"></i>
                                                                                                </a>

                                                                                                <a href="admin_roles/delete_item.php?id=<?php echo $item['id']; ?>" class="btn-icon delete" onclick="return confirm('Delete this item?')" title="Delete">
                                                                                                    <i class="fas fa-trash-alt"></i>
                                                                                                </a>
                                                                                            </td>
                                                                                    </tr>
                                                                            <?php endwhile; ?>
                                                                        <?php else: ?>
                                                                            <tr><td colspan="6" style="text-align:center; padding: 40px;">No documents or folders found. Start by creating a folder!</td></tr>
                                                                        <?php endif; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                </section>
                        <?php endif; ?>

                        <?php if(isset($_GET['tab']) && $_GET['tab'] == 'plan'): ?>
                                        <section id="tab-plan" class="admin-tab">
                                                <div class="page-header-flex">
                                                    <h2 class="page-title">Organizational Heading Plan</h2>
                                                    <button class="btn-create" onclick="location.href='admin_roles/create_folder.php'">
                                                        <i class="fas fa-folder-plus"></i> New Folder
                                                    </button>
                                                </div>

                                                <div class="data-table-container" style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                                                    <div class="folder-hierarchy">
                                                        <?php renderAccordionTree($conn, NULL, $open_folders); ?>
                                                    </div>
                                                </div>
                                        </section>
                        <?php endif; ?>
                    </main>
            </div>
    </div>
<script>
    // If the page is reloaded (refreshed), clear the URL and go home
    if (performance.navigation.type === performance.navigation.TYPE_RELOAD) {
        window.location.href = "admindashboard.php"; }

</script>
<script src="../js/responsive.js"></script>
</body>
</html>