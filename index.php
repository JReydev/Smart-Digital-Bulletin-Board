<?php
session_start();

// If user is already logged in, redirect to homepage directly
if (isset($_SESSION['user_id'])) {
    header("Location: /SmartBulletin/modules/homepage.php");
    exit();
}

// If not logged in, redirect to login page (without destroying session)
header("Location: /SmartBulletin/modules/login/login.php");
?>