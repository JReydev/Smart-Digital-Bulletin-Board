<?php
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

if (isset($_GET['id'])) {
    try {
        $partylist_id = intval($_GET['id']);
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role_id'];
        
        // Check permissions: only admin (role_id = 1) can archive partylists
        if ($user_role != 1) {
            $_SESSION['error'] = 'You do not have permission to archive this partylist. Only admins can archive partylists.';
            header("Location: officers.php");
            exit;
        }

        // Archive the partylist instead of deleting
        // Also archive all associated officers
        $conn->begin_transaction();
        
        // Archive partylist
        $stmt = $conn->prepare("UPDATE partylist SET is_archived = 1 WHERE id = ?");
        $stmt->bind_param("i", $partylist_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error archiving partylist: ' . $stmt->error);
        }
        $stmt->close();
        
        // Archive all officers in this partylist
        $stmt = $conn->prepare("UPDATE officers SET is_archived = 1 WHERE partylist_id = ?");
        $stmt->bind_param("i", $partylist_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error archiving officers: ' . $stmt->error);
        }
        $stmt->close();
        
        $conn->commit();
        $_SESSION['success'] = 'Partylist and associated officers archived successfully';
        header("Location: officers.php");
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header("Location: officers.php");
    }
    exit();
} else {
    $_SESSION['error'] = 'Invalid request.';
    header("Location: officers.php");
    exit();
}
?>
