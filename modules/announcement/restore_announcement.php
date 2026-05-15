<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Get user role
$user_role = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $user_role = $user['role_id'];
    }
}

// Check if user is logged in and is admin
// Using literal value 1 for ROLE_ADMIN since constants aren't defined without header inclusion
if (!isset($_SESSION['user_id']) || $user_role !== 1) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['announcement_id']) ? intval($_POST['announcement_id']) : 0;
        
        // Verify announcement exists and get its current date/time
        $check = $conn->prepare("SELECT announcement_date, announcement_time FROM announcements WHERE id = ?");
        if ($check === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $check->bind_param("i", $id);
        if (!$check->execute()) {
            throw new Exception('Error checking announcement: ' . $check->error);
        }
        
        $result = $check->get_result();
        $announcement = $result->fetch_assoc();
        
        if (!$announcement) {
            throw new Exception('Announcement not found.');
        }
        $check->close();

        // Get current date and time
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');

        // If the announcement date/time has passed, set it to tomorrow at the same time
        if (!empty($announcement['announcement_date']) && !empty($announcement['announcement_time'])) {
            $announcement_datetime = $announcement['announcement_date'] . ' ' . $announcement['announcement_time'];
            $current_datetime = date('Y-m-d H:i:s');
            
            if ($announcement_datetime < $current_datetime) {
                // Set to tomorrow at the same time
                $new_date = date('Y-m-d', strtotime('+1 day'));
                $new_time = $announcement['announcement_time'];
            } else {
                // Keep original date and time
                $new_date = $announcement['announcement_date'];
                $new_time = $announcement['announcement_time'];
            }
        } else {
            // If no date/time was set, set it to tomorrow
            $new_date = date('Y-m-d', strtotime('+1 day'));
            $new_time = '09:00:00'; // Default to 9 AM
        }

        // Update the announcement with new date/time and archive status
        $stmt = $conn->prepare("UPDATE announcements SET is_archived = FALSE, announcement_date = ?, announcement_time = ? WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("ssi", $new_date, $new_time, $id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to restore announcement: ' . $stmt->error);
        }

        // Redirect back to the main announcement page
        header('Location: announcement.php');
        exit;
        
    } catch (Exception $e) {
        // In case of error, redirect back with error message in session
        session_start();
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: archived_announcements.php');
        exit;
    }
} else {
    // Invalid request method, redirect back
    header('Location: archived_announcements.php');
    exit;
}
?> 