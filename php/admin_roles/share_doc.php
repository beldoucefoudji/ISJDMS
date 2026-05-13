<?php
session_start();
include("../database.php");

// 1. ALLOW ALL LOGGED-IN ROLES
// Access is granted as long as a session user_id exists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Identify the role for dynamic redirection
$user_role = $_SESSION['role'] ?? '';

if (!isset($_GET['id'])) {
    // Redirect back to the correct dashboard if the document ID is missing
    if ($user_role === 'admin') {
        header("Location: ../admindashboard.php?tab=docs");
    } else {
        header("Location: ../userdashboard.php");
    }
    exit();
}

$doc_id = (int)$_GET['id'];
if ($user_role === 'admin') {
    $query = $conn->prepare("SELECT name FROM documents WHERE id = ? AND type = 'file'");
    $query->bind_param("i", $doc_id);
} else {
    $query = $conn->prepare("SELECT name FROM documents WHERE id = ? AND type = 'file' AND (FIND_IN_SET(?, viewed_by) OR viewed_by = 'all')");
    $query->bind_param("is", $doc_id, $user_role);
}
$query->execute();
$doc = $query->get_result()->fetch_assoc();

if (!$doc) {
    http_response_code(403);
    echo "Document not found or access denied.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Document — ISJ Docs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/responsive.css">
    <style>
        :root { --isj-blue: #061428; --isj-gold: #D4AF37; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .share-box { background: var(--isj-blue); color: white; padding: 40px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); width: 100%; max-width: 400px; border-top: 4px solid var(--isj-gold); }
        h2 { color: var(--isj-gold); margin-top: 0; }
        .doc-name { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px; margin-bottom: 20px; font-size: 0.95rem; display: flex; align-items: center; gap: 10px; }
        label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #BDC3C7; }
        input, textarea { width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 6px; background: #1a2a44; color: white; border: 1px solid #2c3e50; box-sizing: border-box;}
        input:focus { outline: none; border-color: var(--isj-gold); }
        .btn-send { background: var(--isj-gold); color: var(--isj-blue); border: none; padding: 14px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-send:hover { background: #c5a02c; transform: translateY(-2px); }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #BDC3C7; text-decoration: none; font-size: 0.85rem; cursor: pointer; }
        .cancel-link:hover { color: white; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="share-box">
        <h2><i class="fas fa-paper-plane"></i> Share File</h2>
        <div class="doc-name">
            <i class="fas fa-file-pdf" style="color: #ff4d4d;"></i> 
            <span><?php echo htmlspecialchars($doc['name']); ?></span>
        </div>
        
        <form action="process_share.php" method="POST">
            <input type="hidden" name="doc_id" value="<?php echo $doc_id; ?>">
            
            <label>Recipient Email</label>
            <input type="email" name="recipient_email" placeholder="example@isj.com" required>

            <label>Message (Optional)</label>
            <textarea name="message" rows="3" placeholder="Hello, please check this document..."></textarea>

            <button type="submit" class="btn-send" id="sendBtn">
                <span id="btnText">Send via Email</span>
                <span id="loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Sending...</span>
            </button>

            <a href="<?php echo ($user_role === 'admin') ? '../admindashboard.php?tab=docs' : '../userdashboard.php'; ?>" class="cancel-link">Cancel</a>
        </form>
    </div>

<script>
    document.querySelector('form').onsubmit = function() {
        document.getElementById('btnText').style.display = 'none';
        document.getElementById('loader').style.display = 'inline-block';
        document.getElementById('sendBtn').disabled = true;
        document.getElementById('sendBtn').style.opacity = '0.7';
    };
</script>
<script src="../../js/responsive.js"></script>
</body>
</html>
