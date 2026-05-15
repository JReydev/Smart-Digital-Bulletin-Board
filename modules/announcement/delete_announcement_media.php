<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/media_utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['announcement_id']) || !isset($_POST['media_id'])) {
    echo json_encode(['success' => false, 'message' => 'Announcement ID and Media ID required']);
    exit;
}

$announcement_id = (int)$_POST['announcement_id'];
$media_id = (int)$_POST['media_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Delete from announcement_media table
    $delete_announcement_media = "DELETE FROM announcement_media WHERE announcement_id = ? AND media_id = ?";
    $delete_stmt = $conn->prepare($delete_announcement_media);
    $delete_stmt->bind_param("ii", $announcement_id, $media_id);
    $delete_stmt->execute();
    
    // Delete the media file and record
    deleteMedia($conn, $media_id);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Media deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting media: ' . $e->getMessage()
    ]);
}
?>
