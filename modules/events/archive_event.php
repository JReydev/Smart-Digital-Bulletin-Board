<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if is_archived column exists, if not, add it
    $check_column = "SHOW COLUMNS FROM events LIKE 'is_archived'";
    $column_result = mysqli_query($conn, $check_column);
    if (mysqli_num_rows($column_result) == 0) {
        // Add the column if it doesn't exist
        $add_column = "ALTER TABLE events ADD COLUMN is_archived TINYINT(1) DEFAULT 0";
        mysqli_query($conn, $add_column);
    }
    
    // Verify that the user owns this event or is an admin
    $check_query = "SELECT created_by FROM events WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    
    if (!$event) {
        $_SESSION['error'] = 'Event not found.';
        header('Location: upcoming-events.php');
        exit;
    }
    
    // Check if user has permission to archive this event
    if ($event['created_by'] != $user_id && $_SESSION['role_id'] != 1) { // 1 is admin role
        $_SESSION['error'] = 'You do not have permission to archive this event.';
        header('Location: upcoming-events.php');
        exit;
    }
    
    // Update the event to archive it (set is_archived to 1)
    $archive_query = "UPDATE events SET is_archived = 1 WHERE id = ?";
    $stmt = $conn->prepare($archive_query);
    $stmt->bind_param("i", $event_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Event archived successfully.';
    } else {
        $_SESSION['error'] = 'Failed to archive event. Please try again.';
    }
    
    $stmt->close();
} else {
    $_SESSION['error'] = 'Invalid event ID.';
}

header('Location: upcoming-events.php');
exit;
?>
