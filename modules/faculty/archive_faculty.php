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
    
    // Get faculty details
    $stmt = $conn->prepare("SELECT user_id FROM faculty WHERE id = ?");
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
        throw new Exception('Faculty member not found');
    }
    
    // Check permissions: only admin (role_id = 1) or the faculty member themselves can archive
    $is_admin = ($user_role == 1);
    $is_own_profile = ($faculty['user_id'] && $faculty['user_id'] == $user_id);
    
    if (!$is_admin && !$is_own_profile) {
        throw new Exception('You do not have permission to archive this faculty member. Only admins or the faculty member themselves can archive.');
    }

    // Archive faculty member
    $stmt = $conn->prepare("UPDATE faculty SET is_archived = 1 WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $faculty_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error archiving faculty: ' . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Faculty member archived successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>

