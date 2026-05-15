<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized access";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['announcement_id']) ? intval($_POST['announcement_id']) : 0;
        $user_id = $_SESSION['user_id'];
        
        if ($id <= 0) {
            throw new Exception('Invalid announcement ID.');
        }
        
        // Verify announcement exists and user has permission
        $check = $conn->prepare("SELECT created_by FROM announcements WHERE id = ? AND is_archived = FALSE");
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
            throw new Exception('Announcement not found or already archived.');
        }
        
        // Check if user is the creator or an admin
        $user_role = $_SESSION['role_id'] ?? 0;
        $is_creator = ($announcement['created_by'] == $user_id);
        $is_admin = ($user_role == 1);
        
        if (!$is_creator && !$is_admin) {
            throw new Exception('You can only archive your own announcements.');
        }
        $check->close();

        // Archive the announcement
        $stmt = $conn->prepare("UPDATE announcements SET is_archived = TRUE WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to archive announcement: ' . $stmt->error);
        }
        $stmt->close();

        // Set success message in session and redirect
        $_SESSION['success'] = 'Announcement archived successfully.';
        header('Location: announcement.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: announcement.php');
        exit;
    }
} else {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: announcement.php');
    exit;
}
?>
