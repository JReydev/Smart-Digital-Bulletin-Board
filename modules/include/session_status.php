<?php
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$isValid = false;
$hasSession = false;

// Check if there's a session with user_id
if (isset($_SESSION['user_id'])) {
    $hasSession = true;
    // Use require_once to avoid closing connection if it's already open
    if (!isset($conn)) {
        require_once __DIR__ . '/../../database/connect.php';
    }
    $userId = $_SESSION['user_id'];
    $currentSessionId = session_id();
    if ($conn) {
        if ($stmt = $conn->prepare("SELECT id FROM user_sessions WHERE user_id = ? AND session_id = ? LIMIT 1")) {
            $stmt->bind_param("is", $userId, $currentSessionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $isValid = ($result && $result->num_rows === 1);
            $stmt->close();
        }
        // Update last_activity to keep session fresh
        if ($isValid && ($stmt = $conn->prepare("UPDATE user_sessions SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ? AND session_id = ?"))) {
            $stmt->bind_param("is", $userId, $currentSessionId);
            $stmt->execute();
            $stmt->close();
        }
        // Don't close connection here - let auth_required.php handle it if needed
        // $conn->close();
    }
}

http_response_code(200);
// Return both valid status and whether a session exists
// This allows the client to distinguish between "no session" and "session invalidated"
echo json_encode([
    'valid' => $isValid,
    'hasSession' => $hasSession
]);
exit();
?>
