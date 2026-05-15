<?php
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    // Store the requested URL for potential redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    //header("Location: /modules/login/login.php");
    header("Location: /SmartBulletin/modules/login/login.php");
    exit();
}
?> 