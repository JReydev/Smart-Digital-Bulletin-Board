<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$news_id = $_POST['news_id'] ?? null;

if (!$news_id) {
    echo json_encode(['success' => false, 'message' => 'News item ID not specified']);
    exit;
}

try {
    $news_id = intval($news_id);
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role_id'] ?? 0;
    
    // Only admin can restore marquee items
    if ($user_role != 1) {
        throw new Exception('You do not have permission to restore marquee items. Only admins can restore archived items.');
    }
    
    // Check if news item exists and is archived
    $stmt = $conn->prepare("SELECT id FROM news_update WHERE id = ? AND is_archived = 1");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $news_id);
    if (!$stmt->execute()) {
        throw new Exception('Error fetching news item: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $news = $result->fetch_assoc();
    $stmt->close();
    
    if (!$news) {
        throw new Exception('Archived news item not found');
    }

    // Restore the news item
    $stmt = $conn->prepare("UPDATE news_update SET is_archived = 0 WHERE id = ?");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $news_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error restoring news item: ' . $stmt->error);
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Marquee item restored successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>
