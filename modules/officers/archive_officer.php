<?php
// Start output buffering to prevent any accidental output
ob_start();

include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

// If headers were already sent (e.g., redirect from auth_required), we can't send JSON
if (headers_sent()) {
    ob_end_clean();
    // If we got here, it means auth passed but something else sent output
    // This shouldn't happen, but handle it gracefully
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: Headers already sent']);
    exit;
}

// Clear any output that might have been generated
ob_clean();

// Set JSON header after authentication
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    ob_end_flush();
    exit;
}

$officer_id = $_POST['officer_id'] ?? null;

if (!$officer_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Officer ID not specified']);
    ob_end_flush();
    exit;
}

try {
    $officer_id = intval($officer_id);
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role_id'] ?? 0;
    
    // Get officer details
    $stmt = $conn->prepare("SELECT id FROM officers WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $officer_id);
    if (!$stmt->execute()) {
        throw new Exception('Error fetching officer: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $officer = $result->fetch_assoc();
    $stmt->close();
    
    if (!$officer) {
        throw new Exception('Officer not found');
    }
    
    // Check permissions: only admin (role_id = 1) can archive officers
    $is_admin = ($user_role == 1);
    
    if (!$is_admin) {
        throw new Exception('You do not have permission to archive officers. Only admins can archive.');
    }

    // Archive officer
    $stmt = $conn->prepare("UPDATE officers SET is_archived = 1 WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $officer_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error archiving officer: ' . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Officer archived successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}

// End output buffering and send output
ob_end_flush();
exit;
