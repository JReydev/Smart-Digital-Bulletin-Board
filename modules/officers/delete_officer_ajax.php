<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include '../../database/connect.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$officer_id = $_POST['officer_id'] ?? null;

if (!$officer_id) {
    echo json_encode(['success' => false, 'message' => 'Officer ID not specified']);
    exit;
}

try {
    $id = intval($officer_id);
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role_id'] ?? 0;
    
    // Get officer details
    $stmt = $conn->prepare("SELECT partylist_id FROM officers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $officer = $result->fetch_assoc();
    $stmt->close();
    
    if (!$officer) {
        throw new Exception('Officer not found');
    }
    
    $partylist_id = $officer['partylist_id'];
    
    // Check permissions: only admin (role_id = 1) can delete officers
    if ($user_role != 1) {
        throw new Exception('You do not have permission to delete this officer. Only admins can delete officers.');
    }

    // Get officer multimedia_id to delete associated image
    $stmt = $conn->prepare("SELECT multimedia_id FROM officers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $officer_data = $result->fetch_assoc();
    $multimedia_id = $officer_data['multimedia_id'] ?? null;
    $stmt->close();
    
    // Delete officer from database
    $stmt = $conn->prepare("DELETE FROM officers WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete associated multimedia record and file if it exists
        if ($multimedia_id) {
            $stmt = $conn->prepare("SELECT file_path FROM multimedia_content WHERE id = ?");
            $stmt->bind_param("i", $multimedia_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $media = $result->fetch_assoc();
            $stmt->close();
            
            if ($media && !empty($media['file_path'])) {
                $image_path = __DIR__ . '/../../' . $media['file_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete multimedia record
            $stmt = $conn->prepare("DELETE FROM multimedia_content WHERE id = ?");
            $stmt->bind_param("i", $multimedia_id);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Officer deleted successfully',
            'partylist_id' => $partylist_id
        ]);
    } else {
        throw new Exception('Error deleting officer: ' . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>

