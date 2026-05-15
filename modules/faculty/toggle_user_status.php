<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

$faculty_id = $_GET['id'] ?? null;

if (!$faculty_id) {
    header("Location: faculty.php");
    exit;
}

// Get faculty information with user details
$stmt = $conn->prepare("SELECT f.*, u.id as user_id, u.username, u.is_active FROM faculty f LEFT JOIN users u ON f.user_id = u.id WHERE f.id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();

if (!$faculty || !$faculty['user_id']) {
    $_SESSION['error'] = 'Faculty member does not have a user account';
    header("Location: faculty.php");
    exit;
}

// Toggle user status
$new_status = $faculty['is_active'] ? 0 : 1;
$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $new_status, $faculty['user_id']);
$stmt->execute();

$status_text = $new_status ? 'activated' : 'deactivated';
$_SESSION['success'] = "User account for {$faculty['name']} has been {$status_text}";

header("Location: faculty.php");
exit;
