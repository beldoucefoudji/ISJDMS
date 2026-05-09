<?php
session_start();
include("database.php");

// If the user clicks the 'Dashboard' link or we want a clean state
if (isset($_GET['reset'])) {
    // Redirect to the clean URL without any search parameters
    header("Location: userdashboard.php");
    exit();
}
// 2. THE FRESH LOAD CHECK: Determine if we should show results or not
$results = null; // Default to no results
$is_searching = isset($_GET['perform_search']) || isset($_POST['run_content_search']);

if ($is_searching) {
    // ONLY run your search SQL logic here
    // (Place the $query building and $results = $conn->query code inside this block)
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Determine which tab should be open. Default is now 'all_files'
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all_files';

$fullname = $_SESSION['fullname'];
// Retrieve the role from the session
$user_role = $_SESSION['role'] ?? ''; 
$displayName = strtolower(explode(' ', $fullname)[0]); 
$initial = substr($displayName, 0, 1);

// Flag for upload permissions (Staff and Teachers only)
$can_upload = ($user_role === 'teacher' || $user_role === 'staff');

// --- PLACE THE NEW CODE HERE ---
// Fetch folders the current user is allowed to see
$folders_query = "SELECT id, name FROM documents 
                  WHERE type = 'folder' 
                  AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";

$folders_result = $conn->query($folders_query);

// Put this near your other PHP variables at the top
$recent_query = "SELECT * FROM documents 
                 WHERE type = 'file' 
                 AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                 ORDER BY created_at DESC 
                 LIMIT 10";
$recent_res = $conn->query($recent_query);

// 1. Function for actual documents (.pdf, .docx, etc.)
function get_deep_file_count($conn, $folder_id, $user_role) {
    if (empty($user_role)) return 0;
    $total = 0;
    $sql = "SELECT COUNT(*) as c FROM documents 
            WHERE parent_id = $folder_id 
            AND type = 'file' 
            AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";
    $res = $conn->query($sql);
    $total += (int)$res->fetch_assoc()['c'];

    $sub_folders = $conn->query("SELECT id FROM documents WHERE parent_id = $folder_id AND type = 'folder'");
    while ($sub = $sub_folders->fetch_assoc()) {
        $total += get_deep_file_count($conn, $sub['id'], $user_role);
    }
    return $total;
}

// 2. Function for the folders themselves
function get_deep_folder_count($conn, $folder_id, $user_role) {
    if (empty($user_role)) return 0;
    $total = 0;
    $sql = "SELECT id FROM documents 
            WHERE parent_id = $folder_id 
            AND type = 'folder' 
            AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";
    $res = $conn->query($sql);
    while ($sub = $res->fetch_assoc()) {
        $total++; 
        $total += get_deep_folder_count($conn, $sub['id'], $user_role);
    }
    return $total;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ Docs — Dashboard</title>
    <!-- <link rel="stylesheet" href="../css/userdashboard.css?v=1.1"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/userdashboard.css?v=1.2">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="dashboard-wrapper">
            <aside class="sidebar">
                <div class="sidebar-logo">
                    <img src="../images/image.png" alt="ISJ Logo">
                </div>
                    <button class="btn-upload" 
                        <?php if (!$can_upload) echo 'disabled style="opacity: 0.5; cursor: not-allowed;"'; ?>
                        onclick="window.location.href='admin_roles/upload_doc.php'">
                        <i class="fas <?php echo $can_upload ? 'fa-plus' : 'fa-lock'; ?>"></i> New Upload
                    </button>
                <nav class="sidebar-nav">
                    <p class="nav-label">DOCUMENTATION</p>
                    <a href="#" class="nav-item active" id="btn-all-files">
                        <i class="fas fa-th-large"></i> All Files</a>
                    <a href="#" class="nav-item" id="btn-nouveautes">
                        <i class="fas fa-bolt"></i> Recent Docs <span class="badge-new">New</span></a>
                    <a href="#" class="nav-item" id="btn-advanced-search">
                        <i class="fas fa-graduation-cap"></i> Advanced search</a>
                    <a href="javascript:void(0)" id="btn-content-search" class="nav-item">
                            <i class="fas fa-search-plus" style="color: #D4AF37;"></i>Search Within Files </a>
                    <a href="#" class="nav-item" id="btn-plan"><i class="fas fa-book-reader"></i> Plan</a>
                </nav>

                <div class="sidebar-bottom">
                    <a href="logout.php" class="nav-item logout-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                    <a href="../php/setting.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
                </div>
            </aside>

            <main class="main-content">
                    <!-- search bar header -->
                    <header class="header-top">
                            <!-- 1. Explicitly point to userdashboard.php -->
                                <form action="userdashboard.php" method="GET" class="search-container">
                                    <!-- 2. This hidden field tells the PHP to stay on 'All Files' and NOT jump to 'Advanced Search' -->
                                    <input type="hidden" name="tab" value="all_files">
                                    
                                    <i class="fas fa-search"></i>
                                    <input type="text" name="simple_search" placeholder="Search by keyword, filename, author..." 
                                        value="<?php echo htmlspecialchars($_GET['simple_search'] ?? ''); ?>">
                                    
                                    <!-- 3. Crucial: The name 'perform_search' triggers your logic at the top of the file -->
                                    <button type="submit" name="perform_search" style="display:none;"></button>
                                </form>

                                <div class="user-info">
                                    <span class="user-name"><?php echo htmlspecialchars(ucfirst($displayName)); ?></span>
                                    <div class="user-avatar" style="text-transform: uppercase;"><?php echo htmlspecialchars($initial); ?></div>
                                </div>
                    </header>

                        <!-- success message again -->
                        <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
                            <div style="background: #000; color: #fff; padding: 12px 20px; border-radius: 8px; margin-bottom: 25px; border-left: 5px solid #D4AF37; display: flex; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                <i class="fas fa-check-circle" style="margin-right: 12px; color: #D4AF37;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">Your account settings have been successfully updated.</span>
                            </div>
                        <?php endif; ?>
                       <!-- success message -->
                        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                            <div class="alert-box" 
                                style="padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; 
                                        background: #e6fffa; border: 1px solid #b2f5ea; color: #088f8f; font-family: 'Segoe UI', sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Excellent! The document has been delivered successfully.</span>
                                </div>
                                
                                <button onclick="this.parentElement.style.display='none' "style="background:none; border:none; color:inherit; cursor:pointer; font-size: 1.2rem; font-weight:bold;">&times;</button>
                            </div>
                        <?php endif; ?>
                    
                        <!-- simple search function -->
                        <div id="dashboard-default-content" style="display: <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'all_files') ? 'block' : 'none'; ?>;">
                                        <?php 
                                            /**
                                             * Logic Fix: 
                                             * 1. If 'simple_search' is in the URL, show the search results.
                                             * 2. We removed the dependency on 'perform_search' to stop the Advanced Search from hijacking this view.
                                             */
                                            $is_search_active = isset($_GET['simple_search']) && !empty(trim($_GET['simple_search']));
                                            
                                         if ($is_search_active): 
                                                $s = $conn->real_escape_string(trim($_GET['simple_search']));
                                                
                                                // Query filters for files and respects the user's specific role
                                                $search_query = "SELECT * FROM documents 
                                                                WHERE type = 'file' 
                                                                AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                                                                AND (name LIKE '%$s%' OR author LIKE '%$s%' OR description LIKE '%$s%')
                                                                ORDER BY created_at DESC";
                                                $search_results = $conn->query($search_query);
                                            ?>
                                    <!-- Search Results View -->
                                    <div class="section-title">
                                        <h2>Search Results for: "<?php echo htmlspecialchars($s); ?>"</h2>
                                        <!-- Clicking All Files or Clear Search resets the view -->
                                        <a href="userdashboard.php" style="font-size: 0.8rem; color: #D4AF37;">Back to All Files</a>
                                    </div>
                    
                                    <div class="table-card">
                                        <table class="isj-table">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Author</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                        <?php if ($search_results && $search_results->num_rows > 0): ?>
                                                            <?php while($file = $search_results->fetch_assoc()): ?>
                                                                <tr>
                                                                    <td><i class="fas fa-file-pdf pdf-red"></i> <?php echo htmlspecialchars($file['name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                                                                    <td><?php echo date('d M Y', strtotime($file['created_at'])); ?></td>
                                                                    <td class="action-buttons">
                                                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                                                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;"><i class="fas fa-download"></i></a>
                                                                    </td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        <?php else: ?>
                                                            <tr><td colspan="4" style="text-align: center; padding: 30px; color: #888;">No files found matching your search.</td></tr>
                                                        <?php endif; ?>
                                                </tbody>
                                        </table>
                                    </div>

                                   <?php else: ?>
                                        <!-- Default Folder Grid View (Shown when btn-all-files is active) -->
                                        <div class="folder-grid" style="display: flex; flex-wrap: wrap; gap: 20px; padding: 10px;">
                                        <?php
                                            // Standard query for top-level folders
                                            $sql = "SELECT * FROM documents WHERE type = 'folder' AND parent_id IS NULL AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all') ORDER BY name ASC";
                                            $result = $conn->query($sql);

                                        while($row = $result->fetch_assoc()) {
                                                $folder_id = $row['id'];
                                                $total_folders = get_deep_folder_count($conn, $folder_id, $user_role);
                                                $total_files = get_deep_file_count($conn, $folder_id, $user_role);
                                                
                                                $icon = (stripos($row['name'], 'Governance') !== false) ? "fa-university" : "fa-folder"; 
                                        ?>
                                            <div class="folder-card-new" onclick="window.location.href='view_folder.php?id=<?php echo $folder_id; ?>'" 
                                                style="background: #ffffff; border-radius: 12px; padding: 25px; display: flex; align-items: center; gap: 20px; border-left: 6px solid #D4AF37; box-shadow: 0 4px 12px rgba(0,0,0,0.08); cursor: pointer; width: 350px; min-height: 100px; transition: 0.3s;">
                                                
                                                <div style="font-size: 2.2rem; color: #061428;">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                </div>

                                                <div class="folder-info">
                                                    <h4 style="margin: 0; font-size: 1.15rem; color: #061428; font-weight: 700; font-family: sans-serif;">
                                                        <?php echo htmlspecialchars($row['name']); ?>
                                                    </h4>
                                                    <p style="margin: 6px 0 0; color: #555; font-size: 0.85rem; font-family: sans-serif;">
                                                        <span style="font-weight: 600;"><?php echo $total_folders; ?></span> Folders • 
                                                        <span style="font-weight: 600;"><?php echo $total_files; ?></span> Total Files
                                                    </p>
                                                </div>
                                            </div>
                                        <?php } ?>
                                </div>
                           <?php endif; ?>
                        </div>
                        <!-- Recent Docs section -->
                        <div id="nouveautes-section" style="display: none;">
                                        <div class="section-title">
                                            <h2><i class="fas fa-bolt" style="color: var(--gold);"></i> Recent Docs</h2>
                                        </div>
                            <div class="table-card">
                                <table class="isj-table">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Date</th>
                                                    <th>Author</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                <tbody>
                                            <?php
                                                    // Fetch the 10 most recent files allowed for this role
                                                    $recent_sql = "SELECT * FROM documents 
                                                                WHERE type = 'file' 
                                                                AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                                                                ORDER BY created_at DESC 
                                                                LIMIT 10";
                                                    $recent_res = $conn->query($recent_sql);

                                            if ($recent_res && $recent_res->num_rows > 0):
                                                        while($file = $recent_res->fetch_assoc()): ?>
                                                                        <tr>
                                                                            <td><i class="fas fa-file-pdf pdf-red"></i> <?php echo htmlspecialchars($file['name']); ?></td>
                                                                            <td><?php echo date('F d, Y g:i A', strtotime($file['created_at'])); ?></td>
                                                                            <td><?php echo htmlspecialchars($file['author'] ?? 'System Administrator'); ?></td>
                                                                            <td class="action-buttons">
                                                                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye" style="color: #4B0082;"></i> </a>
                                                                                        <a href="admin_roles/share_doc.php?id=<?php echo $file['id']; ?>" class="btn-icon"><i class="fas fa-share-alt" style="color: #D4AF37;"></i></a>
                                                                                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>"  download="<?php echo htmlspecialchars($file['name']); ?>"  class="btn-icon"> <i class="fas fa-download" style="color: #2E7D32;"></i></a>
                                                                            </td>
                                                                        </tr>
                                                        <?php endwhile; 
                                            else: ?>
                                                <tr>
                                                    <td colspan="4" style="text-align: center; padding: 30px; color: #888;">
                                                        No recent documents found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                    </tbody>
                            </table>
                            </div>
                        </div>
                        <!-- Advance search -->
                        <div id="advanced-search-section" style="display: <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'advanced') ? 'block' : 'none'; ?>;">
                            <div class="search-grid-form">
                                                <div class="section-title">
                                                    <h2><i class="fas fa-search-plus" style="color: var(--gold);"></i> Advanced Search</h2>
                                                </div>
                                
                                <form action="" method="GET" id="searchForm">
                                    <input type="hidden" name="tab" value="advanced">
                                                <div class="input-row">
                                                            <div class="field-group">
                                                                <label>Title (file)</label>
                                                                <input type="text" name="titre" class="search-input" placeholder="Enter title..." value="">
                                                            </div>
                                                            <div class="field-group">
                                                                <label>Author</label>
                                                                <input type="text" name="auteur" class="search-input" placeholder="Enter author name..." value="">
                                                            </div>
                                                </div>
                                            <div class="input-row">
                                                <div class="field-group">
                                                    <label>Specific Keywords (File Content)</label>
                                                    <input type="text" name="description" class="search-input" placeholder="e.g. 'exam', 'finance', 'report'..." value="">
                                                </div>
                                                <div class="field-group">
                                                    <label>Category / Folder</label>
                                                        <select name="folder_filter" class="search-input" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">
                                                            <option value="all">All Accessible Folders</option>
                                                            <?php 
                                                            if($folders_result && $folders_result->num_rows > 0) {
                                                                $folders_result->data_seek(0); 
                                                                while($row = $folders_result->fetch_assoc()): ?>
                                                                    <option value="<?php echo $row['id']; ?>" 
                                                                        <?php echo (isset($_GET['folder_filter']) && $_GET['folder_filter'] == $row['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($row['name']); ?>
                                                                    </option>
                                                                <?php endwhile; 
                                                            } ?>
                                                        </select>
                                                </div>
                                            </div>
                                                <div class="input-row">
                                                    <div class="field-group" style="width: 100%;">
                                                        <label>Date of Creation</label>
                                                        <input type="date" name="date_creation" class="search-input" value="">
                                                    </div>
                                                </div>

                                                <div style="text-align: center; margin-top: 15px;">
                                                    <button type="submit" name="perform_search" class="btn-submit-search">
                                                        <i class="fas fa-search"></i> Search Documents
                                                    </button>
                                                </div>
                                </form>
                            </div>

                            <?php if (isset($_GET['perform_search'])): 
                                    $titre = trim($_GET['titre'] ?? '');
                                    $auteur = trim($_GET['auteur'] ?? '');
                                    $desc = trim($_GET['description'] ?? '');
                                    $folder_id = trim($_GET['folder_filter'] ?? 'all');
                                    $date_c = trim($_GET['date_creation'] ?? '');

                                    // 1. Base Query (The starting point)
                                    $query = "SELECT * FROM documents WHERE type = 'file' AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')";

                                    // 2. Add filters ONLY if they are filled in
                                    if (!empty($titre)) { 
                                        $query .= " AND name LIKE '%" . $conn->real_escape_string($titre) . "%'"; 
                                    }
                                    
                                    if (!empty($auteur)) { 
                                        $query .= " AND author LIKE '%" . $conn->real_escape_string($auteur) . "%'"; 
                                    }
                                    
                                    if (!empty($desc)) { 
                                        $query .= " AND description LIKE '%" . $conn->real_escape_string($desc) . "%'"; 
                                    }

                                    // --- FOLDER LOGIC (Only if folder name is provided) ---
                                    if ($folder_id !== 'all' && !empty($folder_id)) {
                                            $folder_id_safe = $conn->real_escape_string($folder_id);
                                            
                                            /* 
                                            This ensures we only see files where the parent is the selected ID
                                            OR the parent is a sub-folder of that ID.
                                            */
                                            $query .= " AND (
                                                parent_id = '$folder_id_safe' 
                                                OR parent_id IN (
                                                    SELECT id FROM documents 
                                                    WHERE parent_id = '$folder_id_safe' 
                                                    AND type = 'folder'
                                                )
                                            )";
                                    }

                                    // --- DATE LOGIC (Only if date is provided) ---
                                    if (!empty($date_c)) { 
                                        $date_safe = $conn->real_escape_string($date_c);
                                        $query .= " AND DATE(created_at) = '$date_safe'"; 
                                    }

                                    $results = $conn->query($query);
                            ?>

                                <?php if (isset($results) && $results !== null): ?>
                                        <div class="table-card" style="margin-top: 30px;">
                                                    <table class="isj-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Title</th>
                                                                            <th>Author</th>
                                                                            <th>Date Created</th>
                                                                            <th>Actions</th>
                                                                        </tr>
                                                                    </thead>
                                                            <tbody>
                                                                <?php if ($results && $results->num_rows > 0): ?>
                                                                        <?php while($file = $results->fetch_assoc()): ?>
                                                                            <tr>
                                                                                <td><i class="fas fa-file-pdf pdf-red"></i> <?php echo htmlspecialchars($file['name']); ?></td>
                                                                                <td><?php echo htmlspecialchars($file['author'] ?? 'Admin'); ?></td>
                                                                                <td><?php echo date('d/m/Y', strtotime($file['created_at'])); ?></td>
                                                                                <td class="action-buttons">
                                                                                    <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                                                                                    <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" download class="btn-icon" style="color: green;"><i class="fas fa-download"></i></a>
                                                                                    <a href="admin_roles/share_doc.php?id=<?php echo $file['id']; ?>" class="btn-icon"> <i class="fas fa-share-alt" style="color: #D4AF37;"></i></a>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endwhile; ?>
                                                                        <?php else: ?>
                                                                            <tr><td colspan="4" style="text-align: center; padding: 30px; color: #888;">No matches found for your criteria.</td></tr>
                                                                        <?php endif; ?>
                                                            </tbody>
                                                    </table>
                                        </div>
                                    <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <!-- content search -->
                        <div id="content-search-section" style="display: none; padding: 20px;">
                                        <div class="search-container-heavy" style="background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; border-top: 4px solid #D4AF37;">
                                                            <h2 style="color: #061428; margin-bottom: 10px;"><i class="fas fa-file-alt"></i> Deep Content Search</h2>
                                                            <p style="color: #666; margin-bottom: 25px;">Search specifically <strong>inside</strong> the text of your documents.</p>
                                                        
                                                    <form action="" method="POST" style="max-width: 600px; margin: 0 auto; display: flex; gap: 10px;">
                                                        <input type="hidden" name="tab" value="content">
                                                        <input type="text" name="content_keyword" placeholder="Enter any word found inside files(.pdf,.docx,.txt)..." required style="flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 5px;">
                                                        <button type="submit" name="run_content_search" style="background: #061428; color: #D4AF37; padding: 0 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Scan Files</button>
                                                    </form>
                                        </div>

                                            <?php 
                                                    // 1. First Check: Only run if the button was clicked AND we are on the 'content' tab
                                                    if (isset($_POST['run_content_search']) && isset($_POST['tab']) && $_POST['tab'] == 'content'): 
                                                        
                                                        $keyword = $conn->real_escape_string($_POST['content_keyword']);
                                                        $sql = "SELECT * FROM documents WHERE type = 'file' AND file_content LIKE '%$keyword%'";
                                                        $results = $conn->query($sql);
                                            ?>

                                                <?php if (isset($results)): ?>
                                                    <div class="table-card" style="margin-top: 30px; background: white; border-radius: 10px; overflow: hidden;">
                                                        <table class="isj-table" style="width: 100%; border-collapse: collapse;">
                                                            <thead style="background: #061428; color: white;">
                                                                <tr>
                                                                    <th style="padding: 15px; text-align: left;">TITLE</th>
                                                                    <th style="padding: 15px; text-align: left;">AUTHOR</th>
                                                                    <th style="padding: 15px; text-align: left;">DATE CREATED</th>
                                                                    <th style="padding: 15px; text-align: left;">ACTIONS</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if ($results && $results->num_rows > 0): ?>
                                                                    <?php while($row = $results->fetch_assoc()): ?>
                                                                        <tr style="border-bottom: 1px solid #eee;">
                                                                            <td style="padding: 15px;"><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($row['name']); ?></td>
                                                                            <td style="padding: 15px;"><?php echo htmlspecialchars($row['author'] ?? 'Admin'); ?></td>
                                                                            <td style="padding: 15px;"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                                                            <td style="padding: 15px;">
                                                                                <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" style="margin-right: 10px; color: #061428;"><i class="fas fa-eye"></i></a>
                                                                                <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" download style="color: green;"><i class="fas fa-download"></i></a>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endwhile; ?>
                                                                <?php else: ?>
                                                                    <tr><td colspan="4" style="text-align: center; padding: 20px; color: #888;">No content matches for "<?php echo htmlspecialchars($keyword); ?>"</td></tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                        <?php endif; ?>
                        </div>
                         <!-- plan section -->
                        <div id="plan-section" style="display: none;">
                            <div class="section-title">
                                <h2><i class="fas fa-book-reader" style="color: var(--gold);"></i> Organizational Plan</h2>
                                <p style="color: #666; font-size: 0.9rem;">Hierarchy for role: <strong><?php echo ucfirst($user_role); ?></strong></p>
                            </div>

                            <div class="tree-card">
                                <?php
                                // Function to render the tree specifically for the authorized role
                                if (!function_exists('renderRoleTree')) {
                                    function renderRoleTree($conn, $user_role, $parent_id = NULL) {
                                        // Select only folders authorized for this role
                                        $sql = "SELECT * FROM documents 
                                                WHERE type = 'folder' 
                                                AND (parent_id " . ($parent_id === NULL ? "IS NULL" : "= $parent_id") . ") 
                                                AND (FIND_IN_SET('$user_role', viewed_by) OR viewed_by = 'all')
                                                ORDER BY name ASC";
                                        
                                        $result = $conn->query($sql);

                                        if ($result && $result->num_rows > 0) {
                                            echo '<ul class="folder-tree" style="list-style: none; padding-left: 20px; border-left: 1px dashed #ccc; margin: 10px 0;">';
                                            while ($folder = $result->fetch_assoc()) {
                                                $id = $folder['id'];
                                                echo '<li style="margin: 8px 0;">';
                                                echo '<div class="tree-item" onclick="window.location.href=\'view_folder.php?id=' . $id . '\'" style="cursor: pointer; display: flex; align-items: center;">';
                                                echo '<i class="fas fa-folder" style="color: #D4AF37; margin-right: 10px;"></i>';
                                                echo '<span class="folder-name">' . htmlspecialchars($folder['name']) . '</span>';
                                                echo '</div>';
                                                
                                                // Recursive call to find authorized subfolders
                                                renderRoleTree($conn, $user_role, $id);
                                                
                                                echo '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                    }
                                }

                                // Execution of the role-specific tree
                                renderRoleTree($conn, $user_role);?>
                            </div>
                        </div>
            </main>
</div>

<script>

// If the page is reloaded (refreshed), clear the URL and go home
    if (performance.navigation.type === performance.navigation.TYPE_RELOAD) {
        window.location.href = "userdashboard.php"; 
    }
// 1. Keep your validation function here
function validateSearch() {
    const inputs = document.querySelectorAll('.search-input');
    let allEmpty = true;

    inputs.forEach(input => {
        if (input.value.trim() !== "") {
            allEmpty = false;
            input.style.borderColor = "#ddd"; 
        }
    });

    if (allEmpty) {
        alert("Please enter at least one search criteria!");
        inputs.forEach(input => {
            input.style.borderColor = "red"; 
        });
        return false; 
    }
    return true; 
}

function toggleSection(sectionId) {
    // Hide other sections first
    const sections = ['advanced-search-section', 'all-files-section', 'content-search-section'];
    sections.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // Show the target section
    const target = document.getElementById(sectionId);
    if (target) {
        target.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // --- 1. SELECTORS ---
    const nouveautesBtn = document.getElementById('btn-nouveautes');
    const allFilesBtn = document.getElementById('btn-all-files');
    const searchBtn = document.getElementById('btn-advanced-search');
    const planBtn = document.getElementById('btn-plan');
    const contentSearchBtn = document.getElementById('btn-content-search');

    const nouveautesSection = document.getElementById('nouveautes-section');
    const defaultContent = document.getElementById('dashboard-default-content');
    const searchSection = document.getElementById('advanced-search-section');
    const planSection = document.getElementById('plan-section');
    const contentSearchSection = document.getElementById('content-search-section');
    const topSearchBar = document.querySelector('.search-container');

    // --- 2. DATA & PARAMETERS (CLEANED UP) ---
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');

    // Bridge PHP data to JavaScript
    const postTab = "<?php echo (isset($_POST['tab'])) ? $_POST['tab'] : ''; ?>";
    const isPostSearch = "<?php echo (isset($_POST['run_content_search'])) ? 'content' : ''; ?>";
    const isGetSearch = urlParams.has('perform_search') ? 'advanced' : '';
    
    // Detect UI elements
    const resultsTable = document.querySelector('#content-search-section table');
    const isContentSearchActive = document.querySelector('input[name="run_content_search"]');

    // --- 3. UTILITY FUNCTIONS ---
    function hideAllSections() {
        const sections = [nouveautesSection, defaultContent, searchSection, planSection, contentSearchSection];
        sections.forEach(sec => { if(sec) sec.style.display = 'none'; });
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
    }

    // --- 4. INITIAL LOAD LOGIC ---
    hideAllSections(); 

    // PRIORITY 1: All Files (The Home State)
    if (tabParam === 'all_files') {
        if (defaultContent) defaultContent.style.display = 'block';
        if (allFilesBtn) allFilesBtn.classList.add('active');
        if (topSearchBar) topSearchBar.style.display = 'flex';
    } 
    // PRIORITY 2: Content Search
    else if (tabParam === 'content' || isPostSearch === 'content' || postTab === 'content' || resultsTable || isContentSearchActive) {
        if (contentSearchSection) contentSearchSection.style.display = 'block';
        if (contentSearchBtn) contentSearchBtn.classList.add('active');
        if (topSearchBar) topSearchBar.style.display = 'none';
    }
    // PRIORITY 3: Advanced Search
    else if (tabParam === 'advanced' || isGetSearch === 'advanced') {
        if (searchSection) searchSection.style.display = 'block';
        if (searchBtn) searchBtn.classList.add('active');
        if (topSearchBar) topSearchBar.style.display = 'none';
    }
    // DEFAULT: Fallback to Home
    else {
        if (defaultContent) defaultContent.style.display = 'block';
        if (allFilesBtn) allFilesBtn.classList.add('active');
        if (topSearchBar) topSearchBar.style.display = 'flex';
    }
    // --- 5. EVENT LISTENERS ---

    searchBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // If we are currently in "all_files" mode, redirect to clean page
        if (urlParams.get('tab') === 'all_files') {
            window.location.href = "userdashboard.php?tab=advanced";
        } else {
            hideAllSections();
            if (searchSection) searchSection.style.display = 'block';
            if (searchBtn) searchBtn.classList.add('active');
            if (topSearchBar) topSearchBar.style.display = 'none';
        }
    });

    allFilesBtn.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllSections();
        if (defaultContent) defaultContent.style.display = 'block';
        if (topSearchBar) topSearchBar.style.display = 'flex';
        if (allFilesBtn) allFilesBtn.classList.add('active');
    });

    nouveautesBtn.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllSections();
        if (nouveautesSection) nouveautesSection.style.display = 'block';
        if (topSearchBar) topSearchBar.style.display = 'none';
        if (nouveautesBtn) nouveautesBtn.classList.add('active');
    });

    planBtn.addEventListener('click', (e) => {
        e.preventDefault();
        hideAllSections();
        if (planSection) planSection.style.display = 'block';
        if (topSearchBar) topSearchBar.style.display = 'none';
        if (planBtn) planBtn.classList.add('active');
    });

    if (contentSearchBtn) {
        contentSearchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            hideAllSections();
            if (contentSearchSection) contentSearchSection.style.display = 'block';
            if (topSearchBar) topSearchBar.style.display = 'none';
            contentSearchBtn.classList.add('active');
        });
    }
});
</script>
<script src="../js/responsive.js"></script>
</body>
</html>