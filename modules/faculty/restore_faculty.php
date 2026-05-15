<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$faculty_id = $_POST['faculty_id'] ?? null;

if (!$faculty_id) {
    echo json_encode(['success' => false, 'message' => 'Faculty ID not specified']);
    exit;
}

try {
    $faculty_id = intval($faculty_id);
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role_id'] ?? 0;
    
    // Only admin can restore faculty
    if ($user_role != 1) {
        throw new Exception('You do not have permission to restore faculty members. Only admins can restore archived faculty.');
    }
    
    // Check if faculty exists and is archived
    $stmt = $conn->prepare("SELECT id FROM faculty WHERE id = ? AND is_archived = 1");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $faculty_id);
    if (!$stmt->execute()) {
        throw new Exception('Error fetching faculty: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $faculty = $result->fetch_assoc();
    $stmt->close();
    
    if (!$faculty) {
        throw new Exception('Archived faculty member not found');
    }

    // Restore the faculty member
    $stmt = $conn->prepare("UPDATE faculty SET is_archived = 0 WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $faculty_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error restoring faculty: ' . $stmt->error);
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Faculty member restored successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>
