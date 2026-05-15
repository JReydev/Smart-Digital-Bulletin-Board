<?php
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

if (isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role_id'];
        
        // Get faculty details and check permissions
        // Faculty records are associated with users, so we check the user_id
        $stmt = $conn->prepare("SELECT user_id FROM faculty WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception('Error fetching faculty: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $faculty = $result->fetch_assoc();
        $stmt->close();
        
        if (!$faculty) {
            throw new Exception('Faculty not found');
        }
        
        // Check permissions: only the associated user or admin (role_id = 1) can archive
        // Note: For faculty, we consider the user_id as the "creator"
        if ($faculty['user_id'] != $user_id && $user_role != 1) {
            $_SESSION['error'] = 'You do not have permission to archive this faculty profile. Only the faculty member or an admin can archive it.';
            header("Location: faculty.php");
            exit;
        }

        // Archive faculty instead of deleting
        $archive_query = "UPDATE faculty SET is_archived = 1 WHERE id = ?";
        $stmt = $conn->prepare($archive_query);
        
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Faculty profile archived successfully';
            header("Location: faculty.php");
        } else {
            throw new Exception('Error archiving faculty: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: faculty.php");
    }
} else {
    $_SESSION['error'] = 'Invalid faculty ID!';
    header("Location: faculty.php");
}
exit;
?>
