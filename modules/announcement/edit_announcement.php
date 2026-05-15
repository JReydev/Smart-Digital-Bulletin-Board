<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/media_utils.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized access";
    exit;
}

// Handle media deletion
if (isset($_POST['delete_media']) && isset($_POST['announcement_id'])) {
    $announcement_id = $_POST['announcement_id'];
    
    // Get the media_id from the announcement_media table
    $query = "SELECT media_id FROM announcement_media WHERE announcement_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from announcement_media table
        $delete_announcement_media = "DELETE FROM announcement_media WHERE announcement_id = ?";
        $delete_stmt = $conn->prepare($delete_announcement_media);
        $delete_stmt->bind_param("i", $announcement_id);
        $delete_stmt->execute();
        
        // Delete media files and records
        while($row = $result->fetch_assoc()) {
            if($row['media_id']) {
                deleteMedia($conn, $row['media_id']);
            }
        }
        
        // If everything is successful, commit the transaction
        $conn->commit();
    } catch (Exception $e) {
        // If anything goes wrong, rollback the changes
        $conn->rollback();
        throw $e;
    }
    
    header("Location: edit_announcement.php?id=" . $announcement_id);
    exit;
}

if (isset($_GET['id']) || isset($_POST['id'])) {
    $announcement_id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
    
    // Get announcement details using prepared statement
    $query = "SELECT * FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $announcement = $result->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Debug: Log the incoming POST data
        error_log("Edit announcement POST request received. POST data: " . print_r($_POST, true));
        error_log("Edit announcement FILES data: " . print_r($_FILES, true));
        
        try {
            $id = intval($_POST['id']);
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $announcement_date = (!empty($_POST['announcement_date']) && trim($_POST['announcement_date']) !== '') ? trim($_POST['announcement_date']) : null;
            $announcement_time = (!empty($_POST['announcement_time']) && trim($_POST['announcement_time']) !== '') ? trim($_POST['announcement_time']) : null;
            $created_by = (int)$_SESSION['user_id'];

        // Validate inputs
        if (empty($title) || empty($content)) {
            throw new Exception('Title and content are required.');
        }

        // Validate date format only if date is provided
        if (!empty($announcement_date)) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $announcement_date)) {
                throw new Exception('Invalid date format. Use YYYY-MM-DD format.');
            }

            // Validate that the date is not in the past
            $today = date('Y-m-d');
            if ($announcement_date < $today) {
                throw new Exception('Cannot select a past date for announcements.');
            }
        }

        // Validate time format if provided
        if (!empty($announcement_time)) {
            // Allow both HH:MM and HH:MM:SS formats
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $announcement_time)) {
                throw new Exception('Invalid time format. Use HH:MM or HH:MM:SS format.');
            }
        }
        
        // Convert time format from HH:MM to HH:MM:SS for database storage
        if (!empty($announcement_time) && strlen($announcement_time) === 5) {
            $announcement_time = $announcement_time . ':00';
        }

        // If time is provided without date, show warning
        if (!empty($announcement_time) && empty($announcement_date)) {
            throw new Exception('Time cannot be set without a date. Please provide a date or leave both fields empty for tentative announcements.');
        }

        // Validate title length
        if (strlen($title) < 3 || strlen($title) > 255) {
            throw new Exception('Title must be between 3 and 255 characters.');
        }

        // Validate title format (letters, numbers, and common punctuation)
        if (!preg_match('/^[A-Za-z0-9\s\-_.,!?()&@#$%^*+=|\\/:\'"`~]+$/', $title)) {
            throw new Exception('Title contains invalid characters.');
        }

        // Verify announcement exists and user has permission
        $check = $conn->prepare("SELECT created_by FROM announcements WHERE id = ?");
        if ($check === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        $check->bind_param("i", $id);
        if (!$check->execute()) {
            throw new Exception('Error checking announcement: ' . $check->error);
        }
        $check->bind_result($original_creator);
        if (!$check->fetch()) {
            throw new Exception('Announcement not found.');
        }
        $check->close();

        // Only allow edit if user is the creator or an admin
        $user_role = $_SESSION['role_id'] ?? 0;
        $is_creator = ($original_creator === $created_by);
        $is_admin = ($user_role == 1);
        
        if (!$is_creator && !$is_admin) {
            throw new Exception('You do not have permission to edit this announcement.');
        }

        // Start transaction
        $conn->begin_transaction();

        // Handle media files - only process if new files are uploaded
        if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
            // Delete existing media for this announcement
            $delete_media_query = "DELETE am FROM announcement_media am WHERE am.announcement_id = ?";
            $delete_stmt = $conn->prepare($delete_media_query);
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            
            // Upload and save new media files
            $media_values = [];
            $media_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            for ($i = 0; $i < count($_FILES['media']['name']); $i++) {
                if ($_FILES['media']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['media']['type'][$i];
                    
                    // Validate file type
                    if (!in_array($file_type, $media_types)) {
                        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP files are allowed.');
                    }
                    
                    // Validate file size (5MB limit)
                    if ($_FILES['media']['size'][$i] > 5 * 1024 * 1024) {
                        throw new Exception('File size too large. Maximum size is 5MB.');
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = '../../multimedia/announcements/' . $new_filename;
                    
                    // Create directory if it doesn't exist
                    if (!is_dir('../../multimedia/announcements/')) {
                        mkdir('../../multimedia/announcements/', 0755, true);
                    }
                    
                    // Move uploaded file
                    if (!move_uploaded_file($_FILES['media']['tmp_name'][$i], $upload_path)) {
                        throw new Exception('Failed to upload file.');
                    }
                    
                    // Insert media record
                    $media_path = 'announcements/' . $new_filename;
                    $insert_media = "INSERT INTO multimedia_content (file_path, uploaded_by) VALUES (?, ?)";
                    $media_stmt = $conn->prepare($insert_media);
                    $uploaded_by = $_SESSION['user_id'];
                    $media_stmt->bind_param("si", $media_path, $uploaded_by);
                    $media_stmt->execute();
                    $media_id = $conn->insert_id;
                    
                    // Store for announcement_media insertion
                    $media_values[] = "($id, $media_id, $i)";
                }
            }
            
            // Insert announcement_media records
            if (!empty($media_values)) {
                $insert_announcement_media = "INSERT INTO announcement_media (announcement_id, media_id, display_order) VALUES " . implode(',', $media_values);
                $conn->query($insert_announcement_media);
            }
        }

        // Update the announcement
        $current_datetime = date('Y-m-d H:i:s');
        
        // Check if announcement is passed
        if (!empty($announcement_date) && !empty($announcement_time)) {
            $announcement_datetime = $announcement_date . ' ' . $announcement_time;
            $is_archived = ($announcement_datetime < $current_datetime);
        } else {
            // If no date/time is set, don't archive the announcement
            // Only archive if it was previously archived and should remain so
            $is_archived = 0;
        }

        $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, announcement_date = ?, announcement_time = ?, is_archived = ? WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("ssssii", $title, $content, $announcement_date, $announcement_time, $is_archived, $id);

        if (!$stmt->execute()) {
            throw new Exception('Failed to update announcement: ' . $stmt->error);
        }

        // Commit transaction
        $conn->commit();
        
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
        exit;

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            // Log the error for debugging
            error_log("Edit announcement error: " . $e->getMessage() . " - POST data: " . print_r($_POST, true));
            
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage(),
                'debug' => [
                    'post_data' => $_POST,
                    'files_data' => $_FILES,
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile())
                ]
            ]);
            exit;
        }
    } else {
        // GET request - display the form
        if (!$announcement) {
            echo "Announcement not found!";
            exit;
        }
        
        // Get existing media for this announcement
        $media_query = "SELECT mc.*, am.display_order 
                       FROM multimedia_content mc 
                       JOIN announcement_media am ON mc.id = am.media_id 
                       WHERE am.announcement_id = ? 
                       ORDER BY am.display_order";
        $media_stmt = $conn->prepare($media_query);
        $media_stmt->bind_param("i", $announcement_id);
        $media_stmt->execute();
        $media_result = $media_stmt->get_result();
        $existing_media = $media_result->fetch_all(MYSQLI_ASSOC);
        $media_stmt->close();
    }
} else {
    echo "Announcement not found!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Announcement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        body { 
            background: #ffffff;
            color: #333;
            min-height: 100vh;
            line-height: 1.6;
        }
        .header {
            background: #7B0000;
            color: white;
            padding: 15px 20px;
            font-size: 2em;
            font-weight: 600;
            letter-spacing: -0.5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container { 
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: #7B0000;
            font-weight: 500;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group label i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }
        input::placeholder, textarea::placeholder {
            color: #666;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.6;
        }
        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #7B0000;
            color: #ffffff;
            border: 1px solid #7B0000;
            padding: 12px 24px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        button:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #7B0000;
            text-decoration: none;
            padding: 12px 24px;
            border: 1px solid #7B0000;
            border-radius: 30px;
            transition: all 0.3s ease;
            margin-top: 20px;
            background: rgba(123, 0, 0, 0.1);
        }
        .back-link:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .file-input-container {
            margin-top: 10px;
        }
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: #ffffff;
            border: 2px dashed #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            color: #666;
        }
        .file-input-label:hover {
            background: #f8f9fa;
            border-color: #7B0000;
        }
        .file-input-label i {
            font-size: 1.5em;
            color: #7B0000;
        }
        input[type="file"] {
            display: none;
        }
        input[type="date"], input[type="time"] {
            color-scheme: light;
        }
        .current-media {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }
        .current-media img, .current-media video {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .delete-media-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .delete-media-btn:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<?php include '../include/header.php'; ?>
<div class="header">
    <span>Edit Announcement</span>
</div>

<div class="container">
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $announcement_id; ?>">
        
        <div class="form-group">
            <label for="title"><i class="fas fa-heading"></i> Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="form-group">
            <label for="content"><i class="fas fa-align-left"></i> Content</label>
            <textarea name="content" id="content" required><?php echo htmlspecialchars($announcement['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-group">
            <label for="announcement_date"><i class="far fa-calendar"></i> Date</label>
            <input type="date" name="announcement_date" id="announcement_date" value="<?php echo $announcement['announcement_date']; ?>">
        </div>

        <div class="form-group">
            <label for="announcement_time"><i class="far fa-clock"></i> Time</label>
            <input type="time" name="announcement_time" id="announcement_time" value="<?php echo $announcement['announcement_time']; ?>">
        </div>

        <div class="form-group">
            <label for="media"><i class="fas fa-image"></i> Media (optional)</label>
            <?php if(!empty($existing_media)): ?>
                <div class="current-media">
                    <div class="media-preview">
                        <?php foreach($existing_media as $media): 
                            $file_extension = strtolower(pathinfo($media['file_path'], PATHINFO_EXTENSION));
                            $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $is_video = in_array($file_extension, ['mp4', 'webm', 'ogg']);
                        ?>
                            <div style="margin-bottom: 15px;">
                                <?php if($is_image): ?>
                                    <img src="../../<?php echo htmlspecialchars($media['file_path']); ?>" 
                                         alt="Current media" 
                                         style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px;">
                                <?php elseif($is_video): ?>
                                    <video src="../../<?php echo htmlspecialchars($media['file_path']); ?>" 
                                           controls
                                           style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px;"></video>
                                <?php endif; ?>
                                <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <i class="fas fa-file"></i> <?php echo basename($media['file_path']); ?>
                                    </div>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this media?');">
                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement_id; ?>">
                                        <button type="submit" name="delete_media" class="delete-media-btn" style="background: none; border: none; color: #ff4d4d; cursor: pointer; padding: 5px;">
                                            <i class="fas fa-trash"></i> Delete Media
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="file-input-container">
                <label for="media" class="file-input-label">
                    <i class="fas fa-cloud-upload-alt"></i> Choose new files or drag them here
                </label>
                <input type="file" name="media[]" id="media" accept="image/*,video/*" multiple>
            </div>
        </div>

        <button type="submit" name="update_announcement"><i class="fas fa-save"></i> Update Announcement</button>
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="announcement.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Announcements List</a>
    </div>
</div>

</body>
</html>
