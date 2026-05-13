<?php
session_start();
include("database.php");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

$term = "Cameroon2026"; // Change this to the unique word in your text file

$sql = "SELECT name, author FROM documents 
        WHERE file_content LIKE '%$term%'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h3>System Success!</h3>";
    while($row = $result->fetch_assoc()) {
        echo "The word was found inside the document: <b>" . $row['name'] . "</b> uploaded by " . $row['author'];
    }
} else {
    echo "<h3>Search Failed</h3>";
    echo "The system could not find that word inside any file content.";
}
?>
