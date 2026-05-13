<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

echo "<h2 style='font-family: Arial; border-bottom: 2px solid #000; padding-bottom: 10px;'>ISJ Docs - Advanced Content Search</h2>";
echo '<form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="q" placeholder="Search inside files..." value="'.(isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '').'" required style="padding: 10px; width: 300px; border: 1px solid #000;">
        <button type="submit" style="padding: 10px 20px; background: #000; color: #fff; border: none; cursor: pointer;">Search Content</button>
      </form>';

if (isset($_GET['q'])) {
    $q = mysqli_real_escape_string($conn, $_GET['q']);
    
    $sql = "SELECT id, name, author, type, file_path, created_at FROM documents 
            WHERE file_content LIKE '%$q%' 
            OR name LIKE '%$q%'";
            
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<p><strong>Found " . $result->num_rows . " Result(s)</strong></p>";
        echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse; font-family: Segoe UI; text-align: left;'>
                <thead style='background: #061428; color: #D4AF37;'>
                    <tr>
                        <th>Document Name</th>
                        <th>Author</th>
                        <th>Upload Date</th>
                        <th>File Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>";

        while($row = $result->fetch_assoc()) {
            $date = date("d M Y", strtotime($row['created_at']));
            $filePath = "../" . $row['file_path'];
            $extension = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));

            // --- FILE TYPE DETECTION LOGIC ---
            $typeIcon = "fa-file"; // Default
            $typeColor = "#666";
            $typeName = "FILE";

            if ($extension == 'pdf') {
                $typeIcon = "fa-file-pdf";
                $typeColor = "#e74c3c"; // Red for PDF
                $typeName = "PDF";
            } elseif ($extension == 'docx' || $extension == 'doc') {
                $typeIcon = "fa-file-word";
                $typeColor = "#2b579a"; // Blue for Word
                $typeName = "WORD";
            } elseif ($extension == 'txt') {
                $typeIcon = "fa-file-alt";
                $typeColor = "#333"; // Black for Text
                $typeName = "TEXT";
            }

            echo "<tr>
                    <td><i class='fas $typeIcon' style='color: $typeColor; margin-right: 8px;'></i> " . htmlspecialchars($row['name']) . "</td>
                    <td>" . htmlspecialchars($row['author']) . "</td>
                    <td>" . $date . "</td>
                    <td><span style='color: $typeColor; font-size: 0.75rem; font-weight: bold;'>$typeName</span></td>
                    <td>
                        <a href='$filePath' target='_blank' title='View' style='color: #000; margin-right: 12px;'><i class='fas fa-eye'></i></a>
                        <a href='$filePath' download title='Download' style='color: #061428; margin-right: 12px;'><i class='fas fa-download'></i></a>
                        <a href='share.php?id=" . $row['id'] . "' title='Share' style='color: #888;'><i class='fas fa-share-alt'></i></a>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p style='color:red;'>No documents found containing: <b>$q</b></p>";
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
