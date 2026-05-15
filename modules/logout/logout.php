<?php
session_start();

// Add cache control headers to prevent back button access
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Remove session from database if user is logged in
if (isset($_SESSION['user_id'])) {
    include __DIR__ . '/../../database/connect.php';
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?");
    $stmt->bind_param("is", $_SESSION['user_id'], session_id());
    $stmt->execute();
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../login/login.php");
exit;
?>
