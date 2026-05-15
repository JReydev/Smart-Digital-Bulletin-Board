<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Check if is_archived column exists, if not, add it
$check_column = "SHOW COLUMNS FROM events LIKE 'is_archived'";
$column_result = mysqli_query($conn, $check_column);
if (mysqli_num_rows($column_result) == 0) {
    // Add the column if it doesn't exist
    $add_column = "ALTER TABLE events ADD COLUMN is_archived TINYINT(1) DEFAULT 0";
    mysqli_query($conn, $add_column);
}

// Handle POST request (form submission) or GET request (legacy simple restore)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form submission with updated data
    $event_id = (int)$_POST['event_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $duration = (int)$_POST['duration'];
    $location = trim($_POST['location']);
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($date) || empty($time) || empty($location) || $duration < 1) {
        $_SESSION['error'] = 'All fields are required and duration must be at least 1 day.';
        header('Location: archived_events.php');
        exit;
    }
    
    // Validate that the date is not in the past
    $today = date('Y-m-d');
    if ($date < $today) {
        $_SESSION['error'] = 'Cannot select a past date for events.';
        header('Location: archived_events.php');
        exit;
    }
    
    // If the date is today, validate that the time is not in the past
    if ($date === $today) {
        $current_time = date('H:i');
        if ($time < $current_time) {
            $_SESSION['error'] = 'Cannot select a past time for today\'s events.';
            header('Location: archived_events.php');
            exit;
        }
    }
    
} elseif (isset($_GET['id'])) {
    // Legacy GET request for simple restore
    $event_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
} else {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: archived_events.php');
    exit;
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
    header('Location: archived_events.php');
    exit;
}

// Check if user has permission to restore this event
if ($event['created_by'] != $user_id && $_SESSION['role_id'] != 1) { // 1 is admin role
    $_SESSION['error'] = 'You do not have permission to restore this event.';
    header('Location: archived_events.php');
    exit;
}

// Prepare restore query based on request type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Restore with updated data
    $restore_query = "UPDATE events SET is_archived = 0, date = ?, time = ?, duration = ?, location = ? WHERE id = ?";
    $stmt = $conn->prepare($restore_query);
    $stmt->bind_param("ssisi", $date, $time, $duration, $location, $event_id);
    $success_message = 'Event restored successfully with updated information.';
} else {
    // Simple restore without changes
    $restore_query = "UPDATE events SET is_archived = 0 WHERE id = ?";
    $stmt = $conn->prepare($restore_query);
    $stmt->bind_param("i", $event_id);
    $success_message = 'Event restored successfully.';
}

if ($stmt->execute()) {
    $_SESSION['success'] = $success_message;
} else {
    $_SESSION['error'] = 'Failed to restore event. Please try again.';
}

$stmt->close();

header('Location: archived_events.php');
exit;
?>
