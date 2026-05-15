<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the announcement ID and new media order
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['announcement_id']) || !isset($data['media_order'])) {
            throw new Exception('Missing required parameters');
        }

        $announcement_id = intval($data['announcement_id']);
        $media_order = $data['media_order'];

        // Verify announcement exists and user has permission
        $check = $conn->prepare("SELECT created_by FROM announcements WHERE id = ?");
        if ($check === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        $check->bind_param("i", $announcement_id);
        if (!$check->execute()) {
            throw new Exception('Error checking announcement: ' . $check->error);
        }
        $check->bind_result($created_by);
        if (!$check->fetch()) {
            throw new Exception('Announcement not found');
        }
        $check->close();

        // Only allow reordering if user is the creator or an admin
        $user_role = $_SESSION['role_id'] ?? 0;
        $is_creator = ($created_by === $_SESSION['user_id']);
        $is_admin = ($user_role == 1);
        
        if (!$is_creator && !$is_admin) {
            throw new Exception('You do not have permission to modify this announcement');
        }

        // Verify all media IDs belong to this announcement
        $verify_query = "SELECT COUNT(*) as count FROM announcement_media WHERE announcement_id = ? AND media_id IN (" . implode(',', array_map('intval', $media_order)) . ")";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("i", $announcement_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result()->fetch_assoc();
        
        if ($result['count'] !== count($media_order)) {
            throw new Exception('Invalid media IDs provided');
        }

        // Start transaction
        $conn->begin_transaction();

        // Update the display order for each media item
        $update_stmt = $conn->prepare("UPDATE announcement_media SET display_order = ? WHERE announcement_id = ? AND media_id = ?");
        
        foreach ($media_order as $index => $media_id) {
            $update_stmt->bind_param("iii", $index, $announcement_id, $media_id);
            if (!$update_stmt->execute()) {
                throw new Exception('Failed to update media order');
            }
        }

        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
}
?> 