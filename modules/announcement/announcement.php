<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Fetch all announcements with media (left join in case some announcements have no media)
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
    WHERE a.is_archived = FALSE
    GROUP BY a.id
    ORDER BY 
        CASE WHEN a.announcement_date IS NULL THEN 1 ELSE 0 END ASC,
        a.announcement_date ASC, 
        a.announcement_time ASC
";
$stmt = $conn->prepare($query);
if (!$stmt->execute()) {
    die('Error fetching announcements: ' . $stmt->error);
}
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Update archive status for passed announcements
$current_datetime = date('Y-m-d H:i:s');
$archive_query = "
    UPDATE announcements 
    SET is_archived = TRUE 
    WHERE (
        (announcement_date IS NOT NULL AND announcement_time IS NOT NULL AND CONCAT(announcement_date, ' ', announcement_time) < ?)
        OR
        (announcement_date IS NULL AND announcement_time IS NULL AND created_at < ?)
    )
    AND is_archived = FALSE
";
$archive_stmt = $conn->prepare($archive_query);
$archive_stmt->bind_param("ss", $current_datetime, $current_datetime);
$archive_stmt->execute();
$archive_stmt->close();

// Separate active and archived announcements
$active_announcements = array_filter($announcements, function($ann) use ($current_datetime) {
    // Include tentative announcements (no date) and future announcements
    if ($ann['announcement_date'] === null) {
        return !$ann['is_archived']; // Tentative announcements are always active unless archived
    }
    $effective_date = $ann['effective_date'];
    return $effective_date >= $current_datetime && !$ann['is_archived'];
});

$archived_announcements = array_filter($announcements, function($ann) {
    return $ann['is_archived'];
});

// Sort announcements by effective date (tentative announcements first)
usort($active_announcements, function($a, $b) {
    // Tentative announcements (no date) should appear first
    if ($a['announcement_date'] === null && $b['announcement_date'] !== null) {
        return -1; // $a comes first
    }
    if ($a['announcement_date'] !== null && $b['announcement_date'] === null) {
        return 1; // $b comes first
    }
    // Both have dates or both are tentative, sort by effective date
    return strcmp($a['effective_date'], $b['effective_date']);
});

usort($archived_announcements, function($a, $b) {
    return strcmp($b['effective_date'], $a['effective_date']); // Reverse order for archived
});

$announcements = array_merge($active_announcements, $archived_announcements);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcements</title>
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
            color: white;
            padding: 15px 20px;
            font-size: 2em;
            font-weight: 600;
            letter-spacing: -0.5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            margin: 20px auto;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 90%;
            max-width: 1200px;
            border-radius: 10px;
            position: relative;
            z-index: 1;
        }
        .announcement-container {
            width: 90%;
            max-width: 1200px;
            background: #ffffff;
            margin: 30px auto;
            padding: 70px 30px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        .announcement {
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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
        }
        .announcement p {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .announcement small {
            color: #666;
            font-size: 0.9em;
            display: block;
            margin-top: 15px;
            font-style: italic;
        }
        .announcement .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .announcement .actions {
            display: none;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .announcement:hover .actions {
            display: flex;
        }
        .announcement .actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7B0000;
            transition: all 0.3s ease;
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .announcement .actions button:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .announcement .actions button i {
            font-size: 1em;
            color: #7B0000;
        }
        .no-announcements {
            text-align: center;
            padding: 40px 20px;
            margin-top: 20px;
        }
        .no-announcements p {
            font-size: 1.2em;
            color: #666;
            margin: 0;
        }
        .buttons {
            position: absolute;
            top: 20px;
            right: 30px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin: 0;
            z-index: 2;
        }
        .buttons button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #7B0000;
            background: rgba(123, 0, 0, 0.1);
            border: none;
            padding: 10px 20px;
            font-size: 1em;
            cursor: pointer;
            border-radius: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .buttons button:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .buttons .archive-btn {
            background: rgba(128, 128, 128, 0.1);
            border-color: rgba(128, 128, 128, 0.2);
            color: #666;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            text-align: center;
        }
        .buttons .archive-btn:hover {
            background: rgba(128, 128, 128, 0.2);
        }
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
        .popup-body {
            padding: 0;
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
        .popup-footer button[type="submit"],
        .popup-footer .delete-btn {
            background: #7B0000;
            color: white;
            border: 1px solid #7B0000;
        }
        .popup-footer button[type="submit"]:hover,
        .popup-footer .delete-btn:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }
        .popup-footer button[type="submit"]:disabled,
        .popup-footer .delete-btn:disabled {
            background: rgba(123, 0, 0, 0.5);
            color: rgba(255, 255, 255, 0.7);
            cursor: not-allowed;
            transform: none;
            border-color: rgba(123, 0, 0, 0.5);
        }
        .popup-footer button[type="submit"]:disabled:hover,
        .popup-footer .delete-btn:disabled:hover {
            background: rgba(123, 0, 0, 0.5);
            transform: none;
        }
        .popup-footer .archive-btn {
            background: #FF8C00;
            color: white;
            border: 1px solid #FF8C00;
        }
        .popup-footer .archive-btn:hover {
            background: #FF7F00;
            transform: translateY(-2px);
        }
        .info-text {
            color: #666;
            font-size: 0.9em;
            padding: 10px;
            background: rgba(255, 140, 0, 0.1);
            border-radius: 8px;
            border-left: 4px solid #FF8C00;
        }
        .info-text i {
            color: #FF8C00;
            margin-right: 5px;
        }
        .alert {
            padding: 15px 20px;
            margin: 20px 30px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1em;
            font-weight: 500;
        }
        .alert-success {
            background: rgba(46, 160, 67, 0.1);
            color: #2ea043;
            border: 1px solid rgba(46, 160, 67, 0.2);
        }
        .alert-error {
            background: rgba(248, 81, 73, 0.1);
            color: #f85149;
            border: 1px solid rgba(248, 81, 73, 0.2);
        }
        .alert i {
            font-size: 1.2em;
        }
        .close {
            position: absolute;
            top: 0;
            right: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            font-size: 20px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }
        .close:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.1);
        }
        form {
            display: grid;
            gap: 24px;
        }
        form .form-group {
            display: grid;
            gap: 6px;
        }
        form label {
            font-weight: 500;
            color: #7B0000;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        form label i {
            color: #7B0000;
            width: 16px;
        }
        form input[type="text"],
        form textarea {
            width: 100%;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #333;
            transition: all 0.3s ease;
        }
        form input[type="date"],
        form input[type="time"] {
            width: 100%;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #333;
            transition: all 0.3s ease;
        }
        form input[type="date"]::-webkit-calendar-picker-indicator,
        form input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.5;
            cursor: pointer;
        }
        form input[type="date"]:focus,
        form input[type="time"]:focus {
            outline: none;
            border-color: rgba(255, 77, 77, 0.3);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.1);
        }
        .file-input-wrapper {
            position: relative;
            width: 100%;
        }
        .file-input-wrapper label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: #ffffff;
            border: 2px dashed #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: normal;
            color: #666;
        }
        .file-input-wrapper label:hover {
            border-color: #7B0000;
            background: rgba(123, 0, 0, 0.05);
        }
        .file-input-wrapper .file-info {
            margin-top: 8px;
            display: none;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        .file-input-wrapper .remove-file {
            color: #ff4d4d;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .file-input-wrapper .remove-file:hover {
            background: rgba(255, 77, 77, 0.1);
        }
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .help-text i {
            color: #7B0000;
            font-size: 14px;
        }
        .character-count {
            font-size: 0.8rem;
            color: #666;
            text-align: right;
            margin-top: 4px;
        }
        .character-count.warning {
            color: #7B0000;
        }
        .character-count.danger {
            color: #7B0000;
        }
        @media (max-width: 768px) {
            .popup {
                padding: 15px;
            }
            
            .popup-content {
                padding: 20px;
                margin: 10px 0;
                max-height: 90vh;
            }
            .popup-body {
                margin: 0 -5px;
                padding: 0 5px;
            }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .announcement-container {
                width: 98%;
                padding: 20px 10px;
                margin: 10px auto;
            }

            .header {
                font-size: 1.4em;
                padding: 10px 12px;
                margin: 10px auto;
                width: 98%;
            }

            .buttons {
                position: static;
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }

            .buttons button {
                padding: 10px 16px;
                font-size: 0.9em;
                flex: 1;
                min-width: 140px;
                max-width: 200px;
                justify-content: center;
                text-align: center;
            }

            .no-announcements {
                text-align: center;
                padding: 30px 20px;
                margin-top: 0;
            }

            .no-announcements p {
                font-size: 1.1em;
                color: #666;
                margin: 0;
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

            .announcement .meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .announcement .meta small {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                width: 100%;
            }

            .announcement .actions {
                display: flex;
                flex-direction: row;
                gap: 8px;
                margin-top: 0;
                justify-content: flex-start;
            }

            .announcement .actions button {
                padding: 6px 10px;
                font-size: 0.75em;
                flex: 0 0 auto;
                justify-content: center;
            }

            .media-container {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .media-item {
                height: 150px;
            }

            .popup-content {
                width: 98%;
                padding: 12px;
            }

            .popup-content h3 {
                font-size: 1.3em;
            }

            .view-popup-content {
                width: 98%;
                padding: 15px;
                margin: 10px auto;
            }

            .view-popup-content h3 {
                font-size: 20px;
                margin-bottom: 12px;
            }

            .view-popup-content p {
                font-size: 14px;
            }

            .view-popup-content .meta {
                flex-direction: column;
                gap: 8px;
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

        /* Extra Small Mobile Devices */
        @media (max-width: 360px) {
            .announcement-container {
                padding: 15px 8px;
                margin: 8px auto;
            }

            .header {
                font-size: 1.2em;
                padding: 8px 10px;
                margin: 8px auto;
            }

            .buttons {
                position: static;
                margin-bottom: 15px;
                gap: 8px;
            }

            .buttons button {
                padding: 8px 12px;
                font-size: 0.8em;
                min-width: 120px;
                max-width: 160px;
                justify-content: center;
                text-align: center;
            }

            .no-announcements {
                padding: 25px 15px;
                margin-top: 0;
            }

            .no-announcements p {
                font-size: 1em;
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

            .announcement .meta small {
                font-size: 0.8em;
            }

            .announcement .actions button {
                padding: 5px 8px;
                font-size: 0.7em;
                flex: 0 0 auto;
            }

            .media-item {
                height: 120px;
            }

            .popup-content {
                padding: 10px;
            }

            .view-popup-content {
                padding: 12px;
            }

            .view-popup-content h3 {
                font-size: 18px;
            }

            .view-popup-content p {
                font-size: 13px;
            }
        }

        /* Landscape Mobile Orientation */
        @media (max-width: 768px) and (orientation: landscape) {
            .announcement-container {
                padding: 40px 15px 15px;
            }

            .buttons {
                flex-direction: row;
                justify-content: center;
            }

            .buttons button {
                width: auto;
                max-width: none;
            }

            .announcement .actions {
                flex-direction: row;
                justify-content: center;
            }

            .announcement .actions button {
                width: auto;
            }

            .popup-footer {
                flex-direction: row;
            }

            .popup-footer button {
                width: auto;
            }
        }

        /* Tablet devices */
        @media (min-width: 769px) and (max-width: 1024px) {
            .announcement-container {
                width: 92%;
                padding: 65px 25px 25px;
            }

            .header {
                font-size: 1.8em;
                padding: 14px 18px;
            }

            .announcement {
                padding: 18px;
            }

            .media-item {
                height: 180px;
            }
        }

        /* Landscape orientation on mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .announcement-container {
                padding: 50px 15px 15px;
            }

            .popup-content {
                max-height: 85vh;
            }

            .view-popup-content {
                max-height: 85vh;
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .announcement {
                border-width: 0.5px;
            }
        }
        .announcement-image {
            width: 100%;
            margin: 15px 0;
            border-radius: 8px;
            max-height: 400px;
            object-fit: contain;
            background: #f8f9fa;
            padding: 10px;
        }
        .announcement-image.loading {
            min-height: 200px;
            background: #f8f9fa url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" stroke="%237B0000" stroke-width="8" fill="none" stroke-dasharray="180 60" transform="rotate(0 50 50)"><animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="1s" repeatCount="indefinite"/></circle></svg>') center/50px no-repeat;
        }
        @media (max-width: 640px) {
            .view-popup-content {
                margin: 10px auto;
                padding: 20px;
            }
            
            .view-popup-content h3 {
                font-size: 24px;
                margin-bottom: 15px;
            }
            
            .announcement-image {
                max-height: 300px;
            }
        }
        .popup-footer button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .error-message {
            color: #ff0000;
            margin-top: 5px;
            font-size: 14px;
        }
        .success-message {
            color: #008000;
            margin-top: 5px;
            font-size: 14px;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .loading i {
            color: #7B0000;
            font-size: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .view-popup {
            display: none;
            position: fixed;
            inset: 0;
            min-height: 100vh;
            width: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1002;
            overflow-y: auto;
            padding: 60px 0;
        }
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1002;
            cursor: pointer;
        }
        .image-modal .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        .image-modal img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }
        .image-modal .close-button {
            position: absolute;
            top: -40px;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .image-modal .gallery-nav {
            position: absolute;
            bottom: -60px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .image-modal .gallery-nav button {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            color: #333;
            font-size: 14px;
        }
        .image-modal .gallery-nav button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .view-popup-content {
            background: #ffffff;
            width: min(800px, 95%);
            max-height: min(800px, 90vh);
            margin: 20px auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow-y: auto;
        }
        .view-popup-content h3 {
            color: #7B0000;
            font-size: 28px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            padding-right: 40px;
        }
        .view-popup-content p {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }
        .view-popup-content .meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .view-popup-content .meta i {
            width: 16px;
            text-align: center;
            margin-right: 5px;
        }
        .announcement .schedule {
            background: rgba(123, 0, 0, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            margin-bottom: 15px;
            display: inline-block;
            color: #7B0000;
            font-size: 0.9em;
        }
        .announcement .schedule i {
            margin-right: 5px;
        }
        .announcement .tentative {
            background: rgba(255, 193, 7, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            margin-bottom: 15px;
            display: inline-block;
            color: #856404;
            font-size: 0.9em;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .announcement .tentative i {
            margin-right: 5px;
            color: #856404;
        }
        .archive-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(128, 128, 128, 0.1);
            color: #666;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
            vertical-align: middle;
        }
        .media-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            cursor: move;
            transition: transform 0.2s ease;
            background: rgba(0, 0, 0, 0.2);
            user-select: none;
            flex: 0 0 200px;
            min-width: 200px;
        }
        
        .media-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .media-item:active {
            cursor: grabbing;
        }
        
        .media-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .media-item.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }
        
        .media-order-handle {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            pointer-events: none;
        }
        
        /* Current Media Display Styles */
        .current-media-display {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }
        
        .current-media-header {
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .current-media-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .current-media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            border: 1px solid #e0e0e0;
        }
        
        .current-media-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .current-media-item .media-info {
            padding: 8px;
            font-size: 0.8em;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .current-media-item .delete-current-media {
            background: none;
            border: none;
            color: #ff4d4d;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .current-media-item .delete-current-media:hover {
            background: rgba(255, 77, 77, 0.1);
        }
    </style>
</head>
<body>

<div class="header">
    <span>Announcements</span>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="announcement-container">
    <div class="buttons">
        <button id="openPopupBtn">
            <i class="fas fa-plus"></i> Add Announcement
        </button>
        <?php if ($user_role === ROLE_ADMIN): ?>
        <button onclick="window.location.href='archived_announcements.php'" class="archive-btn">
            <i class="fas fa-archive"></i> View Archives
        </button>
        <?php endif; ?>
    </div>

    <?php if (empty($announcements)): ?>
        <div class="no-announcements">
            <p>No announcements yet. Be the first to create one!</p>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $index => $announcement): ?>
            <div class="announcement" data-id="<?php echo htmlspecialchars($announcement['id']); ?>">
                <?php if (!empty($announcement['announcement_date']) && !empty($announcement['announcement_time'])): ?>
                <div class="schedule">
                    <i class="fas fa-calendar-alt"></i>
                    <?php 
                    $date = date('F j, Y', strtotime($announcement['announcement_date']));
                    $time = !empty($announcement['announcement_time']) ? date('g:i A', strtotime($announcement['announcement_time'])) : '';
                    echo htmlspecialchars($time ? "Scheduled for $date at $time" : "Scheduled for $date"); 
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
                    Tentative - Date to be announced
                </div>
                <?php endif; ?>
                <h3>
                    <?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <p><?= nl2br(htmlspecialchars($announcement['content'], ENT_QUOTES, 'UTF-8')) ?></p>
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
                            <div class="media-item" data-media-id="<?= htmlspecialchars($media['media_id']) ?>">
                                <img src="../../multimedia/<?= htmlspecialchars($media['file_path']) ?>" 
                                     alt="<?= htmlspecialchars($announcement['title']) ?>" 
                                     class="announcement-image"
                                     onclick="openImageModal(this.src)"
                                     draggable="false">
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
                        <i class="fas fa-user"></i> <?= htmlspecialchars($announcement['author_name'] ?? 'Unknown User', ENT_QUOTES, 'UTF-8') ?>
                        <i class="fas fa-clock"></i> <?= date('M d, Y h:i A', strtotime($announcement['created_at'])) ?>
                    </small>
                    <div class="actions">
                        <button onclick="event.stopPropagation(); openEditPopup(<?= $index ?>)" title="Edit Announcement">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="event.stopPropagation(); showArchiveConfirmation(<?= $index ?>)" title="Archive Announcement">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Announcement Popup -->
<div class="popup" id="popupForm">
    <div class="popup-wrapper">
    <div class="popup-content">
            <div class="popup-header">
                <h3><i class="fas fa-bullhorn"></i> Add Announcement</h3>
        <span class="close" id="closePopupBtn">&times;</span>
            </div>
            
            <div class="popup-body">
        <form id="announcementForm" action="add_announcement.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">
                            <i class="fas fa-heading"></i> Title
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               placeholder="Enter announcement title" 
                               required 
                               maxlength="255" 
                               pattern="[A-Za-z0-9\s\-_.,!?()&@#$%^*+=|\\/:'\"`~]+" 
                               title="Letters, numbers, punctuation, and special characters allowed">
                        <div class="character-count">0/255 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="announcement_date">
                            <i class="fas fa-calendar"></i> Date
                        </label>
                        <input type="date" 
                               name="announcement_date" 
                               id="announcement_date">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            Select the date for the announcement (optional - leave empty for tentative announcements)
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="announcement_time">
                            <i class="fas fa-clock"></i> Time (Optional)
                        </label>
                        <input type="time" 
                               name="announcement_time" 
                               id="announcement_time">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            Select the time for the announcement (optional - requires date to be set)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">
                            <i class="fas fa-align-left"></i> Content
                        </label>
                        <textarea name="content" 
                                  id="content" 
                                  placeholder="Enter announcement content" 
                                  required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-image"></i> Media Attachment
                        </label>
                        <div class="file-input-wrapper">
                            <label for="media">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload images or drag and drop</span>
                            </label>
                            <input type="file" 
                                   name="media[]" 
                                   id="media" 
                                   accept="image/jpeg,image/png,image/gif"
                                   multiple>
                            <div class="file-info">
                                <i class="fas fa-file-image"></i>
                                <span class="file-name"></span>
                                <span class="remove-file" title="Remove files">
                                    <i class="fas fa-times"></i>
                                </span>
                            </div>
                            <div class="help-text">
                                <i class="fas fa-info-circle"></i>
                                Max file size: 5MB per file. Allowed formats: JPEG, PNG, GIF. You can select multiple files.
                            </div>
                        </div>
                    </div>
                    
            <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?>">
        </form>
            </div>
            
            <div class="popup-footer">
                <button type="button" id="cancelBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="announcementForm">
                    <i class="fas fa-paper-plane"></i> Post Announcement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Announcement Popup -->
<div class="view-popup" id="viewPopup">
    <div class="view-popup-content" id="announcementDetails">
        <span class="close" onclick="closeViewPopup()">&times;</span>
        <h3 id="viewTitle"></h3>
        <img id="viewImage" class="announcement-image" style="display: none;" alt="Announcement Image">
        <p id="viewContent"></p>
        <div class="meta">
            <span><i class="fas fa-user"></i> <span id="viewAuthor"></span></span>
            <span><i class="fas fa-clock"></i> <span id="viewDate"></span></span>
        </div>
    </div>
</div>

<!-- Edit Announcement Popup -->
<div class="popup" id="editPopupForm">
    <div class="popup-wrapper">
    <div class="popup-content">
            <div class="popup-header">
                <h3><i class="fas fa-edit"></i> Edit Announcement</h3>
        <span class="close" onclick="closeEditPopup()">&times;</span>
            </div>
            
            <div class="popup-body">
        <form id="editAnnouncementForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editId">
                    <div class="form-group">
                        <label for="editTitle">
                            <i class="fas fa-heading"></i> Title
                        </label>
                        <input type="text" 
                               name="title" 
                               id="editTitle" 
                               placeholder="Enter announcement title" 
                               required 
                               maxlength="255" 
                               pattern="[A-Za-z0-9\s\-_.,!?()&@#$%^*+=|\\/:'\"`~]+" 
                               title="Letters, numbers, punctuation, and special characters allowed">
                        <div class="character-count">0/255 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="editAnnouncementDate">
                            <i class="fas fa-calendar"></i> Date
                        </label>
                        <input type="date" 
                               name="announcement_date" 
                               id="editAnnouncementDate">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            Select the date for the announcement (optional - leave empty for tentative announcements)
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="editAnnouncementTime">
                            <i class="fas fa-clock"></i> Time (Optional)
                        </label>
                        <input type="time" 
                               name="announcement_time" 
                               id="editAnnouncementTime">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            Select the time for the announcement (optional - requires date to be set)
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="editContent">
                            <i class="fas fa-align-left"></i> Content
                        </label>
                        <textarea name="content" 
                                  id="editContent" 
                                  placeholder="Enter announcement content" 
                                  required></textarea>
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-image"></i> Media Attachment
                        </label>
                        
                        <!-- Current Media Display -->
                        <div id="editCurrentMedia" class="current-media-display" style="display: none;">
                            <div class="current-media-header">
                                <i class="fas fa-images"></i> Current Media Files
                            </div>
                            <div id="editCurrentMediaList" class="current-media-list">
                                <!-- Current media will be populated here -->
                            </div>
                        </div>
                        
                        <div class="file-input-wrapper">
                            <label for="editMedia">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload new images or drag and drop</span>
                            </label>
                            <input type="file" 
                                   name="media[]" 
                                   id="editMedia" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   multiple>
                            <div class="file-info">
                                <i class="fas fa-file-image"></i>
                                <span class="file-name"></span>
                                <span class="remove-file" title="Remove file">
                                    <i class="fas fa-times"></i>
                                </span>
                            </div>
                            <div class="help-text">
                                <i class="fas fa-info-circle"></i>
                                Max file size: 5MB. Allowed formats: JPEG, PNG, GIF, WebP
                            </div>
                        </div>
                    </div>
            <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?>">
                </form>
            </div>
            
            <div class="popup-footer">
                <button type="button" onclick="closeEditPopup()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="editAnnouncementForm">
                <i class="fas fa-save"></i> Save Changes
            </button>
            </div>
        </div>
    </div>
</div>

<!-- Archive Confirmation Modal -->
<div class="popup" id="archiveConfirmModal">
    <div class="popup-wrapper">
        <div class="popup-content">
            <div class="popup-header">
                <h3><i class="fas fa-archive"></i> Archive Announcement</h3>
                <span class="close" onclick="closeArchiveModal()">&times;</span>
            </div>
            
            <div class="popup-body">
                <p>Are you sure you want to archive this announcement?</p>
                <div class="announcement-title" id="archiveTitle"></div>
                <p class="info-text">
                    <i class="fas fa-info-circle"></i> This announcement will be moved to archives and can be restored later.
                </p>
            </div>
            
            <div class="popup-footer">
                <button type="button" onclick="closeArchiveModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="archive-btn" onclick="confirmArchive()">
                    <i class="fas fa-archive"></i> Archive
                </button>
            </div>
        </div>
    </div>
</div>

<div class="loading" id="loading">
    <i class="fas fa-spinner"></i>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    let currentAnnouncementIndex = -1;
    const announcements = <?= json_encode($announcements) ?>;

    // Show loading spinner
    function showLoading() {
        document.getElementById('loading').style.display = 'block';
    }

    // Hide loading spinner
    function hideLoading() {
        document.getElementById('loading').style.display = 'none';
    }

    // Show success message
    function showSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.textContent = message;
        document.querySelector('.announcement-container').insertBefore(
            successDiv,
            document.querySelector('.buttons')
        );
        setTimeout(() => successDiv.remove(), 3000);
    }

    // Show error message
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        document.querySelector('.announcement-container').insertBefore(
            errorDiv,
            document.querySelector('.buttons')
        );
        setTimeout(() => errorDiv.remove(), 3000);
    }

    document.getElementById("openPopupBtn").onclick = () => {
        const popup = document.getElementById("popupForm");
        popup.classList.add('show');
        // Reset form when opening
        document.getElementById('announcementForm').reset();
        document.querySelector('.file-info').classList.remove('show');
    };

    document.getElementById("closePopupBtn").onclick = () => {
        const popup = document.getElementById("popupForm");
        popup.classList.remove('show');
    };

    function showAnnouncement(index) {
        currentAnnouncementIndex = index;
        const ann = announcements[index];
        
        document.getElementById("viewTitle").innerText = ann.title;
        document.getElementById("viewContent").innerText = ann.content;

        const image = document.getElementById("viewImage");
        if (ann.file_path) {
            image.classList.add('loading');
            image.style.display = "block";
            image.src = '../../multimedia/' + ann.file_path;
            image.onload = () => image.classList.remove('loading');
            image.onerror = () => {
                image.style.display = "none";
                showError('Failed to load image');
            };
        } else {
            image.style.display = "none";
        }
        
        document.getElementById("viewAuthor").textContent = ann.author_name || 'Unknown User';
        document.getElementById("viewDate").textContent = new Date(ann.created_at).toLocaleString();

        document.getElementById("viewPopup").style.display = "flex";
        
        // Add animation class
        requestAnimationFrame(() => {
            document.getElementById("announcementDetails").classList.add('show');
        });
    }

    function closeViewPopup() {
        const popup = document.getElementById("viewPopup");
        const details = document.getElementById("announcementDetails");
        details.classList.remove('show');
        setTimeout(() => popup.style.display = "none", 200);
    }

    function openEditPopup(index) {
        const ann = announcements[index];
        
        // Debug: Log the announcement data
        console.log('Announcement data:', ann);
        console.log('announcement_time value:', ann.announcement_time);
        console.log('announcement_time type:', typeof ann.announcement_time);
        
        // Store original values for comparison
        window.originalFormValues = {
            title: ann.title,
            content: ann.content,
            date: ann.announcement_date || '',
            time: ann.announcement_time || '',
            mediaFiles: [] // We'll track new file selections separately
        };

        document.getElementById("editId").value = ann.id;
        document.getElementById("editTitle").value = ann.title;
        document.getElementById("editContent").value = ann.content;
        document.getElementById("editAnnouncementDate").value = ann.announcement_date || '';
        
        // Fix time format - ensure it's in HH:MM format for HTML input
        let timeValue = ann.announcement_time || '';
        console.log('Original timeValue:', timeValue);
        
        if (timeValue) {
            // If time is in HH:MM:SS format, convert to HH:MM for HTML input
            if (timeValue.length === 8 && timeValue.includes(':')) {
                timeValue = timeValue.substring(0, 5); // Extract HH:MM part
            } else if (timeValue.length === 5 && timeValue.includes(':')) {
                // Already in correct format
                timeValue = timeValue;
            } else {
                // Invalid format, clear it
                timeValue = '';
            }
        }
        console.log('Final timeValue being set:', timeValue);
        document.getElementById("editAnnouncementTime").value = timeValue;
        
        // Load and display current media
        loadCurrentMedia(ann.id);
        
        // Update character count
        const charCount = document.querySelector('#editPopupForm .character-count');
        const length = ann.title.length;
        charCount.textContent = `${length}/255 characters`;
        if (length > 200) {
            charCount.className = 'character-count warning';
        } else if (length > 240) {
            charCount.className = 'character-count danger';
        } else {
            charCount.className = 'character-count';
        }

        // Reset file input and info
        const fileInput = document.getElementById('editMedia');
        const fileInfo = document.querySelector('#editPopupForm .file-info');
        fileInput.value = '';
        fileInfo.classList.remove('show');

        // Show the modal
        const popup = document.getElementById("editPopupForm");
        popup.classList.add('show');

        // Initially disable submit button
        const submitBtn = document.querySelector('#editPopupForm button[type="submit"]');
        submitBtn.disabled = true;

        // Add change listeners to form inputs
        const form = document.getElementById('editAnnouncementForm');
        const inputs = form.querySelectorAll('input, textarea');
        
        function checkFormChanges() {
            const currentValues = {
                title: document.getElementById("editTitle").value,
                content: document.getElementById("editContent").value,
                date: document.getElementById("editAnnouncementDate").value,
                time: document.getElementById("editAnnouncementTime").value,
                mediaFiles: document.getElementById("editMedia").files.length > 0
            };

            const hasChanges = 
                currentValues.title !== window.originalFormValues.title ||
                currentValues.content !== window.originalFormValues.content ||
                currentValues.date !== window.originalFormValues.date ||
                currentValues.time !== window.originalFormValues.time ||
                currentValues.mediaFiles; // Any new files count as a change

            submitBtn.disabled = !hasChanges;
        }

        // Add change event listeners to all form inputs
        inputs.forEach(input => {
            input.addEventListener('input', checkFormChanges);
            input.addEventListener('change', checkFormChanges);
        });
    }

    function loadCurrentMedia(announcementId) {
        // Fetch current media for this announcement
        fetch(`get_announcement_media.php?id=${announcementId}`)
            .then(response => response.json())
            .then(data => {
                const currentMediaDiv = document.getElementById('editCurrentMedia');
                const currentMediaList = document.getElementById('editCurrentMediaList');
                
                if (data.success && data.media && data.media.length > 0) {
                    currentMediaDiv.style.display = 'block';
                    currentMediaList.innerHTML = '';
                    
                    data.media.forEach((media, index) => {
                        const mediaItem = document.createElement('div');
                        mediaItem.className = 'current-media-item';
                        mediaItem.innerHTML = `
                            <img src="../../multimedia/${media.file_path}" alt="Current media ${index + 1}">
                            <div class="media-info">
                                <span>${media.file_name}</span>
                                <button type="button" class="delete-current-media" onclick="deleteCurrentMedia(${announcementId}, ${media.media_id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        currentMediaList.appendChild(mediaItem);
                    });
                } else {
                    currentMediaDiv.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading current media:', error);
                document.getElementById('editCurrentMedia').style.display = 'none';
            });
    }
    
    function deleteCurrentMedia(announcementId, mediaId) {
        if (confirm('Are you sure you want to delete this media file?')) {
            fetch('delete_announcement_media.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `announcement_id=${announcementId}&media_id=${mediaId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload current media
                    loadCurrentMedia(announcementId);
                } else {
                    alert('Error deleting media: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting media:', error);
                alert('Error deleting media file');
            });
        }
    }

    function closeEditPopup() {
        const popup = document.getElementById("editPopupForm");
        popup.classList.remove('show');
        document.getElementById('editAnnouncementForm').reset();
        document.querySelector('#editPopupForm .file-info').classList.remove('show');
        
        // Hide current media display
        document.getElementById('editCurrentMedia').style.display = 'none';
        
        // Remove the stored original values
        window.originalFormValues = null;
        
        // Remove event listeners (they'll be re-added when the form opens again)
        const form = document.getElementById('editAnnouncementForm');
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.replaceWith(input.cloneNode(true));
        });
    }

    // Add character count for edit title
    document.getElementById('editTitle').addEventListener('input', function() {
        const length = this.value.length;
        const charCount = document.querySelector('#editPopupForm .character-count');
        charCount.textContent = `${length}/255 characters`;
        
        if (length > 200) {
            charCount.className = 'character-count warning';
        } else if (length > 240) {
            charCount.className = 'character-count danger';
        } else {
            charCount.className = 'character-count';
        }
    });

    // File handling for edit form
    document.getElementById('editMedia').addEventListener('change', function() {
        const fileInfo = document.querySelector('#editPopupForm .file-info');
        const fileName = document.querySelector('#editPopupForm .file-name');
        const files = Array.from(this.files);
        
        if (files.length > 0) {
            const fileNames = files.map(f => f.name).join(', ');
            fileName.textContent = files.length === 1 ? fileNames : `${files.length} files selected: ${fileNames}`;
            fileInfo.classList.add('show');
        } else {
            fileInfo.classList.remove('show');
        }
    });

    // Remove file in edit form
    document.querySelector('#editPopupForm .remove-file').addEventListener('click', function() {
        const mediaInput = document.getElementById('editMedia');
        const fileInfo = document.querySelector('#editPopupForm .file-info');
        mediaInput.value = '';
        fileInfo.classList.remove('show');
    });

    // Handle edit form submission
    document.getElementById('editAnnouncementForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = document.querySelector('#editPopupForm button[type="submit"]');
        
        // Disable submit button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        fetch('edit_announcement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Success - close modal and reload page
                closeEditPopup();
                showSuccess(data.message || 'Announcement updated successfully.');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error updating announcement:', error);
            showError('Failed to update announcement: ' + error.message);
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        });
    });

    function handleError(error, message) {
        console.error('Error:', error);
        showError(message || 'An error occurred. Please try again.');
        hideLoading();
    }

    let archiveAnnouncementIndex = -1;

    function showArchiveConfirmation(index) {
        event.stopPropagation();
        archiveAnnouncementIndex = index;
        const ann = announcements[index];
        
        document.getElementById("archiveTitle").textContent = ann.title;
        document.getElementById("archiveConfirmModal").classList.add('show');
    }

    function closeArchiveModal() {
        document.getElementById("archiveConfirmModal").classList.remove('show');
        archiveAnnouncementIndex = -1;
    }

    function confirmArchive() {
        if (archiveAnnouncementIndex === -1) return;
        
        const ann = announcements[archiveAnnouncementIndex];
        showLoading();
        
        fetch('archive_announcement.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `announcement_id=${encodeURIComponent(ann.id)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            closeArchiveModal();
            showSuccess('Announcement archived successfully.');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => handleError(error, 'Failed to archive announcement. Please try again.'));
    }


    // Enhanced form handling
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('title');
        const contentInput = document.getElementById('content');
        const mediaInput = document.getElementById('media');
        const dateInput = document.getElementById('announcement_date');
        const timeInput = document.getElementById('announcement_time');
        const fileInfo = document.querySelector('.file-info');
        const fileName = document.querySelector('.file-name');
        const removeFile = document.querySelector('.remove-file');
        const charCount = document.querySelector('.character-count');
        const cancelBtn = document.getElementById('cancelBtn');

        // Date validation setup
        function setupDateValidation(dateInput, timeInput) {
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
                // Clear time if no date is selected
                if (!dateInput.value && timeInput.value) {
                    alert('Please select a date first before setting a time, or leave both empty for tentative announcements.');
                    timeInput.value = '';
                    return;
                }
                
                if (dateInput.value === today && timeInput.value < timeInput.min) {
                    alert('Cannot select a past time for today\'s announcements.');
                    timeInput.value = timeInput.min;
                }
            });
            
            // Clear time when date is cleared
            dateInput.addEventListener('input', function() {
                if (!dateInput.value) {
                    timeInput.value = '';
                }
            });
            
            // Initial validation
            validateTime();
        }

        // Set up validation for add form
        if (dateInput && timeInput) {
            setupDateValidation(dateInput, timeInput);
        }

        // Set up validation for edit form
        const editDateInput = document.getElementById('editAnnouncementDate');
        const editTimeInput = document.getElementById('editAnnouncementTime');
        if (editDateInput && editTimeInput) {
            setupDateValidation(editDateInput, editTimeInput);
        }

        // Set default time to 00:00 when date is selected
        dateInput.addEventListener('change', function() {
            if (this.value && !timeInput.value) {
                timeInput.value = '00:00';
            }
        });

        // Set time to 00:00 when time input is clicked
        timeInput.addEventListener('click', function() {
            this.value = '00:00';
        });
        
        editDateInput.addEventListener('change', function() {
            if (this.value && !editTimeInput.value) {
                editTimeInput.value = '00:00';
            }
        });

        // Set time to 00:00 when edit time input is clicked
        editTimeInput.addEventListener('click', function() {
            this.value = '00:00';
        });

        // Title character count
        titleInput.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = `${length}/255 characters`;
            
            if (length > 200) {
                charCount.className = 'character-count warning';
            } else if (length > 240) {
                charCount.className = 'character-count danger';
            } else {
                charCount.className = 'character-count';
            }
        });

        // File handling
        mediaInput.addEventListener('change', function() {
            const files = Array.from(this.files);
            if (files.length > 0) {
                const fileNames = files.map(f => f.name).join(', ');
                fileName.textContent = files.length === 1 ? fileNames : `${files.length} files selected: ${fileNames}`;
                fileInfo.classList.add('show');
            } else {
                fileInfo.classList.remove('show');
            }
        });

        // Remove file
        removeFile.addEventListener('click', function() {
            mediaInput.value = '';
            fileInfo.classList.remove('show');
        });

        // Drag and drop support
        const dropZone = document.querySelector('.file-input-wrapper label');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.style.borderColor = '#7B0000';
            dropZone.style.background = '#fff';
            }
            
        function unhighlight(e) {
            dropZone.style.borderColor = '#ddd';
            dropZone.style.background = '#f8f9fa';
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = Array.from(dt.files).filter(file => file.type.startsWith('image/'));
            
            if (files.length > 0) {
                // Create a new DataTransfer object
                const newDt = new DataTransfer();
                files.forEach(file => newDt.items.add(file));
                
                mediaInput.files = newDt.files;
                const fileNames = files.map(f => f.name).join(', ');
                fileName.textContent = files.length === 1 ? fileNames : `${files.length} files selected: ${fileNames}`;
                fileInfo.classList.add('show');
            }
        }

        // Cancel button
        cancelBtn.addEventListener('click', function() {
            document.getElementById('announcementForm').reset();
            fileInfo.classList.remove('show');
            document.getElementById("popupForm").classList.remove('show');
        });

        // Form validation
    document.getElementById('announcementForm').addEventListener('submit', function(e) {
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
        
        if (title.length < 3) {
            e.preventDefault();
            showError('Title must be at least 3 characters long');
                titleInput.focus();
            return;
        }
        
        if (content.length < 10) {
            e.preventDefault();
            showError('Content must be at least 10 characters long');
                contentInput.focus();
            return;
        }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
            showLoading();
        });
    });

    // Close popups when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('popup-wrapper')) {
            event.target.parentElement.classList.remove('show');
        }
    }

    // Add keyboard support
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.popup, .view-popup').forEach(popup => {
                popup.style.display = "none";
            });
        }
    });

    function showAllMedia(mediaFiles) {
        let currentIndex = 0;
        
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        
        function updateImage() {
            modal.innerHTML = `
                <div class="modal-content">
                    <img src="../../multimedia/${mediaFiles[currentIndex]}" alt="Full size image">
                    <button class="close-button" onclick="this.closest('.image-modal').remove()">&times;</button>
                </div>
                <div class="gallery-nav">
                    <button onclick="updateGallery(-1)" ${currentIndex === 0 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <span style="color: white">${currentIndex + 1} / ${mediaFiles.length}</span>
                    <button onclick="updateGallery(1)" ${currentIndex === mediaFiles.length - 1 ? 'disabled' : ''}>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            `;
        }
        
        window.updateGallery = function(direction) {
            currentIndex = Math.max(0, Math.min(mediaFiles.length - 1, currentIndex + direction));
            updateImage();
        };
        
        document.body.appendChild(modal);
        updateImage();
        setTimeout(() => modal.classList.add('show'), 10);
    }

    function openImageModal(src) {
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <img src="${src}" alt="Full size image">
                <button class="close-button" onclick="this.closest('.image-modal').remove()">&times;</button>
            </div>
        `;
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Wait a bit for the script to load, then check if Sortable is available
        setTimeout(function() {
            // Check if Sortable is loaded
            if (typeof Sortable === 'undefined') {
                console.error('Sortable library not loaded! Attempting fallback...');
                
                // Try to load from a different CDN as fallback
                const fallbackScript = document.createElement('script');
                fallbackScript.src = 'https://unpkg.com/sortablejs@1.15.0/Sortable.min.js';
                fallbackScript.crossOrigin = 'anonymous';
                fallbackScript.onload = function() {
                    console.log('Sortable library loaded from fallback CDN');
                    initializeSortable();
                };
                fallbackScript.onerror = function() {
                    console.error('Primary CDN failed, trying second fallback...');
                    // Try a third fallback
                    const secondFallbackScript = document.createElement('script');
                    secondFallbackScript.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                    secondFallbackScript.crossOrigin = 'anonymous';
                    secondFallbackScript.onload = function() {
                        console.log('Sortable library loaded from second fallback CDN');
                        initializeSortable();
                    };
                    secondFallbackScript.onerror = function() {
                        console.error('All CDNs failed to load Sortable');
                        alert('Unable to load drag-and-drop functionality. Please check your internet connection and refresh the page.');
                    };
                    document.head.appendChild(secondFallbackScript);
                };
                document.head.appendChild(fallbackScript);
                return;
            }
            
            console.log('Sortable library loaded successfully');
            initializeSortable();
        }, 100);
        
        function initializeSortable() {
            // Initialize Sortable for each media container
            document.querySelectorAll('.media-container').forEach(container => {
                const announcementId = container.dataset.announcementId;
                
                console.log('Initializing Sortable for announcement:', announcementId);
                
                new Sortable(container, {
                    animation: 150,
                    ghostClass: 'dragging',
                    onStart: function(evt) {
                        console.log('Drag started on:', evt.item);
                    },
                    onEnd: async function(evt) {
                        console.log('Drag ended:', {
                            oldIndex: evt.oldIndex,
                            newIndex: evt.newIndex,
                            item: evt.item
                        });
                        
                        const mediaOrder = Array.from(container.children).map(item => 
                            parseInt(item.dataset.mediaId)
                        );
                        
                        console.log('New media order:', mediaOrder);
                        
                        try {
                            const response = await fetch('reorder_media.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    announcement_id: announcementId,
                                    media_order: mediaOrder
                                })
                            });
                            
                            const result = await response.json();
                            if (!response.ok) {
                                throw new Error(result.error || 'Failed to update media order');
                            }
                            
                            console.log('Media order updated successfully');
                            
                            // Update order numbers
                            container.querySelectorAll('.media-order-handle').forEach((handle, index) => {
                                handle.textContent = `${index + 1}/${container.children.length}`;
                            });
                            
                        } catch (error) {
                            console.error('Error updating media order:', error);
                            alert('Error updating media order: ' + error.message);
                            location.reload();
                        }
                    }
                });
            });
        }
    });

    // Real-time sync for announcements
    if (window.RealtimeSync) {
        window.RealtimeSync.on('announcements', function(change) {
            console.log('[Announcements] Change detected:', change);
            // Reload the page to show updated announcements
            // Use a smooth reload to avoid jarring user experience
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            location.reload();
            // Restore scroll position after reload
            window.addEventListener('load', function() {
                window.scrollTo(0, currentScroll);
            }, { once: true });
        });
    }
</script>

</body>
</html>

