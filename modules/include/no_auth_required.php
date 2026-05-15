<?php
// This file should be included at the top of login/signup pages
session_start();

// Prevent caching to stop back button from accessing login pages after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// If user is already logged in, redirect to homepage
if (isset($_SESSION['user_id'])) {
    header("Location: /SmartBulletin/modules/homepage.php");
    exit();
}
?> 