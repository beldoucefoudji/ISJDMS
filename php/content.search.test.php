<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

if(isset($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    
    // This query looks at the filename AND the hidden file content
    $sql = "SELECT id, name, type FROM documents 
            WHERE name LIKE '%$keyword%' 
            OR file_content LIKE '%$keyword%'";
            
    $result = $conn->query($sql);
    
    echo "<h2>Search results for: " . htmlspecialchars($keyword) . "</h2>";
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "Found: <b>" . $row['name'] . "</b> (" . $row['type'] . ")<br>";
        }
    } else {
        echo "No documents contain this word.";
    }
}
?>

<form method="GET">
    <input type="text" name="keyword" placeholder="Search inside files...">
    <button type="submit">Search</button>
</form>
