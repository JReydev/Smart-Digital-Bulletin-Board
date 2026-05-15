<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Announcement ID required']);
    exit;
}

$announcement_id = (int)$_GET['id'];

try {
    // Get media for this announcement
    $query = "SELECT mc.id as media_id, mc.file_path 
              FROM multimedia_content mc 
              JOIN announcement_media am ON mc.id = am.media_id 
              WHERE am.announcement_id = ? 
              ORDER BY am.display_order";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $media = [];
    while ($row = $result->fetch_assoc()) {
        $full_path = __DIR__ . '/../../multimedia/' . $row['file_path'];
        $mime_type = file_exists($full_path) ? mime_content_type($full_path) : 'unknown';
        
        $media[] = [
            'media_id' => $row['media_id'],
            'file_path' => $row['file_path'],
            'file_name' => basename($row['file_path']),
            'type' => $mime_type
        ];
    }
    
    // Log for debugging
    error_log("Media query for announcement $announcement_id returned " . count($media) . " items");
    
    echo json_encode([
        'success' => true,
        'media' => $media,
        'debug' => [
            'announcement_id' => $announcement_id,
            'count' => count($media)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching media: ' . $e->getMessage()
    ]);
}
?>
