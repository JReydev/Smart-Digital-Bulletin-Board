<?php
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

if (isset($_GET['id'])) {
    try {
        $event_id = intval($_GET['id']);
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role_id'];
        
        // Get event details and check permissions
        $stmt = $conn->prepare("SELECT created_by FROM events WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $event_id);
        if (!$stmt->execute()) {
            throw new Exception('Error fetching event: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $stmt->close();
        
        if (!$event) {
            throw new Exception('Event not found');
        }
        
        // Check permissions: only creator or admin (role_id = 1) can archive
        if ($event['created_by'] != $user_id && $user_role != 1) {
            $_SESSION['error'] = 'You do not have permission to archive this event. Only the creator or an admin can archive it.';
            header("Location: upcoming-events.php");
            exit;
        }

        // Archive event instead of deleting
        $archive_query = "UPDATE events SET is_archived = 1 WHERE id = ?";
        $stmt = $conn->prepare($archive_query);
        
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $event_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Event archived successfully';
            header("Location: upcoming-events.php");
        } else {
            throw new Exception('Error archiving event: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: upcoming-events.php");
    }
} else {
    $_SESSION['error'] = 'Invalid event ID!';
    header("Location: upcoming-events.php");
}
?>
