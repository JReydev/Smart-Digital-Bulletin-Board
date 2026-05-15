<?php
// Role constants
define('ROLE_ADMIN', 1);
define('ROLE_SECRETARY', 2);
define('ROLE_OFFICER', 3);
define('ROLE_FACULTY', 4);

// Get current page path and name
$current_path = $_SERVER['PHP_SELF'];
$current_page = basename($current_path);
$current_page = str_replace('.php', '', $current_page);

// Get the directory path to determine the module
$directory_path = dirname($current_path);
$module_name = basename($directory_path);

// Function to check if current page belongs to a module
function isInModule($current_path, $module_name) {
    return stripos($current_path, "/modules/$module_name/") !== false;
}

// Determine the active page based on both current page and module
if (isInModule($current_path, 'Announcement')) {
    $active_page = 'Announcement';
} elseif (isInModule($current_path, 'Events')) {
    $active_page = 'Upcoming Events';
} elseif (isInModule($current_path, 'Faculty')) {
    $active_page = 'Faculty';
} elseif (isInModule($current_path, 'Officers')) {
    $active_page = 'Officers';
} elseif (isInModule($current_path, 'Calendar')) {
    $active_page = 'Calendar';
} elseif (isInModule($current_path, 'admin') && $current_page === 'manage_accounts') {
    $active_page = 'Manage Accounts';
} elseif (isInModule($current_path, 'admin') && $current_page === 'manage_marquee') {
    $active_page = 'Manage Marquee';
} elseif (isInModule($current_path, 'admin') && $current_page === 'configure') {
    $active_page = 'Configure';
} elseif (isInModule($current_path, 'Account')) {
    $active_page = 'Account';
} elseif ($current_page === 'homepage') {
    $active_page = 'Homepage';
} else {
    $active_page = ucwords(str_replace(['_', '-'], ' ', $current_page));
}

// Get user role
$user_role = null;
if (isset($_SESSION['user_id'])) {
    global $conn;
    $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $user_role = $user['role_id'];
    }
}

// Check if user is admin
$is_admin = ($user_role === ROLE_ADMIN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/SmartBulletin/manifest.json">
    
    <!-- iOS PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SmartBulletin">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="format-detection" content="telephone=no">
    
    <!-- Theme Color for iOS -->
    <meta name="theme-color" content="#7B0000">
    <meta name="msapplication-TileColor" content="#7B0000">
    <meta name="msapplication-tap-highlight" content="no">
    
    <!-- Apple Touch Icons (iOS Home Screen Icons) -->
    <link rel="apple-touch-icon" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="57x57" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/SmartBulletin/images/logo.png">
    
    <!-- iOS Splash Screens -->
    <link rel="apple-touch-startup-image" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" href="/SmartBulletin/images/ios-splash-iphone-se.png">
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)" href="/SmartBulletin/images/ios-splash-iphone-8.png">
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)" href="/SmartBulletin/images/ios-splash-iphone-8-plus.png">
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)" href="/SmartBulletin/images/ios-splash-iphone-x.png">
    <link rel="apple-touch-startup-image" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)" href="/SmartBulletin/images/ios-splash-iphone-12.png">
    <link rel="apple-touch-startup-image" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3)" href="/SmartBulletin/images/ios-splash-iphone-12-pro-max.png">
    <link rel="apple-touch-startup-image" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)" href="/SmartBulletin/images/ios-splash-ipad.png">
    <link rel="apple-touch-startup-image" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)" href="/SmartBulletin/images/ios-splash-ipad-pro.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/SmartBulletin/images/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/SmartBulletin/images/logo.png">
    <link rel="shortcut icon" href="/SmartBulletin/images/logo.png">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    html, body {
        height: 100%;
        width: 100%;
        overflow-x: hidden;
    }

    body { 
        background: #ffffff;
        color: #333;
        min-height: 100vh;
        line-height: 1.6;
        padding-top: 80px; /* Space for fixed nav */
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
    }

    /* Main Navigation Bar */
    nav {
        background-color: #006633;
        padding: 10px 20px;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 1001;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .logo img {
        height: 60px;
        width: 60px;
        border-radius: 50%;
        object-fit: cover;
    }

    nav ul {
        list-style: none;
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 0;
        padding: 0;
    }

    nav ul li {
        position: relative;
    }

    nav ul li a {
        text-decoration: none;
        color: white;
        font-size: 16px;
        text-transform: uppercase;
        padding: 8px 12px;
        border-radius: 4px;
        transition: background-color 0.3s ease;
        white-space: nowrap;
    }

    nav ul li a:hover {
        background-color: #005a2e;
    }

    nav ul li a.active {
        background-color: #008844;
        color: white;
        box-shadow: 0 2px 10px rgba(0, 102, 51, 0.3);
    }

    /* Hamburger Menu */
    .hamburger {
        display: none;
        flex-direction: column;
        cursor: pointer;
        padding: 5px;
        background: none;
        border: none;
        outline: none;
    }

    .hamburger span {
        width: 25px;
        height: 3px;
        background: white;
        margin: 3px 0;
        transition: 0.3s;
        border-radius: 2px;
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(-45deg) translate(-5px, 6px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(45deg) translate(-5px, -6px);
    }

    /* Page Header */
    .header {
        background: rgba(123, 0, 0, 0.95);
        color: white;
        padding: 15px 20px;
        font-size: 2em;
        font-weight: 600;
        letter-spacing: -0.5px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        margin: 20px auto;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 90%;
        max-width: 1200px;
        border-radius: 10px;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        body {
            padding-top: 70px;
        }

        nav {
            padding: 8px 15px;
        }

        .logo img {
            height: 50px;
            width: 50px;
        }

        .hamburger {
            display: flex;
        }

        nav ul {
            position: fixed;
            top: 70px;
            left: -100%;
            width: 100%;
            height: calc(100vh - 70px);
            background-color: #000;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            gap: 0;
            transition: left 0.3s ease;
            padding: 20px 0;
            overflow-y: auto;
        }

        nav ul.active {
            left: 0;
        }

        nav ul li {
            width: 100%;
            text-align: center;
        }

        nav ul li a {
            display: block;
            padding: 15px 20px;
            font-size: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
        }

        nav ul li a:hover {
            background-color: #6d0000;
        }

        .header {
            font-size: 1.4em;
            padding: 12px 15px;
            margin: 15px auto;
            width: 95%;
        }
    }


    /* Landscape Mobile Orientation */
    @media (max-width: 768px) and (orientation: landscape) {
        nav ul {
            height: calc(100vh - 60px);
            padding: 10px 0;
        }

        nav ul li a {
            padding: 10px 20px;
            font-size: 14px;
        }

        .header {
            padding: 8px 15px;
            margin: 8px auto;
        }
    }

    @media (max-width: 480px) {
        body {
            padding-top: 60px;
        }

        nav {
            padding: 6px 12px;
        }

        .logo img {
            height: 45px;
            width: 45px;
        }

        nav ul {
            top: 60px;
            height: calc(100vh - 60px);
        }

        nav ul li a {
            padding: 12px 15px;
            font-size: 15px;
        }

        .header {
            font-size: 1.2em;
            padding: 10px 12px;
            margin: 10px auto;
            width: 98%;
        }

        .hamburger span {
            width: 22px;
            height: 2px;
        }
    }

    @media (max-width: 360px) {
        nav {
            padding: 5px 10px;
        }

        .logo img {
            height: 40px;
            width: 40px;
        }

        nav ul li a {
            padding: 10px 12px;
            font-size: 14px;
        }

        .header {
            font-size: 1.1em;
            padding: 8px 10px;
        }
    }
    </style>
</head>
<body>
<!-- Navigation Bar -->
<nav>
    <!-- Logo Section -->
    <div class="logo">
        <img src="/SmartBulletin/images/logo.png" alt="Logo">
    </div>

    <!-- Hamburger Menu -->
    <button class="hamburger" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Navigation Links -->
    <ul id="nav-menu">
        <li><a href="/SmartBulletin/modules/homepage.php" <?php echo $active_page === 'Homepage' ? 'class="active"' : ''; ?>>Home</a></li>
        
        <!-- Calendar - Accessible by all roles -->
        <li><a href="/SmartBulletin/modules/calendar/calendar.php" <?php echo $active_page === 'Calendar' ? 'class="active"' : ''; ?>>Calendar</a></li>

        <?php if ($user_role === ROLE_ADMIN): ?>
            <!-- Admin Navigation -->
            <li><a href="/SmartBulletin/modules/announcement/announcement.php" <?php echo $active_page === 'Announcement' ? 'class="active"' : ''; ?>>Announcement</a></li>
            <li><a href="/SmartBulletin/modules/events/upcoming-events.php" <?php echo $active_page === 'Upcoming Events' ? 'class="active"' : ''; ?>>Upcoming Events</a></li>
            <li><a href="/SmartBulletin/modules/faculty/faculty.php" <?php echo $active_page === 'Faculty' ? 'class="active"' : ''; ?>>Faculty</a></li>
            <li><a href="/SmartBulletin/modules/officers/officers.php" <?php echo $active_page === 'Officers' ? 'class="active"' : ''; ?>>FC&amp;MS Officers</a></li>
            <li><a href="/SmartBulletin/modules/admin/manage_accounts.php" <?php echo $active_page === 'Manage Accounts' ? 'class="active"' : ''; ?>>Manage Accounts</a></li>
            <li><a href="/SmartBulletin/modules/admin/manage_marquee.php" <?php echo $active_page === 'Manage Marquee' ? 'class="active"' : ''; ?>>Manage Marquee</a></li>
            <li><a href="/SmartBulletin/modules/admin/configure.php" <?php echo $active_page === 'Configure' ? 'class="active"' : ''; ?>>Configure</a></li>
        <?php elseif ($user_role === ROLE_SECRETARY): ?>
            <!-- Faculty Navigation -->
            <li><a href="/SmartBulletin/modules/announcement/announcement.php" <?php echo $active_page === 'Announcement' ? 'class="active"' : ''; ?>>Announcement</a></li>
        <?php elseif ($user_role === ROLE_OFFICER): ?>
            <!-- Officer Navigation -->
            <li><a href="/SmartBulletin/modules/announcement/announcement.php" <?php echo $active_page === 'Announcement' ? 'class="active"' : ''; ?>>Announcement</a></li>
            <li><a href="/SmartBulletin/modules/events/upcoming-events.php" <?php echo $active_page === 'Upcoming Events' ? 'class="active"' : ''; ?>>Upcoming Events</a></li>
        <?php elseif ($user_role === ROLE_FACULTY): ?>
            <!-- Faculty Navigation -->
            <li><a href="/SmartBulletin/modules/faculty/faculty_profile.php" <?php echo $active_page === 'Faculty Profile' ? 'class="active"' : ''; ?>>My Profile</a></li>
            <li><a href="/SmartBulletin/modules/faculty/faculty_marquee_announcements.php" <?php echo $active_page === 'Marquee Announcements' ? 'class="active"' : ''; ?>>Marquee Announcements</a></li>
        <?php endif; ?>
        
        <li><a href="/SmartBulletin/modules/account/account.php" <?php echo $active_page === 'Account' ? 'class="active"' : ''; ?>>Account</a></li>
        <li><a href="/SmartBulletin/modules/logout/logout.php">Logout</a></li>
    </ul>
</nav>

<script>
function toggleMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('#nav-menu');
    
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
}

// Close menu when clicking on a link
document.querySelectorAll('#nav-menu a').forEach(link => {
    link.addEventListener('click', () => {
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('#nav-menu');
        
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    });
});

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('#nav-menu');
    
    if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    }
});
</script>

<script src="/SmartBulletin/js/session-heartbeat.js"></script>
<script src="/SmartBulletin/js/pwa-register.js"></script>
</body>
</html>