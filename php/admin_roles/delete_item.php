<?php
session_start();
include("../database.php");

// Security: Only Admin should be able to delete items
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. Fetch item details to know the type and path before deleting
    $stmt = $conn->prepare("SELECT type, file_path FROM documents WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $type = $item['type'];
        $path = $item['file_path'];

        // 2. Physical File Deletion logic
        if ($type === 'file' && !empty($path)) {
            // Check if file exists on server and delete it
            if (file_exists($path)) {
                unlink($path); 
            }
        } 
        
        // 3. Folder logic (If you want to prevent deleting non-empty folders)
        if ($type === 'folder') {
            // Check if folder has children in DB
            $checkChildren = $conn->prepare("SELECT id FROM documents WHERE parent_id = ?");
            $checkChildren->bind_param("i", $id);
            $checkChildren->execute();
            if ($checkChildren->get_result()->num_rows > 0) {
                // Redirect with error: Folder not empty
                header("Location: ../admindashboard.php?error=folder_not_empty");
                exit();
            }
        }

        // 4. Delete from Database
        $deleteStmt = $conn->prepare("SELECT id FROM documents WHERE id = ?"); // Safety check
        $deleteQuery = $conn->prepare("DELETE FROM documents WHERE id = ?");
        $deleteQuery->bind_param("i", $id);

        if ($deleteQuery->execute()) {
            header("Location: ../admindashboard.php?msg=deleted");
            exit();
        } else {
            header("Location: ../admindashboard.php?error=db_error");
            exit();
        }

    } else {
        header("Location: ../admindashboard.php?error=not_found");
        exit();
    }
} else {
    header("Location: ../admindashboard.php");
    exit();
}
?>
