<?php
// This file should be included at the top of all pages that require authentication
session_start();

// Prevent caching to stop back button from accessing protected pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in and session is valid
if (!isset($_SESSION['user_id'])) {
    // Store the requested URL for potential redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header("Location: /SmartBulletin/modules/login/login.php");
    exit();
}

// Verify session in database
include __DIR__ . '/../../database/connect.php';
$stmt = $conn->prepare("SELECT id FROM user_sessions WHERE user_id = ? AND session_id = ?");
$userId = $_SESSION['user_id'];
$currentSessionId = session_id();
$stmt->bind_param("is", $userId, $currentSessionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Session not found in database - invalidate current session
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: /SmartBulletin/modules/login/login.php");
    exit();
}

// Ensure role_id is in session (for backward compatibility with existing sessions)
if (!isset($_SESSION['role_id'])) {
    $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user_result = $stmt->get_result();
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $_SESSION['role_id'] = $user_data['role_id'];
    }
}

// Update last activity timestamp
$stmt = $conn->prepare("UPDATE user_sessions SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ? AND session_id = ?");
$stmt->bind_param("is", $userId, $currentSessionId);
$stmt->execute();
?> 