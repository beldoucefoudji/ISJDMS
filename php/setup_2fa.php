<?php
session_start();
// Verify this path matches your folder structure: php/phpGangsta/
require_once 'phpGangsta/GoogleAuthenticator.php';
include("database.php");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

$ga = new PHPGangsta_GoogleAuthenticator();

// 1. Generate a unique "Secret Key"
$secret = $ga->createSecret(); 

// 2. Create the QR Code URL (This tells the App who you are)
$qrCodeUrl = $ga->getQRCodeGoogleUrl('ISJDMS_Admin', 'admin@isj.cm', $secret);

echo "<h2><i class='fas fa-shield-alt'></i> Admin 2FA Setup</h2>";
echo "<p>1. Open the <strong>Google Authenticator</strong> app on your phone.</p>";
echo "<p>2. Scan the QR code below:</p>";

// Display the QR Code image
echo '<img src="'.$qrCodeUrl.'" style="border: 10px solid white; box-shadow: 0 0 15px rgba(0,0,0,0.2); margin: 20px 0;">';

echo "<p>3. Once scanned, your phone will show a 6-digit code for <strong>ISJDMS_Admin</strong>.</p>";
echo "<hr>";
echo "<p style='color: red;'><strong>CRITICAL:</strong> Save this Secret Key in your database for the Admin user:</p>";
echo "<code style='background: #eee; padding: 10px; font-size: 1.5rem; display: inline-block;'>$secret</code>";
?>
