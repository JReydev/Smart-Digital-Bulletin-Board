<?php
include_once __DIR__ . '/../include/no_auth_required.php';
include __DIR__ . '/../../database/connect.php'; // Adjust path as needed

// Check if user is already logged in with a valid session
if (isset($_SESSION['user_id'])) {
    // Verify session is still valid in database
    $stmt = $conn->prepare("SELECT id FROM user_sessions WHERE user_id = ? AND session_id = ?");
    $userId = $_SESSION['user_id'];
    $currentSessionId = session_id();
    $stmt->bind_param("is", $userId, $currentSessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If session is valid, redirect to homepage
    if ($result->num_rows > 0) {
        header("Location: ../homepage.php");
        exit();
    } else {
        // Session invalid, clear it
        session_unset();
        session_destroy();
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, password, role_id, first_name, last_name FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Check if there are any existing sessions
                $stmt = $conn->prepare("SELECT COUNT(*) as session_count FROM user_sessions WHERE user_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $session_result = $stmt->get_result();
                $session_data = $session_result->fetch_assoc();
                
                // If there are existing sessions, set a notification for the new login
                if ($session_data['session_count'] > 0) {
                    $_SESSION['new_login_notification'] = true;
                }

                // Invalidate any existing sessions for this user
                $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Clear all session data before creating new session
                // This ensures a clean session for the new login
                // Note: We intentionally clear redirect_url to ensure consistent homepage redirect
                session_unset();
                
                // Create new session
                session_regenerate_id(true); // Generate new session ID
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_id'] = $user['role_id']; // Store role_id in session
                $_SESSION['show_greeting'] = true; // Flag to show greeting popup on homepage
                $_SESSION['user_first_name'] = $user['first_name']; // Store first name for greeting
                
                // Store session info in database
                $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $stmt->bind_param("isss", $user['id'], session_id(), $ip, $user_agent);
                $stmt->execute();

                // Log the login action
                $log_stmt = $conn->prepare("INSERT INTO logs (action, created_by) VALUES ('login', ?)");
                $log_stmt->bind_param("i", $user['id']);
                $log_stmt->execute();
                $log_stmt->close();

                // Redirect logic: 
                // For consistency, always redirect to homepage after successful login
                // The redirect_url feature is preserved for cases where user was redirected from a protected page,
                // but to ensure consistent behavior, we'll default to homepage for all logins
                // This prevents unexpected redirects to random pages (like Archived Events) when logging in
                header("Location: ../homepage.php");
                exit();
            } else {
                $_SESSION['error'] = "Invalid credentials.";
            }
        } else {
            $_SESSION['error'] = "No account found.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="SmartBulletin">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SmartBulletin">
    <meta name="description" content="Faculty of Computer and Management Sciences Bulletin Board System">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-config" content="/SmartBulletin/browserconfig.xml">
    <meta name="msapplication-TileColor" content="#000000">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="theme-color" content="#000000">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/SmartBulletin/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/SmartBulletin/images/logo.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/SmartBulletin/images/logo.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/SmartBulletin/images/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/SmartBulletin/images/logo.png">
    <link rel="shortcut icon" href="/SmartBulletin/images/logo.png">
    
    <title>Login - SmartBulletin</title>
    <script>
        // Prevent back button navigation
        if (window.history && window.history.pushState) {
            window.history.pushState('forward', null, './login.php');
            
            window.onpopstate = function() {
                window.history.pushState('forward', null, './login.php');
                // If user is logged in, redirect to homepage
                <?php if(isset($_SESSION['user_id'])): ?>
                    window.location.href = '../homepage.php';
                <?php endif; ?>
            };
        }
        
        // Disable backspace key navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.matches('input, textarea')) {
                e.preventDefault();
            }
        });
        
        // PWA Service Worker Registration (handled by pwa-register.js)
        // Service worker is now registered via the centralized script in header.php
        
        // PWA Install Prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', function(e) {
            console.log('PWA install prompt triggered');
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button or notification
            showInstallPrompt();
        });
        
        function showInstallPrompt() {
            // You can customize this to show a custom install button
            // For now, we'll just log it
            console.log('PWA can be installed');
        }
        
        // Handle PWA install
        window.addEventListener('appinstalled', function(e) {
            console.log('PWA was installed');
            deferredPrompt = null;
        });
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: url('1.jpg') no-repeat center center;
            background-size: cover;
            padding: 20px;
            overflow-x: hidden;
        }

        .outer-container {
            background-color: rgba(128, 128, 128, 0.6);
            padding: 10px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            width: 450px;
            max-width: 100%;
        }

        .inner-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            text-align: center;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
        }

        .logo {
            width: 120px;
            height: auto;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        input {
            width: 100%;
            padding: 15px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 15px;
            background-color: black;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            margin-top: 10px;
        }

        button:hover {
            background-color: #333;
        }

        .links {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 10px;
        }

        .forgot-password, .signup-link {
            margin-top: 1px;
            font-size: 14px;
            color: #007bff;
            text-decoration: none;
        }

        .forgot-password:hover, .signup-link:hover {
            text-decoration: underline;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .outer-container {
                width: 100%;
                max-width: 320px;
                padding: 10px;
            }

            .inner-container {
                padding: 20px 15px;
            }

            .logo {
                width: 80px;
                height: auto;
            }

            .logo-container {
                margin-bottom: 15px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 12px;
            }

            input {
                padding: 10px;
                font-size: 16px;
                margin: 8px 0;
            }

            button {
                padding: 10px;
                font-size: 16px;
                margin-top: 6px;
            }

            .forgot-password, .signup-link {
                font-size: 12px;
                padding: 6px;
            }
        }

        /* Enhanced Mobile Styles */
        @media (max-width: 480px) {
            body {
                padding: 8px;
            }

            .outer-container {
                max-width: 300px;
                padding: 10px;
            }

            .inner-container {
                padding: 15px 12px;
            }

            .logo {
                width: 70px;
                height: auto;
            }

            h2 {
                font-size: 18px;
                margin-bottom: 10px;
            }

            input {
                padding: 8px;
                font-size: 16px;
                margin: 6px 0;
            }

            button {
                padding: 8px;
                font-size: 16px;
                margin-top: 4px;
            }

            .forgot-password, .signup-link {
                font-size: 11px;
                padding: 4px;
            }
        }

        @media (max-width: 360px) {
            .outer-container {
                max-width: 280px;
                padding: 10px;
            }

            .inner-container {
                padding: 12px 10px;
            }

            .logo-wrapper {
                width: 60px;
                height: 60px;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            h2 {
                font-size: 16px;
                margin-bottom: 8px;
            }

            input {
                padding: 6px;
                font-size: 16px;
                margin: 4px 0;
            }

            button {
                padding: 6px;
                font-size: 16px;
                margin-top: 3px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
            }

            .outer-container {
                padding: 10px;
                border-radius: 10px;
                max-width: 280px;
            }

            .inner-container {
                padding: 15px 12px;
                border-radius: 8px;
            }

            .logo {
                width: 70px;
                height: auto;
            }

            .logo-container {
                margin-bottom: 12px;
            }

            h2 {
                font-size: 18px;
                margin-bottom: 10px;
            }

            input {
                padding: 8px;
                font-size: 16px;
                margin: 6px 0;
            }

            button {
                padding: 8px;
                font-size: 16px;
                margin-top: 4px;
            }

            .error-message {
                font-size: 12px;
                margin-bottom: 6px;
            }

            .forgot-password, .signup-link {
                font-size: 11px;
                padding: 4px;
            }
        }

        @media (max-width: 360px) {
            .outer-container {
                padding: 10px;
                max-width: 260px;
            }

            .inner-container {
                padding: 12px 10px;
            }

            .logo {
                width: 60px;
                height: auto;
            }

            .logo-container {
                margin-bottom: 10px;
            }

            h2 {
                font-size: 16px;
                margin-bottom: 8px;
            }

            input {
                padding: 6px;
                font-size: 16px;
                margin: 4px 0;
            }

            button {
                padding: 6px;
                font-size: 16px;
                margin-top: 3px;
            }
        }

        /* Landscape orientation for mobile */
        @media (max-width: 768px) and (orientation: landscape) {
            body {
                padding: 8px;
            }

            .outer-container {
                width: 280px;
                padding: 10px;
            }

            .inner-container {
                padding: 15px 12px;
            }

            .logo {
                width: 60px;
                height: auto;
            }

            .logo-container {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="outer-container">
        <div class="inner-container">
            <div class="logo-container">
                <img src="fcmslogo2.png" alt="FCMS Logo" class="logo">
                <img src="logoOLFU.png" alt="OLFU Logo" class="logo">
            </div>
            
            <h2>Smart Bulletin Board</h2>
            
            <?php 
            if (isset($_SESSION['error'])) {
                echo '<p class="error-message">' . $_SESSION['error'] . '</p>';
                unset($_SESSION['error']);
            }
            ?>
            
            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <div class="links">
                <a href="../Signup/signup.php" class="forgot-password">Forgot Password?</a>
            </div>
        </div>
    </div>

</body>
</html>
