<?php
include_once __DIR__ . '/include/auth_required.php';
include __DIR__ . '/../database/connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Homepage</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="SmartBulletin">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SmartBulletin">
    <meta name="description" content="Faculty of Computer and Management Sciences Bulletin Board System">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#7B0000">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="theme-color" content="#7B0000">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="../manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="../images/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../images/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/logo.png">
    <link rel="apple-touch-icon" sizes="167x167" href="../images/logo.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../images/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/logo.png">
    <link rel="shortcut icon" href="../images/logo.png">
    
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
            overflow-x: hidden;
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
            justify-content: space-between;
            align-items: center;
            width: 90%;
            max-width: 1200px;
            border-radius: 10px;
            position: relative;
            z-index: 1;
        }

        .install-app-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.6em;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .install-app-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .install-app-btn:active {
            transform: translateY(0);
        }

        .install-app-btn i {
            font-size: 1.1em;
        }

        .popup-footer button[type="submit"] {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid rgba(123, 0, 0, 0.2);
        }

        .popup-footer button[type="submit"]:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .popup-footer button[type="submit"]:disabled {
            background: rgba(123, 0, 0, 0.05);
            color: rgba(123, 0, 0, 0.5);
            cursor: not-allowed;
            transform: none;
            border-color: rgba(123, 0, 0, 0.1);
        }

        .popup-footer button[type="submit"]:disabled:hover {
            background: rgba(123, 0, 0, 0.05);
            transform: none;
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
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            font-size: 20px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: scale(1.1);
        }

        form input[type="text"]:focus,
        form textarea:focus {
            outline: none;
            border-color: rgba(123, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }

        .file-input-wrapper label:hover {
            border-color: #7B0000;
            background: rgba(123, 0, 0, 0.05);
        }

        .file-input-wrapper .remove-file {
            color: #7B0000;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .file-input-wrapper .remove-file:hover {
            background: rgba(123, 0, 0, 0.1);
        }

        .help-text i {
            color: #7B0000;
            font-size: 14px;
        }

        .character-count.warning {
            color: #7B0000;
        }

        .character-count.danger {
            color: #7B0000;
        }

        .error-message {
            color: #7B0000;
            margin-top: 5px;
            font-size: 14px;
        }

        .success-message {
            color: #7B0000;
            margin-top: 5px;
            font-size: 14px;
        }

        .loading i {
            color: #7B0000;
            font-size: 24px;
            animation: spin 1s linear infinite;
        }

        .header-title {
            font-size: 1.8em;
            font-weight: 600;
            color: #7B0000;
            text-shadow: none;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .header-actions a {
            color: #7B0000;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            transition: all 0.3s ease;
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
        }

        .header-actions a:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .section-container {
            width: 90%;
            max-width: 1000px;
            background: #ffffff;
            margin: 20px auto;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .section-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 48px rgba(123, 0, 0, 0.1);
        }

        .section-container h2 {
            color: #7B0000;
            font-size: 1.6em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: none;
        }

        .section-container h2::before {
            content: '';
            display: block;
            width: 3px;
            height: 20px;
            background: #7B0000;
            border-radius: 2px;
        }

        .entry {
            padding: 15px;
            margin-bottom: 15px;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .entry:hover {
            background: #ffffff;
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(123, 0, 0, 0.1);
        }

        .entry:last-child {
            margin-bottom: 0;
        }

        .entry h3 {
            font-size: 1.2em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 8px;
        }

        .entry p {
            font-size: 1em;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .entry small {
            color: #666;
            font-size: 0.85em;
            display: block;
            margin-top: 10px;
            font-style: italic;
        }

        .view-all {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #7B0000;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(123, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-top: 15px;
            font-size: 0.9em;
            border: 1px solid rgba(123, 0, 0, 0.2);
        }

        .view-all:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateX(3px);
        }

        .profile-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            margin-right: 15px;
            float: left;
            border: 2px solid rgba(123, 0, 0, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.03);
            border-color: rgba(123, 0, 0, 0.5);
        }

        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin: 12px 0;
            padding: 12px;
            background: rgba(123, 0, 0, 0.05);
            border-radius: 8px;
        }

        .event-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            color: #333;
        }

        .event-detail i {
            color: #7B0000;
            font-size: 1.1em;
        }

        .announcement-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 10px 0;
            border: 1px solid #e0e0e0;
        }

        .media-container {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            overflow: hidden;
            padding: 5px 0;
            width: 100%;
            justify-content: flex-start;
        }

        .media-item {
            position: relative;
            width: calc((100% - 20px) / 3);
            height: 150px;
            flex: 0 0 auto;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        .media-item.has-more::after {
            content: attr(data-remaining);
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(123, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .media-item.has-more:hover::after {
            background: rgba(123, 0, 0, 0.9);
        }

        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .more-media {
            display: none;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .header {
                padding: 12px 15px;
                font-size: 1.4em;
                width: 95%;
                margin: 15px auto;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .install-app-btn {
                font-size: 0.7em;
                padding: 8px 16px;
                align-self: center;
            }

            .section-container {
                width: 95%;
                padding: 15px;
                margin: 15px auto;
                border-radius: 12px;
            }

            .section-container h2 {
                font-size: 1.3em;
                margin-bottom: 15px;
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }

            .section-container h2::before {
                display: none;
            }

            .entry {
                padding: 12px;
                margin-bottom: 12px;
                border-radius: 8px;
            }

            .entry h3 {
                font-size: 1.1em;
                margin-bottom: 6px;
            }

            .entry p {
                font-size: 0.95em;
                line-height: 1.4;
            }

            .entry small {
                font-size: 0.8em;
                margin-top: 8px;
            }

            .profile-image {
                width: 70px;
                height: 70px;
                margin-right: 10px;
                border-radius: 8px;
            }

            .event-details {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 10px;
            }

            .event-detail {
                font-size: 0.85em;
            }

            .media-container {
                gap: 8px;
                margin: 8px 0;
            }

            .media-item {
                width: calc((100% - 16px) / 3);
                height: 120px;
            }

            .view-all {
                padding: 10px 20px;
                font-size: 0.85em;
                margin-top: 12px;
                width: 100%;
                justify-content: center;
            }
        }

        /* Enhanced Mobile Responsive Styles */
        @media (max-width: 480px) {
            .header {
                font-size: 1.2em;
                padding: 10px 12px;
                width: 98%;
                margin: 10px auto;
            }

            .section-container {
                width: 98%;
                padding: 12px;
                margin: 10px auto;
            }

            .section-container h2 {
                font-size: 1.2em;
            }

            .entry {
                padding: 10px;
                margin-bottom: 10px;
            }

            .entry h3 {
                font-size: 1em;
            }

            .entry p {
                font-size: 0.9em;
            }

            .profile-image {
                width: 60px;
                height: 60px;
                margin-right: 8px;
            }

            .media-item {
                height: 100px;
            }

            .event-details {
                padding: 8px;
            }

            .event-detail {
                font-size: 0.8em;
            }
        }

        /* Extra Small Mobile Devices */
        @media (max-width: 360px) {
            .header {
                font-size: 1.1em;
                padding: 8px 10px;
            }

            .section-container {
                padding: 10px;
                margin: 8px auto;
            }

            .entry {
                padding: 8px;
                margin-bottom: 8px;
            }

            .entry h3 {
                font-size: 0.95em;
            }

            .entry p {
                font-size: 0.85em;
            }

            .profile-image {
                width: 50px;
                height: 50px;
                margin-right: 6px;
            }

            .media-item {
                height: 80px;
            }

            .view-all {
                padding: 8px 16px;
                font-size: 0.8em;
            }
        }

        /* Landscape Mobile Orientation */
        @media (max-width: 768px) and (orientation: landscape) {
            .header {
                flex-direction: row;
                text-align: left;
                padding: 8px 15px;
            }

            .install-app-btn {
                align-self: center;
                font-size: 0.6em;
                padding: 6px 12px;
            }

            .section-container {
                padding: 12px;
                margin: 8px auto;
            }

            .entry {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .header {
                font-size: 1.2em;
                padding: 10px 12px;
                width: 98%;
                margin: 10px auto;
            }

            .section-container {
                width: 98%;
                padding: 12px;
                margin: 10px auto;
            }

            .section-container h2 {
                font-size: 1.2em;
            }

            .entry {
                padding: 10px;
                margin-bottom: 10px;
            }

            .entry h3 {
                font-size: 1em;
            }

            .entry p {
                font-size: 0.9em;
            }

            .profile-image {
                width: 60px;
                height: 60px;
                margin-right: 8px;
            }

            .media-item {
                height: 100px;
            }

            .event-details {
                padding: 8px;
            }

            .event-detail {
                font-size: 0.8em;
            }
        }

        @media (min-width: 1400px) {
            .section-container {
                max-width: 1200px;
            }
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #7B0000;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
        }

        .notification i {
            font-size: 20px;
        }

        .notification .close-notification {
            margin-left: 15px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .notification .close-notification:hover {
            opacity: 1;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .notification.hide {
            animation: slideOut 0.5s ease-in forwards;
        }

        /* Mobile notification adjustments */
        @media (max-width: 768px) {
            .notification {
                top: 10px;
                right: 10px;
                left: 10px;
                padding: 12px 20px;
                font-size: 0.9em;
            }
        }

        /* Greeting Popup Styles */
        .greeting-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease-out;
        }

        .greeting-modal {
            background: white;
            border-radius: 15px;
            padding: 40px 50px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease-out;
            position: relative;
        }

        .greeting-icon {
            font-size: 60px;
            color: #7B0000;
            margin-bottom: 20px;
            animation: bounce 0.6s ease-out;
        }

        .greeting-text {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .greeting-name {
            color: #7B0000;
            font-weight: 700;
        }

        .greeting-message {
            font-size: 18px;
            color: #666;
            margin-top: 15px;
            font-style: italic;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* Mobile adjustments for greeting */
        @media (max-width: 768px) {
            .greeting-modal {
                padding: 30px 25px;
                max-width: 90%;
            }

            .greeting-icon {
                font-size: 50px;
                margin-bottom: 15px;
            }

            .greeting-text {
                font-size: 22px;
            }

            .greeting-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<?php include 'include/header.php'; ?>

<?php 
// Check if we should show greeting popup after login
$showGreeting = false;
$userFirstName = '';
if (isset($_SESSION['show_greeting']) && $_SESSION['show_greeting']) {
    $showGreeting = true;
    $userFirstName = $_SESSION['user_first_name'] ?? '';
    // Clear the flag so it doesn't show again on refresh
    unset($_SESSION['show_greeting']);
    unset($_SESSION['user_first_name']);
}
?>

<?php if (isset($_SESSION['new_login_notification'])): ?>
<div class="notification" id="loginNotification">
    <i class="fas fa-exclamation-triangle"></i>
    <span>Your account has been logged in from another device. This session will be terminated.</span>
    <span class="close-notification" onclick="closeNotification()">&times;</span>
</div>

<script>
function closeNotification() {
    const notification = document.getElementById('loginNotification');
    notification.classList.add('hide');
    setTimeout(() => {
        notification.remove();
    }, 500);
}

// Automatically close notification after 10 seconds
setTimeout(closeNotification, 10000);
</script>
<?php 
unset($_SESSION['new_login_notification']);
endif; 
?>

<?php if ($showGreeting && !empty($userFirstName)): ?>
<div class="greeting-overlay" id="greetingPopup">
    <div class="greeting-modal">
        <div class="greeting-icon">
            <i class="fas fa-hand-sparkles"></i>
        </div>
        <div class="greeting-text">
            <span id="greetingTime"></span>, <span class="greeting-name"><?php echo htmlspecialchars($userFirstName); ?></span>!
        </div>
        <div class="greeting-message" id="greetingMessage">Have a great day!</div>
    </div>
</div>

<script>
(function() {
    const greetingPopup = document.getElementById('greetingPopup');
    if (!greetingPopup) return;

    // Determine time of day and set greeting
    const hour = new Date().getHours();
    let greeting = '';
    let message = '';

    if (hour >= 5 && hour < 12) {
        greeting = 'Good morning';
        message = 'Have a wonderful day!';
    } else if (hour >= 12 && hour < 17) {
        greeting = 'Good afternoon';
        message = 'Hope you\'re having a great day!';
    } else if (hour >= 17 && hour < 21) {
        greeting = 'Good evening';
        message = 'Hope you had a great day!';
    } else {
        greeting = 'Good night';
        message = 'Have a restful evening!';
    }

    // Update greeting text
    document.getElementById('greetingTime').textContent = greeting;
    document.getElementById('greetingMessage').textContent = message;

    // Auto-close after 3 seconds
    setTimeout(() => {
        greetingPopup.style.animation = 'fadeIn 0.3s ease-out reverse';
        setTimeout(() => {
            greetingPopup.remove();
        }, 300);
    }, 3000);
})();
</script>
<?php endif; ?>

<div class="header">
    <span>Welcome to Our Homepage</span>
    <button id="installAppBtn" class="install-app-btn" style="display: none;">
        <i class="fas fa-download"></i> Install App
    </button>
</div>

<div class="section-container" id="calendar">
    <h2><i class="fas fa-calendar-alt"></i> Upcoming Events</h2>
    <?php
    $query = "SELECT e.*, m.file_path, u.username as created_by_name 
              FROM events e 
              LEFT JOIN multimedia_content m ON e.media_id = m.id 
              LEFT JOIN users u ON e.created_by = u.id 
              WHERE CONCAT(e.date, ' ', e.time) >= NOW()
                AND (e.is_archived = 0 OR e.is_archived IS NULL)
              ORDER BY e.date ASC, e.time ASC 
              LIMIT 3";
    $calendar = mysqli_query($conn, $query);
    
    if ($calendar && mysqli_num_rows($calendar) > 0) {
        while ($row = mysqli_fetch_assoc($calendar)) {
            $end_date = date('Y-m-d', strtotime($row['date'] . ' + ' . ($row['duration'] - 1) . ' days'));
            $date_display = $row['date'] == $end_date ? 
                date("F j, Y", strtotime($row['date'])) : 
                date("F j", strtotime($row['date'])) . ' - ' . date("F j, Y", strtotime($end_date));
            
            echo "<div class='entry'>";
            if(!empty($row['file_path'])) {
                $ext = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                $mediaSrc = '../multimedia/events/' . basename($row['file_path']);
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    echo "<div class='media-container'>";
                    echo "<div class='media-item'>";
                    echo "<img src='" . htmlspecialchars($mediaSrc) . "' alt='" . htmlspecialchars($row['title']) . "'>";
                    echo "</div>";
                    echo "</div>";
                }
            }
            echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
            echo "<p>" . htmlspecialchars($row['description']) . "</p>";
            echo "<div class='event-details'>";
            echo "<div class='event-detail'><i class='fas fa-calendar'></i> " . $date_display . "</div>";
            echo "<div class='event-detail'><i class='fas fa-clock'></i> " . date("h:i A", strtotime($row['time'])) . "</div>";
            echo "<div class='event-detail'><i class='fas fa-map-marker-alt'></i> " . htmlspecialchars($row['location']) . "</div>";
            echo "<div class='event-detail'><i class='fas fa-hourglass-half'></i> " . $row['duration'] . " day" . ($row['duration'] > 1 ? 's' : '') . "</div>";
            echo "</div>";
            echo "<small><i class='fas fa-user'></i> Posted by: " . htmlspecialchars($row['created_by_name']) . "</small>";
            echo "</div>";
        }
    } else {
        echo "<div class='entry'><p>There are no upcoming events yet.</p></div>";
    }
    ?>
    <?php if ($user_role === ROLE_ADMIN): ?>
    <div style="text-align: center;">
        <a href="events/upcoming-events.php" class="view-all">
            View All Events <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="section-container" id="announcement">
    <?php if (in_array($user_role, [ROLE_ADMIN, ROLE_SECRETARY, ROLE_OFFICER])): ?>
    <h2><i class="fas fa-bullhorn"></i> Announcements</h2>
    <?php
    $query = "SELECT a.*, u.username as created_by_name, 
              GROUP_CONCAT(DISTINCT m.file_path ORDER BY am.display_order) as media_files
              FROM announcements a 
              LEFT JOIN users u ON a.created_by = u.id 
              LEFT JOIN announcement_media am ON a.id = am.announcement_id
              LEFT JOIN multimedia_content m ON am.media_id = m.id 
              WHERE a.is_archived = FALSE
              GROUP BY a.id
              ORDER BY a.announcement_date ASC, a.announcement_time ASC 
              LIMIT 3";
    $announcements = mysqli_query($conn, $query);
    
    if ($announcements && mysqli_num_rows($announcements) > 0) {
        while ($row = mysqli_fetch_assoc($announcements)) {
            echo "<div class='entry'>";
            echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
            
            if (!empty($row['media_files'])) {
                $media_files = explode(',', $row['media_files']);
                echo "<div class='media-container'>";
                $media_count = count($media_files);
                $display_count = min(3, $media_count);
                
                for ($i = 0; $i < $display_count; $i++) {
                    $file_path = trim($media_files[$i]);
                    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $extra_class = ($i === 2 && $media_count > 3) ? ' has-more' : '';
                        $remaining = ($media_count > 3) ? "data-remaining='+" . ($media_count - 3) . "'" : '';
                        $file_name = basename($file_path);
                        echo "<div class='media-item{$extra_class}' {$remaining}>";
                        echo "<img src='../multimedia/announcements/" . htmlspecialchars($file_name) . "' 
                                  alt='" . htmlspecialchars($row['title']) . "'>";
                        echo "</div>";
                    }
                }
                echo "</div>";
            }
            
            echo "<p>" . nl2br(htmlspecialchars($row['content'])) . "</p>";
            echo "<small><i class='fas fa-user'></i> Posted by: " . htmlspecialchars($row['created_by_name']);
            
            if (!empty($row['announcement_date'])) {
                echo " <i class='fas fa-calendar'></i> " . date("F j, Y", strtotime($row['announcement_date']));
                if (!empty($row['announcement_time'])) {
                    echo " <i class='fas fa-clock'></i> " . date("g:i A", strtotime($row['announcement_time']));
                }
            }
            
            echo "</small>";
            echo "</div>";
        }
    } else {
        echo "<div class='entry'><p>No announcements yet.</p></div>";
    }
    ?>
    <div style="text-align: center;">
        <a href="announcement/announcement.php" class="view-all">
            View All Announcements <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="section-container" id="faculty">
    <h2><i class="fas fa-chalkboard-teacher"></i> Faculty</h2>
    <?php
    $query = "SELECT f.*, m.file_path 
              FROM faculty f 
              LEFT JOIN multimedia_content m ON f.media_id = m.id 
              WHERE f.type = 'faculty' 
              ORDER BY 
              CASE 
                  WHEN f.position LIKE '%College Dean%' THEN 1
                  WHEN f.position LIKE '%Program Head%' THEN 2
                  WHEN f.position LIKE '%Coordinator%' THEN 3
                  WHEN f.position LIKE '%SHS Faculty%' AND 
                       (f.position LIKE '%Coordinator%' OR 
                        f.position LIKE '%Adviser%' OR 
                        f.position LIKE '%Head%') THEN 4
                  WHEN f.position LIKE '%SHS Faculty%' THEN 5
                  WHEN f.position LIKE '%CCS Faculty%' AND 
                       (f.position LIKE '%Coordinator%' OR 
                        f.position LIKE '%Adviser%' OR 
                        f.position LIKE '%Head%') THEN 6
                  WHEN f.position LIKE '%CCS Faculty%' THEN 7
                  ELSE 8
              END,
              f.lname ASC, f.fname ASC, f.mname ASC";
    $faculty = mysqli_query($conn, $query);
    
    if ($faculty) {
        while ($row = mysqli_fetch_assoc($faculty)) {
            // Generate display name from components
            $display_name = trim(($row['prefix'] ? $row['prefix'] . ' ' : '') . 
                               $row['fname'] . 
                               ($row['mname'] ? ' ' . $row['mname'] : '') . 
                               ' ' . $row['lname'] . 
                               ($row['suffix'] ? ', ' . $row['suffix'] : ''));
            
            echo "<div class='entry'>";
            if($row['file_path']) {
                echo "<img src='../multimedia/faculty/" . basename($row['file_path']) . "' 
                          alt='" . htmlspecialchars($display_name) . "' 
                          class='profile-image'>";
            }
            echo "<h3>" . htmlspecialchars($display_name) . "</h3>";
            
            // Display position if available
            if (!empty($row['position'])) {
                echo "<p><strong><i class='fas fa-briefcase'></i> Position: " . htmlspecialchars($row['position']) . "</strong></p>";
            }
            
            echo "<p>" . htmlspecialchars($row['description']) . "</p>";
            echo "<div style='clear: both;'></div>";
            echo "</div>";
        }
    } else {
        echo "<div class='entry'><p>No faculty members found.</p></div>";
    }
    ?>
</div>

<div class="section-container" id="officers">
    <h2><i class="fas fa-users"></i> FC&MS Officers</h2>
    <?php
    $query = "SELECT o.*, m.file_path, p.name as partylist_name 
              FROM officers o 
              LEFT JOIN multimedia_content m ON o.multimedia_id = m.id 
              LEFT JOIN partylist p ON o.partylist_id = p.id 
              WHERE p.is_selected = 1 
              ORDER BY FIELD(o.position,
                'President',
                'Internal Vice President',
                'External Vice President',
                'Secretary',
                'Assistant Secretary',
                'Treasurer',
                'Asst. Treasurer',
                'Auditor',
                'PRO Internal',
                'PRO External',
                '1st Year Representative',
                '2nd Year Representative',
                '3rd Year Representative',
                '4th Year Representative',
                'Marshall Head',
                'Senior Multimedia',
                'Multimedia Team',
                'Senior Esports',
                'Esports Team'
              ), o.id ASC";
    $officers = mysqli_query($conn, $query);
    
    if ($officers) {
        while ($row = mysqli_fetch_assoc($officers)) {
            echo "<div class='entry'>";
            if($row['file_path']) {
                echo "<img src='../multimedia/officers/" . basename($row['file_path']) . "' 
                          alt='" . htmlspecialchars($row['name']) . "' 
                          class='profile-image'>";
            }
            echo "<h3>" . htmlspecialchars($row['name']) . "</h3>";
            echo "<p><strong>" . htmlspecialchars($row['position']) . "</strong></p>";
            if ($row['partylist_name']) {
                echo "<p><em>Partylist: " . htmlspecialchars($row['partylist_name']) . "</em></p>";
            }
            echo "<div style='clear: both;'></div>";
            echo "</div>";
        }
    } else {
        echo "<div class='entry'><p>No officers found.</p></div>";
    }
    ?>
</div>

<script>
// PWA Install Button Functionality
let deferredPrompt;
const installBtn = document.getElementById('installAppBtn');

// Check if the app is already installed
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    // App is already installed, hide the button
    installBtn.style.display = 'none';
} else {
    // Show install button if PWA can be installed
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('PWA install prompt triggered');
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        // Show the install button
        installBtn.style.display = 'flex';
    });
}

// Handle install button click
installBtn.addEventListener('click', async () => {
    if (deferredPrompt) {
        // Show the install prompt
        deferredPrompt.prompt();
        // Wait for the user to respond to the prompt
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        // Clear the deferredPrompt so it can only be used once
        deferredPrompt = null;
        // Hide the install button
        installBtn.style.display = 'none';
    }
});

// Handle app installed event
window.addEventListener('appinstalled', (evt) => {
    console.log('PWA was installed');
    // Hide the install button
    installBtn.style.display = 'none';
    // Show success message
    showNotification('App installed successfully!', 'success');
});

// Notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <span class="close-notification" onclick="this.parentElement.remove()">&times;</span>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Service Worker Registration (handled by pwa-register.js)
// Service worker is now registered via the centralized script in header.php
</script>

</body>
</html>

