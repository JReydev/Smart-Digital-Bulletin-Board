<?php
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = intval($_POST['id']);
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role_id'];
        
        // Start transaction
        $conn->begin_transaction();

        // Get announcement details and check permissions
        $stmt = $conn->prepare("SELECT created_by FROM announcements WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception('Error fetching announcement: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $announcement = $result->fetch_assoc();
        $stmt->close();
        
        if (!$announcement) {
            throw new Exception('Announcement not found');
        }
        
        // Check permissions: only creator or admin (role_id = 1) can archive
        if ($announcement['created_by'] != $user_id && $user_role != 1) {
            http_response_code(403);
            throw new Exception('You do not have permission to archive this announcement. Only the creator or an admin can archive it.');
        }

        // Archive announcement instead of deleting
        $stmt = $conn->prepare("UPDATE announcements SET is_archived = 1 WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception('Error archiving announcement: ' . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
        echo "Archived successfully";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        http_response_code(500);
        echo $e->getMessage();
    }
} else {
    http_response_code(400);
    echo "Invalid request";
}
