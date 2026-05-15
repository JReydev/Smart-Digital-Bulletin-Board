<?php
function handleMediaUpload($file, $type) {
    global $conn;
    
    if ($file['error'] !== 0) {
        return null;
    }

    $upload_dir = __DIR__ . '/../../multimedia/' . $type . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    $relative_path = $type . '/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Insert into multimedia_content table with uploaded_by field
        $query = "INSERT INTO multimedia_content (file_path, uploaded_by) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $uploaded_by = $_SESSION['user_id']; // Get the current user's ID from session
        $stmt->bind_param("si", $relative_path, $uploaded_by);
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
    }
    
    return null;
}

function deleteMedia($conn, $media_id) {
    // Get the file path
    $query = "SELECT file_path FROM multimedia_content WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $media_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $file_path = __DIR__ . '/../../multimedia/' . $row['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM multimedia_content WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $media_id);
        $stmt->execute();
    }
}

function getMediaFileName($conn, $media_id) {
    $query = "SELECT file_path FROM multimedia_content WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $media_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return basename($row['file_path']);
    }
    return null;
} 