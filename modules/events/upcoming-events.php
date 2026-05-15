<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upcoming Events</title>
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
        .container { 
            width: 90%;
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        .event-card { 
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .event-card:hover {
            background: #f8f9fa;
            transform: translateX(5px);
            border-color: #7B0000;
        }
        .event-card h3 { 
            font-size: 1.4em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 10px;
        }
        .event-card p { 
            font-size: 1.1em;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .event-meta { 
            color: #666;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
        }
        .event-meta i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }
        .buttons { 
            text-align: right;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        .add-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #7B0000;
            background: rgba(123, 0, 0, 0.1);
            border: none;
            padding: 12px 24px;
            font-size: 1.1em;
            cursor: pointer;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(123, 0, 0, 0.2);
            justify-content: center;
            text-align: center;
        }
        .add-button:hover { 
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .add-button.archive-btn {
            background: rgba(128, 128, 128, 0.1);
            border-color: rgba(128, 128, 128, 0.2);
            color: #666;
        }
        
        /* Add Event Modal Styles */
        .popup {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1002;
            overflow-y: auto;
            padding: 80px 0;
            backdrop-filter: blur(8px);
        }
        .popup.show {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .popup-wrapper {
            width: 100%;
            min-height: calc(100vh - 280px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px;
        }
        .popup-content {
            background: #ffffff;
            padding: 25px;
            border-radius: 16px;
            width: min(600px, 90%);
            max-height: 80vh;
            position: relative;
            border: 1px solid #e0e0e0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            color: #333;
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            margin: 20px auto;
        }
        .popup.show .popup-content {
            transform: translateY(0);
            opacity: 1;
        }
        .popup-header {
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .popup-header h3 {
            color: #7B0000;
            font-size: 1.3em;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .popup-header .close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .popup-header .close:hover {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
        }
        .popup-body {
            overflow-y: auto;
            flex: 1;
            margin: 0 -8px;
            padding: 0 8px;
        }
        .popup-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 15px;
        }
        .popup-footer button {
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .popup-footer button[type="button"] {
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
            color: #7B0000;
        }
        .popup-footer button[type="button"]:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .popup-footer button[type="submit"] {
            background: #7B0000;
            color: white;
            border: 1px solid #7B0000;
        }
        .popup-footer button[type="submit"]:hover {
            background: #8B0000;
            transform: translateY(-2px);
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
        .popup input, .popup textarea, .popup select {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }
        .popup input:focus, .popup textarea:focus, .popup select:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }
        .popup input::placeholder, .popup textarea::placeholder {
            color: #666;
        }
        .popup textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.6;
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
        .popup input[type="file"] {
            display: none;
        }
        .popup input[type="date"], .popup input[type="time"] {
            color-scheme: light;
        }
        .add-button.archive-btn:hover {
            background: rgba(128, 128, 128, 0.2);
        }
        .event-media {
            margin: 15px 0;
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .event-media img, .event-media video {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 12px;
        }
        .event-details {
            padding: 10px 0;
        }
        .event-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .edit-btn, .delete-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7B0000;
            text-decoration: none;
            transition: all 0.3s ease;
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .edit-btn:hover, .delete-btn:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .edit-btn i, .delete-btn i {
            font-size: 1em;
            color: #7B0000;
        }
        .event-description {
            white-space: pre-wrap;
            margin: 15px 0;
        }
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
                margin: 15px auto;
            }
            .event-card {
                padding: 15px;
            }
            .event-media img, .event-media video {
                max-height: 300px;
            }
            
            /* Modal responsive styles */
            .popup {
                padding: 20px 0;
            }
            .popup-content {
                width: 95%;
                padding: 20px;
                margin: 10px auto;
            }
            .popup-footer {
                flex-direction: column;
                gap: 8px;
            }
            .popup-footer button {
                width: 100%;
                justify-content: center;
            }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .container {
                width: 98%;
                padding: 12px;
                margin: 10px auto;
            }

            .event-card {
                padding: 12px;
                margin-bottom: 15px;
            }

            .event-card h3 {
                font-size: 1.1em;
            }

            .event-card p {
                font-size: 0.95em;
            }

            .buttons {
                position: static;
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }

            .add-button {
                padding: 10px 16px;
                font-size: 0.9em;
                flex: 1;
                min-width: 140px;
                max-width: 200px;
                justify-content: center;
                text-align: center;
            }
            
            /* Modal responsive styles */
            .popup-content {
                width: 98%;
                padding: 15px;
                margin: 5px auto;
            }
            .popup-header h3 {
                font-size: 1.1em;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                font-size: 0.9em;
            }
            .popup input, .popup textarea, .popup select {
                padding: 10px 12px;
                font-size: 0.9em;
            }
            .popup textarea {
                min-height: 100px;
            }

            .edit-btn, .delete-btn {
                padding: 8px 12px;
                font-size: 0.85em;
            }

            .event-media img, .event-media video {
                max-height: 200px;
            }

            .modal-content {
                width: 95%;
                margin: 10px auto;
            }

            .modal-header {
                padding: 12px 15px;
                font-size: 1.2em;
            }

            .modal-body {
                padding: 12px 15px;
            }
        }

        /* Extra Small Mobile Devices */
        @media (max-width: 360px) {
            .container {
                padding: 10px;
                margin: 8px auto;
            }

            .event-card {
                padding: 10px;
                margin-bottom: 10px;
            }

            .event-card h3 {
                font-size: 1em;
            }

            .event-card p {
                font-size: 0.9em;
            }

            .buttons {
                position: static;
                margin-bottom: 15px;
                gap: 8px;
            }

            .add-button {
                padding: 8px 12px;
                font-size: 0.8em;
                min-width: 120px;
                max-width: 160px;
                justify-content: center;
                text-align: center;
            }
            
            /* Modal responsive styles */
            .popup-content {
                width: 100%;
                padding: 12px;
                margin: 0;
                border-radius: 0;
            }
            .popup-header h3 {
                font-size: 1em;
            }
            .form-group {
                margin-bottom: 12px;
            }
            .form-group label {
                font-size: 0.85em;
            }
            .popup input, .popup textarea, .popup select {
                padding: 8px 10px;
                font-size: 0.85em;
            }
            .popup textarea {
                min-height: 80px;
            }
        }

        /* Tablet devices */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                width: 92%;
                padding: 25px;
            }

            .event-card {
                padding: 18px;
            }

            .event-media img, .event-media video {
                max-height: 350px;
            }
        }

        /* Landscape orientation on mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .container {
                margin: 10px auto;
                padding: 12px;
            }

            .event-card {
                padding: 10px;
                margin-bottom: 10px;
            }

            .modal {
                padding: 20px 0;
            }
        }

        /* Delete Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            overflow-y: auto;
            padding: 80px 0;
            backdrop-filter: blur(5px);
        }
        .modal.show {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .modal-content {
            background: #ffffff;
            width: min(400px, 90%);
            margin: 20px auto;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease;
            position: relative;
        }
        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        .modal-header {
            padding: 15px 20px;
            color: #7B0000;
            font-size: 1.4em;
            font-weight: 600;
        }
        .modal-body {
            padding: 15px 20px;
            color: #333;
            text-align: center;
        }
        .modal-footer {
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            gap: 12px;
            border-top: 1px solid #e0e0e0;
        }
        .modal-footer button {
            padding: 8px 16px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .modal-footer .cancel-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .modal-footer .cancel-btn:hover {
            background: rgba(123, 0, 0, 0.2);
        }
        .modal-footer .confirm-delete-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .modal-footer .confirm-delete-btn:hover {
            background: rgba(123, 0, 0, 0.2);
        }
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: #666;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: auto;
            margin: 0;
        }
        .close:hover {
            color: #333;
        }
        
        /* No Events Styles */
        .no-events {
            text-align: center;
            padding: 60px 30px;
            background: #f8f9fa;
            border-radius: 20px;
            border: 2px dashed #e0e0e0;
            margin: 20px 0;
        }
        .no-events-icon {
            font-size: 4em;
            color: #ccc;
            margin-bottom: 20px;
        }
        .no-events h3 {
            font-size: 1.8em;
            color: #666;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .no-events p {
            font-size: 1.1em;
            color: #888;
            margin-bottom: 30px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="header">
    <span>Upcoming Events</span>
</div>

<div class="container">
    <!-- Add Event Button -->
    <div class="buttons">
        <button class="add-button" onclick="openAddEventModal()"><i class="fas fa-plus"></i> Add New Event</button>
        <a href="archived_events.php" class="add-button archive-btn"><i class="fas fa-archive"></i> View Archives</a>
    </div>

    <?php
    // Check if is_archived column exists, if not, add it
    $check_column = "SHOW COLUMNS FROM events LIKE 'is_archived'";
    $column_result = mysqli_query($conn, $check_column);
    if (mysqli_num_rows($column_result) == 0) {
        // Add the column if it doesn't exist
        $add_column = "ALTER TABLE events ADD COLUMN is_archived TINYINT(1) DEFAULT 0";
        mysqli_query($conn, $add_column);
    }

    $query = "SELECT e.*, m.file_path, u.username as created_by_name 
              FROM events e 
              LEFT JOIN multimedia_content m ON e.media_id = m.id 
              LEFT JOIN users u ON e.created_by = u.id 
              WHERE CONCAT(e.date, ' ', e.time) >= NOW() 
              AND (e.is_archived = 0 OR e.is_archived IS NULL)
              ORDER BY e.date ASC, e.time ASC";
    $result = mysqli_query($conn, $query);
    
    // Fetch all events into an array
    $events = [];
    while($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    
    if (empty($events)): ?>
        <div class="no-events">
            <div class="no-events-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h3>No Upcoming Events</h3>
            <p>There are no events scheduled at the moment. Be the first to create one!</p>
        </div>
    <?php else: ?>
        <?php foreach($events as $row): 
        $end_date = date('Y-m-d', strtotime($row['date'] . ' + ' . ($row['duration'] - 1) . ' days'));
        $date_display = $row['date'] == $end_date ? 
            date('F j, Y', strtotime($row['date'])) : 
            date('F j', strtotime($row['date'])) . ' - ' . date('F j, Y', strtotime($end_date));
        
        // Normalize media path for robustness across historical data and OS differences
        $filePath = isset($row['file_path']) ? $row['file_path'] : '';
        $mediaSrc = '';
        if ($filePath) {
            $filePath = str_replace('\\', '/', $filePath); // normalize windows backslashes
            $filePath = ltrim($filePath, '/'); // remove leading slash
            if (strpos($filePath, 'SmartBulletin/') === 0) {
                $filePath = substr($filePath, strlen('SmartBulletin/'));
            }
            if (strpos($filePath, 'multimedia/') === 0) {
                $mediaSrc = '../../' . $filePath;
            } elseif (strpos($filePath, 'events/') === 0) {
                $mediaSrc = '../../multimedia/' . $filePath;
            } elseif (strpos($filePath, 'announcements/') === 0 || strpos($filePath, 'faculty/') === 0 || strpos($filePath, 'officers/') === 0) {
                $mediaSrc = '../../multimedia/' . $filePath;
            } else {
                $mediaSrc = '../../multimedia/events/' . basename($filePath);
            }
        }
        ?>
        <div class="event-card">
            <?php if($row['file_path']): ?>
                <div class="event-media">
                    <?php
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                        <img src="<?php echo $mediaSrc; ?>" alt="Event Media">
                    <?php elseif(in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                        <video controls>
                            <source src="<?php echo $mediaSrc; ?>" type="video/<?php echo $ext; ?>">
                        </video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="event-details">
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p class="event-meta"><i class="far fa-calendar"></i> <?php echo $date_display; ?></p>
                <p class="event-meta"><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($row['time'])); ?></p>
                <p class="event-meta"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?></p>
                <p class="event-description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                <p class="event-meta"><i class="far fa-clock"></i> Duration: <?php echo $row['duration']; ?> day<?php echo $row['duration'] > 1 ? 's' : ''; ?></p>
                <p class="event-meta"><i class="far fa-user"></i> Posted by: <?php echo htmlspecialchars($row['created_by_name']); ?></p>
                
                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['created_by']): ?>
                    <div class="event-actions">
                        <button type="button" class="edit-btn" onclick="openEditEventModal(<?php echo $row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['title'])); ?>', '<?php echo addslashes(htmlspecialchars($row['description'])); ?>', '<?php echo $row['date']; ?>', <?php echo $row['duration']; ?>, '<?php echo $row['time']; ?>', '<?php echo addslashes(htmlspecialchars($row['location'])); ?>', '<?php echo $row['file_path'] ? $mediaSrc : ''; ?>', '<?php echo $row['file_path'] ? basename($filePath) : ''; ?>')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="delete-btn" onclick="showArchiveModal(<?php echo $row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['title'])); ?>')">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Archive Confirmation Modal -->
<div class="modal" id="archiveModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-archive"></i> Archive Event</h3>
            <button type="button" class="close" onclick="closeArchiveModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to archive this event?</p>
            <p style="font-weight: 600; margin-top: 10px;" id="archiveEventTitle"></p>
            <p style="color: #666; font-size: 0.9em; margin-top: 10px;">
                <i class="fas fa-info-circle"></i> This will remove the event from the upcoming events list but can be restored later.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="cancel-btn" onclick="closeArchiveModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="confirm-delete-btn" onclick="confirmArchive()">
                <i class="fas fa-archive"></i> Archive
            </button>
        </div>
    </div>
</div>


<script>
let currentEventId = null;
let currentArchiveEventId = null;

function showArchiveModal(eventId, eventTitle) {
    currentArchiveEventId = eventId;
    document.getElementById('archiveEventTitle').textContent = eventTitle;
    document.getElementById('archiveModal').classList.add('show');
}

function closeArchiveModal() {
    document.getElementById('archiveModal').classList.remove('show');
    setTimeout(() => {
        currentArchiveEventId = null;
        document.getElementById('archiveEventTitle').textContent = '';
    }, 300);
}

function confirmArchive() {
    if (currentArchiveEventId) {
        window.location.href = 'archive_event.php?id=' + currentArchiveEventId;
    }
}


// Close modal when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const archiveModal = document.getElementById('archiveModal');
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
    if (event.target === archiveModal) {
        closeArchiveModal();
    }
}

// Add keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
        closeArchiveModal();
    }
});

// Add Event Modal Functions
function openAddEventModal() {
    document.getElementById('addEventModal').classList.add('show');
    
    // Set minimum date to today for the date input
    const dateInput = document.querySelector('#addEventModal input[type="date"]');
    const timeInput = document.querySelector('#addEventModal input[type="time"]');
    
    // Set min date to today
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;
    
    // Function to handle time validation
    function validateTime() {
        const selectedDate = dateInput.value;
        if (selectedDate === today) {
            const now = new Date();
            const currentHour = String(now.getHours()).padStart(2, '0');
            const currentMinute = String(now.getMinutes()).padStart(2, '0');
            const currentTime = `${currentHour}:${currentMinute}`;
            timeInput.min = currentTime;
        } else {
            timeInput.min = ''; // Reset min time if date is in future
        }
    }
    
    // Add event listeners
    dateInput.addEventListener('change', validateTime);
    timeInput.addEventListener('input', function() {
        if (dateInput.value === today && timeInput.value < timeInput.min) {
            alert('Cannot select a past time for today\'s events.');
            timeInput.value = timeInput.min;
        }
    });
    
    // Initial validation
    validateTime();

    // Media preview functionality
    const mediaInput = document.querySelector('#addEventModal input[type="file"]');
    const mediaPreview = document.querySelector('#addEventModal #media-preview');
    const imagePreview = document.querySelector('#addEventModal #image-preview');
    const videoPreview = document.querySelector('#addEventModal #video-preview');

    mediaInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const fileType = file.type;
            
            if (fileType.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    videoPreview.style.display = 'none';
                    mediaPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else if (fileType.startsWith('video/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    videoPreview.src = e.target.result;
                    videoPreview.style.display = 'block';
                    imagePreview.style.display = 'none';
                    mediaPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        } else {
            mediaPreview.style.display = 'none';
        }
    });
}

function closeAddEventModal() {
    document.getElementById('addEventModal').classList.remove('show');
    
    // Reset form
    const form = document.querySelector('#addEventModal form');
    form.reset();
    
    // Hide media preview
    const mediaPreview = document.querySelector('#addEventModal #media-preview');
    mediaPreview.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const archiveModal = document.getElementById('archiveModal');
    const addEventModal = document.getElementById('addEventModal');
    const editEventModal = document.getElementById('editEventModal');
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
    if (event.target === archiveModal) {
        closeArchiveModal();
    }
    if (event.target === addEventModal) {
        closeAddEventModal();
    }
    if (event.target === editEventModal) {
        closeEditEventModal();
    }
}

// Add keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
        closeArchiveModal();
        closeAddEventModal();
        closeEditEventModal();
    }
});

// Edit Event Modal Functions
function openEditEventModal(eventId, title, description, date, duration, time, location, mediaPath, mediaName) {
    // Populate form fields
    document.getElementById('editEventId').value = eventId;
    document.getElementById('editTitle').value = title;
    document.getElementById('editDescription').value = description;
    document.getElementById('editDate').value = date;
    document.getElementById('editDuration').value = duration;
    document.getElementById('editTime').value = time;
    document.getElementById('editLocation').value = location;
    
    // Handle current media display
    const currentMediaPreview = document.getElementById('currentMediaPreview');
    const currentImagePreview = document.getElementById('currentImagePreview');
    const currentVideoPreview = document.getElementById('currentVideoPreview');
    const currentMediaName = document.getElementById('currentMediaName');
    
    if (mediaPath && mediaName) {
        currentMediaPreview.style.display = 'block';
        currentMediaName.textContent = mediaName;
        
        const fileExtension = mediaName.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
            currentImagePreview.src = mediaPath;
            currentImagePreview.style.display = 'block';
            currentVideoPreview.style.display = 'none';
        } else if (['mp4', 'webm', 'ogg'].includes(fileExtension)) {
            currentVideoPreview.src = mediaPath;
            currentVideoPreview.style.display = 'block';
            currentImagePreview.style.display = 'none';
        }
    } else {
        currentMediaPreview.style.display = 'none';
    }
    
    // Reset new media preview
    const editMediaPreview = document.getElementById('editMediaPreview');
    const editImagePreview = document.getElementById('editImagePreview');
    const editVideoPreview = document.getElementById('editVideoPreview');
    editMediaPreview.style.display = 'none';
    editImagePreview.style.display = 'none';
    editVideoPreview.style.display = 'none';
    editImagePreview.src = '';
    editVideoPreview.src = '';
    
    // Clear file input
    document.getElementById('editMedia').value = '';
    
    // Show modal
    document.getElementById('editEventModal').classList.add('show');
}

function closeEditEventModal() {
    document.getElementById('editEventModal').classList.remove('show');
    
    // Reset form
    const form = document.querySelector('#editEventModal form');
    form.reset();
    
    // Hide media previews
    const currentMediaPreview = document.getElementById('currentMediaPreview');
    const editMediaPreview = document.getElementById('editMediaPreview');
    const editImagePreview = document.getElementById('editImagePreview');
    const editVideoPreview = document.getElementById('editVideoPreview');
    
    currentMediaPreview.style.display = 'none';
    editMediaPreview.style.display = 'none';
    editImagePreview.style.display = 'none';
    editVideoPreview.style.display = 'none';
    editImagePreview.src = '';
    editVideoPreview.src = '';
}

// Edit media preview functionality
document.addEventListener('DOMContentLoaded', function() {
    const editMediaInput = document.querySelector('#editEventModal input[type="file"]');
    const editMediaPreview = document.querySelector('#editEventModal #editMediaPreview');
    const editImagePreview = document.querySelector('#editEventModal #editImagePreview');
    const editVideoPreview = document.querySelector('#editEventModal #editVideoPreview');

    if (editMediaInput) {
        editMediaInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileType = file.type;
                
                if (fileType.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        editImagePreview.src = e.target.result;
                        editImagePreview.style.display = 'block';
                        editVideoPreview.style.display = 'none';
                        editMediaPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else if (fileType.startsWith('video/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        editVideoPreview.src = e.target.result;
                        editVideoPreview.style.display = 'block';
                        editImagePreview.style.display = 'none';
                        editMediaPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            } else {
                editMediaPreview.style.display = 'none';
            }
        });
    }
    
    // Delete current media functionality
    const deleteCurrentMediaBtn = document.getElementById('deleteCurrentMedia');
    if (deleteCurrentMediaBtn) {
        deleteCurrentMediaBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete the current media?')) {
                // Hide current media preview
                const currentMediaPreview = document.getElementById('currentMediaPreview');
                currentMediaPreview.style.display = 'none';
                
                // Add a hidden input to indicate media deletion
                const form = document.getElementById('editEventForm');
                let deleteMediaInput = document.getElementById('deleteMediaFlag');
                if (!deleteMediaInput) {
                    deleteMediaInput = document.createElement('input');
                    deleteMediaInput.type = 'hidden';
                    deleteMediaInput.name = 'delete_media';
                    deleteMediaInput.id = 'deleteMediaFlag';
                    form.appendChild(deleteMediaInput);
                }
            }
        });
    }
});
</script>

<!-- Add Event Modal -->
<div class="popup" id="addEventModal">
    <div class="popup-wrapper">
        <div class="popup-content">
            <div class="popup-header">
                <h3><i class="fas fa-calendar-plus"></i> Add Event</h3>
                <span class="close" onclick="closeAddEventModal()">&times;</span>
            </div>
            
            <div class="popup-body">
                <form action="add_event.php" method="POST" enctype="multipart/form-data" id="eventForm">
                    <div class="form-group">
                        <label for="title"><i class="fas fa-heading"></i> Event Title</label>
                        <input type="text" name="title" id="title" required placeholder="Enter event title">
                    </div>

                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="description" required placeholder="Enter event description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date"><i class="far fa-calendar"></i> Date</label>
                        <input type="date" name="date" id="date" required>
                    </div>

                    <div class="form-group">
                        <label for="duration"><i class="far fa-clock"></i> Duration (days)</label>
                        <input type="number" name="duration" id="duration" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="time"><i class="far fa-clock"></i> Time</label>
                        <input type="time" name="time" id="time" required>
                    </div>

                    <div class="form-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" name="location" id="location" required placeholder="Enter event location">
                    </div>

                    <div class="form-group">
                        <label for="media"><i class="fas fa-image"></i> Media (optional)</label>
                        <div class="file-input-container">
                            <label for="media" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i> Choose a file or drag it here
                            </label>
                            <input type="file" name="media" id="media" accept="image/*,video/*">
                        </div>
                        <div id="media-preview" class="media-preview" style="display: none;">
                            <img id="image-preview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;">
                            <video id="video-preview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;" controls></video>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="popup-footer">
                <button type="button" onclick="closeAddEventModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="eventForm">
                    <i class="fas fa-plus"></i> Add Event
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="popup" id="editEventModal">
    <div class="popup-wrapper">
        <div class="popup-content">
            <div class="popup-header">
                <h3><i class="fas fa-edit"></i> Edit Event</h3>
                <span class="close" onclick="closeEditEventModal()">&times;</span>
            </div>
            
            <div class="popup-body">
                <form action="edit_event.php" method="POST" enctype="multipart/form-data" id="editEventForm">
                    <input type="hidden" name="event_id" id="editEventId">
                    
                    <div class="form-group">
                        <label for="editTitle"><i class="fas fa-heading"></i> Event Title</label>
                        <input type="text" name="title" id="editTitle" required placeholder="Enter event title">
                    </div>

                    <div class="form-group">
                        <label for="editDescription"><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="editDescription" required placeholder="Enter event description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editDate"><i class="far fa-calendar"></i> Date</label>
                        <input type="date" name="date" id="editDate" required>
                    </div>

                    <div class="form-group">
                        <label for="editDuration"><i class="far fa-clock"></i> Duration (days)</label>
                        <input type="number" name="duration" id="editDuration" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="editTime"><i class="far fa-clock"></i> Time</label>
                        <input type="time" name="time" id="editTime" required>
                    </div>

                    <div class="form-group">
                        <label for="editLocation"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" name="location" id="editLocation" required placeholder="Enter event location">
                    </div>

                    <div class="form-group">
                        <label for="editMedia"><i class="fas fa-image"></i> Media (optional)</label>
                        <div id="currentMediaPreview" class="current-media" style="display: none;">
                            <div class="media-preview">
                                <img id="currentImagePreview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;">
                                <video id="currentVideoPreview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;" controls></video>
                            </div>
                            <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <i class="fas fa-file"></i> <span id="currentMediaName">Current media</span>
                                </div>
                                <button type="button" id="deleteCurrentMedia" class="delete-media-btn" style="background: none; border: none; color: #ff4d4d; cursor: pointer; padding: 5px;">
                                    <i class="fas fa-trash"></i> Delete Media
                                </button>
                            </div>
                        </div>
                        <div class="file-input-container">
                            <label for="editMedia" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i> Choose a new file or drag it here
                            </label>
                            <input type="file" name="media" id="editMedia" accept="image/*,video/*">
                        </div>
                        <div id="editMediaPreview" class="media-preview" style="display: none;">
                            <img id="editImagePreview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;">
                            <video id="editVideoPreview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;" controls></video>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="popup-footer">
                <button type="button" onclick="closeEditEventModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="editEventForm">
                    <i class="fas fa-save"></i> Update Event
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
