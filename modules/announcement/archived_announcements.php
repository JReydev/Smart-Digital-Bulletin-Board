<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/header.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $user_role !== ROLE_ADMIN) {
    header('Location: ../../login.php');
    exit;
}

// Fetch archived announcements
$query = "
    SELECT a.*, 
           GROUP_CONCAT(DISTINCT m.file_path ORDER BY am.display_order) as media_files,
           u.username as author_name,
           CASE 
               WHEN a.announcement_date IS NOT NULL AND a.announcement_time IS NOT NULL THEN
                   CONCAT(a.announcement_date, ' ', a.announcement_time) 
               ELSE 
                   a.created_at 
           END as effective_date
    FROM announcements a 
    LEFT JOIN announcement_media am ON a.id = am.announcement_id
    LEFT JOIN multimedia_content m ON am.media_id = m.id 
    LEFT JOIN users u ON a.created_by = u.id
    WHERE a.is_archived = TRUE
    GROUP BY a.id
    ORDER BY effective_date DESC
";
$stmt = $conn->prepare($query);
if (!$stmt->execute()) {
    die('Error fetching announcements: ' . $stmt->error);
}
$archived_announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Announcements</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            padding: 20px;
            text-align: center;
            font-size: 1.5em;
            color: #ffffff;
            position: relative;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            width: 90%;
            max-width: 1200px;
            border-radius: 10px;
        }
        .header-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        .header-title {
            font-size: 1.5em;
            font-weight: 600;
        }
        .back-button {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8em;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%) translateX(-5px);
        }
        .back-button .button-text {
            display: inline;
        }
        .announcement-container {
            width: 90%;
            max-width: 1200px;
            background: #ffffff;
            margin: 30px auto;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        .announcement {
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .announcement:hover {
            box-shadow: 0 4px 12px rgba(123, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .announcement h3 {
            font-size: 1.4em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .announcement p {
            color: #333;
            margin-bottom: 10px;
        }
        .archive-info {
            background: rgba(123, 0, 0, 0.05);
            padding: 10px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.9em;
            color: #7B0000;
        }
        .archive-info i {
            margin-right: 5px;
        }
        .no-archives {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .schedule {
            background: rgba(123, 0, 0, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            margin-bottom: 15px;
            display: inline-block;
            color: #7B0000;
            font-size: 0.9em;
        }
        .tentative {
            background: rgba(255, 193, 7, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            margin-bottom: 15px;
            display: inline-block;
            color: #856404;
            font-size: 0.9em;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .tentative i {
            margin-right: 5px;
            color: #856404;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .meta small {
            color: #666;
        }
        .meta i {
            margin-right: 5px;
            color: #7B0000;
        }
        .restore-button {
            padding: 8px 16px;
            background: rgba(123, 0, 0, 0.1);
            border: none;
            border-radius: 20px;
            color: #7B0000;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .restore-button:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .media-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s ease;
            background: #ffffff;
            border: 1px solid #e0e0e0;
        }
        
        .media-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .media-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(123, 0, 0, 0.1);
        }
        
        .media-order-handle {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(123, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            pointer-events: none;
        }
        
        .announcement-image {
            width: 100%;
            margin: 15px 0;
            border-radius: 8px;
            max-height: 400px;
            object-fit: contain;
            background: #ffffff;
            padding: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .announcement-image.loading {
            min-height: 200px;
            background: #f8f9fa url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" stroke="%237B0000" stroke-width="8" fill="none" stroke-dasharray="180 60" transform="rotate(0 50 50)"><animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="1s" repeatCount="indefinite"/></circle></svg>') center/50px no-repeat;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .header {
                font-size: 1.3em;
                padding: 15px;
                margin: 15px auto;
                width: 95%;
            }

            .header-content {
                flex-direction: column;
                gap: 10px;
            }

            .back-button {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                display: flex;
                align-items: center;
                justify-content: center;
                width: auto;
                max-width: none;
                order: 0;
                padding: 8px;
                border-radius: 50%;
            }
            .back-button .button-text {
                display: none;
            }

            .header-title {
                font-size: 1.3em;
                order: 2;
            }

            .announcement-container {
                width: 95%;
                padding: 20px;
                margin: 20px auto;
            }

            .announcement {
                padding: 15px;
                margin-bottom: 15px;
            }

            .announcement h3 {
                font-size: 1.2em;
            }

            .media-container {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .media-item img {
                height: 150px;
            }
        }

        @media (max-width: 480px) {
            .header {
                font-size: 1.1em;
                padding: 12px;
                margin: 10px auto;
                width: 98%;
            }

            .header-content {
                gap: 8px;
            }

            .back-button {
                font-size: 0.9em;
                padding: 6px;
                max-width: none;
                left: 12px;
            }

            .header-title {
                font-size: 1.1em;
            }

            .announcement-container {
                width: 98%;
                padding: 15px;
                margin: 15px auto;
            }

            .announcement {
                padding: 12px;
                margin-bottom: 12px;
            }

            .announcement h3 {
                font-size: 1.1em;
            }

            .announcement p {
                font-size: 0.95em;
            }

            .meta {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .restore-button {
                padding: 6px 12px;
                font-size: 0.9em;
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 360px) {
            .header {
                font-size: 1em;
                padding: 10px;
            }

            .header-content {
                gap: 6px;
            }

            .back-button {
                font-size: 0.8em;
                padding: 5px;
                max-width: none;
                left: 10px;
            }

            .header-title {
                font-size: 1em;
            }

            .announcement-container {
                padding: 12px;
            }

            .announcement {
                padding: 10px;
                margin-bottom: 10px;
            }

            .announcement h3 {
                font-size: 1em;
            }

            .announcement p {
                font-size: 0.9em;
            }
        }

        @media (max-width: 640px) {
            .announcement-image {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="announcement.php" class="back-button">
                <i class="fas fa-arrow-left"></i> <span class="button-text">Back to Announcements</span>
            </a>
            <span class="header-title"><i class="fas fa-archive"></i> Archived Announcements</span>
        </div>
    </div>

    <div class="announcement-container">
        <?php if (empty($archived_announcements)): ?>
            <div class="no-archives">
                <i class="fas fa-box-open"></i>
                <p>No archived announcements found</p>
            </div>
        <?php else: ?>
            <?php foreach ($archived_announcements as $announcement): ?>
                <div class="announcement">
                    <?php if (!empty($announcement['announcement_date']) && !empty($announcement['announcement_time'])): ?>
                        <div class="schedule">
                            <i class="fas fa-calendar-alt"></i>
                            <?php 
                            $date = date('F j, Y', strtotime($announcement['announcement_date']));
                            $time = date('g:i A', strtotime($announcement['announcement_time']));
                            echo htmlspecialchars("Scheduled for $date at $time"); 
                            ?>
                        </div>
                    <?php elseif (!empty($announcement['announcement_date'])): ?>
                        <div class="schedule">
                            <i class="fas fa-calendar-alt"></i>
                            <?php 
                            $date = date('F j, Y', strtotime($announcement['announcement_date']));
                            echo htmlspecialchars("Scheduled for $date"); 
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="tentative">
                            <i class="fas fa-clock"></i>
                            Tentative - Date was not set
                        </div>
                    <?php endif; ?>
                    
                    <h3>
                        <i class="fas fa-archive"></i>
                        <?php echo htmlspecialchars($announcement['title']); ?>
                    </h3>
                    
                    <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                    
                    <?php if (!empty($announcement['media_files'])): ?>
                        <div class="media-container" data-announcement-id="<?= htmlspecialchars($announcement['id']) ?>">
                            <?php 
                            // Fetch media IDs and paths for this announcement with proper ordering
                            $media_query = "
                                SELECT m.id as media_id, m.file_path 
                                FROM announcement_media am 
                                JOIN multimedia_content m ON am.media_id = m.id 
                                WHERE am.announcement_id = ? 
                                ORDER BY am.display_order ASC
                            ";
                            $media_stmt = $conn->prepare($media_query);
                            $media_stmt->bind_param("i", $announcement['id']);
                            $media_stmt->execute();
                            $media_result = $media_stmt->get_result();
                            $total_media = $media_result->num_rows;
                            
                            $counter = 1;
                            while ($media = $media_result->fetch_assoc()): 
                                $file_extension = strtolower(pathinfo($media['file_path'], PATHINFO_EXTENSION));
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])):
                            ?>
                                <?php 
                                    $filePath = $media['file_path'];
                                    $src = (strpos($filePath, 'multimedia/') === 0)
                                        ? '../../' . $filePath
                                        : '../../multimedia/' . $filePath;
                                ?>
                                <div class="media-item" data-media-id="<?= htmlspecialchars($media['media_id']) ?>">
                                    <img src="<?= htmlspecialchars($src) ?>" 
                                         alt="<?= htmlspecialchars($announcement['title']) ?>" 
                                         class="announcement-image"
                                         onclick="openImageModal(this.src)">
                                    <div class="media-order-handle"><?= $counter ?>/<?= $total_media ?></div>
                                </div>
                            <?php 
                                $counter++;
                                endif;
                            endwhile;
                            $media_stmt->close();
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="meta">
                        <small>
                            <i class="fas fa-user"></i> <?= htmlspecialchars($announcement['author_name'] ?? 'Unknown User') ?>
                            <i class="fas fa-clock"></i> <?= date('M d, Y h:i A', strtotime($announcement['created_at'])) ?>
                        </small>
                        <form action="restore_announcement.php" method="POST" style="display: inline;">
                            <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement['id']) ?>">
                            <button type="submit" class="restore-button">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function restoreAnnouncement(id) {
            if (confirm('Are you sure you want to restore this announcement?')) {
                fetch('restore_announcement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${encodeURIComponent(id)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(() => {
                    alert('Announcement restored successfully');
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to restore announcement');
                });
            }
        }

        function openImageModal(src) {
            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            modal.style.zIndex = '1000';
            modal.style.cursor = 'pointer';

            const img = document.createElement('img');
            img.src = src;
            img.style.maxWidth = '90%';
            img.style.maxHeight = '90%';
            img.style.objectFit = 'contain';
            img.style.borderRadius = '8px';

            modal.appendChild(img);
            document.body.appendChild(modal);

            modal.onclick = function() {
                document.body.removeChild(modal);
            };
        }
    </script>
</body>
</html> 