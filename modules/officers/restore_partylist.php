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

$partylist_id = $_POST['partylist_id'] ?? null;

if (!$partylist_id) {
    echo json_encode(['success' => false, 'message' => 'Partylist ID not specified']);
    exit;
}

try {
    $partylist_id = intval($partylist_id);
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role_id'] ?? 0;
    
    // Only admin can restore partylists
    if ($user_role != 1) {
        throw new Exception('You do not have permission to restore partylists. Only admins can restore archived partylists.');
    }
    
    // Check if partylist exists and is archived
    $stmt = $conn->prepare("SELECT id FROM partylist WHERE id = ? AND is_archived = 1");
    if ($stmt === false) {
        throw new Exception('MySQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $partylist_id);
    if (!$stmt->execute()) {
        throw new Exception('Error fetching partylist: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $partylist = $result->fetch_assoc();
    $stmt->close();
    
    if (!$partylist) {
        throw new Exception('Archived partylist not found');
    }

    // Restore the partylist and all associated officers in a transaction
    $conn->begin_transaction();
    
    try {
        // Restore partylist
        $stmt = $conn->prepare("UPDATE partylist SET is_archived = 0 WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $partylist_id);
        if (!$stmt->execute()) {
            throw new Exception('Error restoring partylist: ' . $stmt->error);
        }
        $stmt->close();
        
        // Restore all officers in this partylist
        $stmt = $conn->prepare("UPDATE officers SET is_archived = 0 WHERE partylist_id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $partylist_id);
        if (!$stmt->execute()) {
            throw new Exception('Error restoring officers: ' . $stmt->error);
        }
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Partylist and associated officers restored successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>
