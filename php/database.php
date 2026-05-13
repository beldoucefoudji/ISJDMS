<?php
// 1. Get the values (locally or from Render)
$host = getenv('DB_HOST') ?: 'mysql-32d8df3-beldoucefoudji-2066.h.aivencloud.com';
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS') ?: 'YOUR_ACTUAL_PASSWORD_HERE';
$dbname = getenv('DB_NAME') ?: 'defaultdb';
$port = getenv('DB_PORT') ?: 22007; // Use the number directly here

// 2. Initialize and set SSL
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL); 

// 3. Connect (Notice the (int) before $port to fix the error!)
mysqli_real_connect($conn, $host, $user, $pass, $dbname, (int)$port);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}
?>