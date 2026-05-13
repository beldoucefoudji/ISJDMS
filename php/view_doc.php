<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_role = $_SESSION['role'] ?? '';

if ($user_role === 'admin') {
    $stmt = $conn->prepare("SELECT file_path, name FROM documents WHERE id = ? AND type = 'file'");
    $stmt->bind_param("i", $id);
} else {
    $stmt = $conn->prepare("SELECT file_path, name FROM documents WHERE id = ? AND type = 'file' AND (FIND_IN_SET(?, viewed_by) OR viewed_by = 'all')");
    $stmt->bind_param("is", $id, $user_role);
}

$stmt->execute();
$res = $stmt->get_result();

if($row = $res->fetch_assoc()) {
    $file = "../" . $row['file_path'];
    
    if (file_exists($file)) {
        // These headers tell the browser (and IDM) to VIEW, not download
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($row['name']) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        readfile($file);
        exit;
    }
}
http_response_code(404);
echo "File not found or access denied.";
?>
