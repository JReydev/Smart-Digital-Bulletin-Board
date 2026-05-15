<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Ensure role constants are available without requiring header output
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 1);
}
if (!defined('ROLE_SECRETARY')) {
    define('ROLE_SECRETARY', 2);
}
if (!defined('ROLE_OFFICER')) {
    define('ROLE_OFFICER', 3);
}
if (!defined('ROLE_FACULTY')) {
    define('ROLE_FACULTY', 4);
}

// Role mapping for display
$role_names = [
    ROLE_ADMIN => 'Admin',
    ROLE_SECRETARY => 'Secretary',
    ROLE_OFFICER => 'Officer',
    ROLE_FACULTY => 'Faculty'
];

// Check if user is admin
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: /login.php');
    exit;
}

$stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role_id'] !== ROLE_ADMIN) {
    header('Location: /');
    exit;
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_account':
                // Get the raw input values without any manipulation
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $first_name = trim($_POST['first_name'] ?? '');
                $middle_name = trim($_POST['middle_name'] ?? ''); // Only what's in the middle_name field
                $last_name = trim($_POST['last_name'] ?? '');
                $role_id = (int)($_POST['role_id'] ?? 3); // Default to officer role
                
                // Validate input
                if (empty($username) || empty($password) || empty($first_name) || empty($last_name)) {
                    $_SESSION['error'] = 'All required fields must be filled out';
                } else {
                    // Check if username already exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $_SESSION['error'] = 'Username already exists';
                    } else {
                        // Create the account with exact values from form
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (username, password, first_name, middle_name, last_name, role_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                        $stmt->bind_param("sssssi", $username, $hashed_password, $first_name, $middle_name, $last_name, $role_id);
                        
                        if ($stmt->execute()) {
                            $new_user_id = $stmt->insert_id;
                            // Log the activity
                            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_type, activity_details) VALUES (?, 'account_created', ?)");
                            $details = "Admin created new account for user: {$username}";
                            $stmt->bind_param("is", $user_id, $details);
                            $stmt->execute();
                            
                            $_SESSION['success'] = 'Account created successfully';
                        } else {
                            $_SESSION['error'] = 'Failed to create account';
                        }
                    }
                }
                
                // Redirect back to the same page
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                break;
                
            case 'toggle_status':
                $target_user_id = $_POST['user_id'] ?? null;
                
                if ($target_user_id && $target_user_id != $user_id) { // Prevent self-deactivation
                    // Get current status
                    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    if ($user) {
                        $new_status = !$user['is_active'];
                        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
                        $stmt->bind_param("ii", $new_status, $target_user_id);
                        
                        if ($stmt->execute()) {
                            // Log the activity
                            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_type, activity_details) VALUES (?, 'status_updated', ?)");
                            $details = "Admin " . ($new_status ? "activated" : "deactivated") . " account for user ID {$target_user_id}";
                            $stmt->bind_param("is", $user_id, $details);
                            $stmt->execute();
                        }
                    }
                }
                
                // Redirect back to the same page
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                break;
            
            case 'update_role':
                $target_user_id = $_POST['user_id'] ?? null;
                $new_role = $_POST['role'] ?? null;
                
                if ($target_user_id && $new_role) {
                    $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_role, $target_user_id);
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'User role updated successfully';
                        
                        // Log the activity
                        $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_type, activity_details) VALUES (?, 'role_updated', ?)");
                        $details = "Admin updated role for user ID {$target_user_id} to {$new_role}";
                        $stmt->bind_param("is", $user_id, $details);
                        $stmt->execute();
                    } else {
                        $response['message'] = 'Failed to update user role';
                    }
                }
                break;

            case 'update_user':
                $target_user_id = $_POST['user_id'] ?? null;
                $username = trim($_POST['username'] ?? '');
                $first_name = trim($_POST['first_name'] ?? '');
                $middle_name = trim($_POST['middle_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $role_id = (int)($_POST['role_id'] ?? 3);
                
                if ($target_user_id && !empty($username) && !empty($first_name) && !empty($last_name)) {
                    // Check if username exists for other users
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $stmt->bind_param("si", $username, $target_user_id);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $_SESSION['error'] = 'Username already exists';
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, middle_name = ?, last_name = ?, role_id = ? WHERE id = ?");
                        $stmt->bind_param("ssssii", $username, $first_name, $middle_name, $last_name, $role_id, $target_user_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = 'User updated successfully';
                            
                            // Log the activity
                            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_type, activity_details) VALUES (?, 'user_updated', ?)");
                            $details = "Admin updated user information for user ID {$target_user_id}";
                            $stmt->bind_param("is", $user_id, $details);
                            $stmt->execute();
                        } else {
                            $_SESSION['error'] = 'Failed to update user';
                        }
                    }
                } else {
                    $_SESSION['error'] = 'All required fields must be filled out';
                }
                
                // Redirect back to the same page
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                break;
        }
    }
}

// Defer displaying messages until after header include to avoid premature output
$pendingSuccess = $_SESSION['success'] ?? null;
$pendingError = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total users count
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_pages = ceil($total_users / $per_page);

// Get users for current page
$stmt = $conn->prepare("
    SELECT u.*, 
           f.name as faculty_name,
           f.prefix as faculty_prefix,
           f.suffix as faculty_suffix,
           f.position as faculty_position,
           m.file_path as faculty_image,
           (SELECT COUNT(*) FROM user_activity_log WHERE user_id = u.id) as activity_count,
           (SELECT MAX(created_at) FROM user_activity_log WHERE user_id = u.id) as last_activity
    FROM users u
    LEFT JOIN faculty f ON u.id = f.user_id
    LEFT JOIN multimedia_content m ON f.media_id = m.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<?php include __DIR__ . '/../include/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Accounts - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
            margin: 20px auto;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 90%;
            max-width: 1200px;
            border-radius: 10px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        .header-actions {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .action-btn.create {
            background: #7B0000;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .action-btn.create:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }

        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .filters select, 
        .filters input {
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 0.95rem;
            color: #333;
            transition: all 0.3s ease;
        }

        .filters select:focus,
        .filters input:focus {
            outline: none;
            border-color: #7B0000;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }

        .filters input::placeholder {
            color: #999;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            /* Prevent over-squeezing on small screens; enable horizontal scroll via wrapper */
            min-width: 700px;
        }

        /* Responsive table container */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .users-table th {
            background: #7B0000;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            word-break: break-word;
            white-space: normal;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .role-badge.role-admin {
            background: #7B0000;
            color: white;
        }

        .role-badge.role-secretary {
            background: #8B0000;
            color: white;
        }

        .role-badge.role-officer {
            background: #9B0000;
            color: white;
        }

        .role-badge.role-faculty {
            background: #006633;
            color: white;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .status-badge.active {
            background: #28a745;
            color: white;
        }

        .status-badge.inactive {
            background: #dc3545;
            color: white;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-right: 8px;
            white-space: nowrap;
        }

        .action-btn.edit {
            background: #7B0000;
            color: white;
        }

        .action-btn.edit:hover {
            background: #8B0000;
        }

        .action-btn.deactivate {
            background: #dc3545;
            color: white;
        }

        .action-btn.deactivate:hover {
            background: #c82333;
        }

        .action-btn.activate {
            background: #28a745;
            color: white;
        }

        .action-btn.activate:hover {
            background: #218838;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #f8f9fa;
        }

        .pagination a.active {
            background: #7B0000;
            color: white;
            border-color: #7B0000;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            overflow-y: auto;
            padding: 120px 0 40px;
        }

        .modal.show {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .modal-content {
            background: #ffffff;
            padding: 25px;
            border-radius: 16px;
            width: min(420px, 95%);
            position: relative;
            border: 1px solid #e0e0e0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            color: #333;
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal h3 {
            margin-bottom: 20px;
            color: #7B0000;
            font-size: 1.3em;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group label {
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }
        
        .form-group label i {
            color: #7B0000;
            width: 16px;
            margin-right: 6px;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #7B0000;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }
        
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%237B0000' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 12px) center;
            padding-right: 35px;
        }

        .form-group select option {
            background: #ffffff;
            color: #333;
            padding: 8px;
        }
        
        .required::after {
            content: " *";
            color: #7B0000;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 5px;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .modal-buttons .confirm {
            background: #7B0000;
            color: white;
        }

        .modal-buttons .cancel {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e0e0e0;
        }

        .modal-buttons .confirm:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }

        .modal-buttons .cancel:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .modal h3 i {
            color: #7B0000;
            margin-right: 8px;
        }

        .modal-buttons .confirm i {
            color: white;
        }

        .modal-buttons .cancel i {
            color: #333;
        }

        @media (max-width: 768px) {
            .modal {
                padding: 15px;
            }
            
            .modal-content {
                padding: 20px;
            }
            
            .form-group input,
            .form-group select,
            .modal-buttons button {
                padding: 8px 14px;
            }

            /* Center the Create New Account button on mobile */
            .header-actions {
                justify-content: center;
            }

            /* Stack filters on small screens */
            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filters select,
            .filters input {
                width: 100%;
            }

            /* Hide less critical columns (Joined, Last Activity) on small screens */
            .users-table th:nth-child(4),
            .users-table td:nth-child(4),
            .users-table th:nth-child(5),
            .users-table td:nth-child(5) {
                display: none;
            }

            /* Reduce text sizes for better mobile readability */
            .header {
                font-size: 1.4em;
                padding: 12px 15px;
                width: 95%;
            }

            .container {
                width: 95%;
                padding: 15px;
                margin: 15px auto;
            }

            .users-table {
                font-size: 0.9rem;
            }

            .user-username {
                font-size: 0.9rem;
            }

            .user-fullname {
                font-size: 0.8rem;
            }

            .faculty-position {
                font-size: 0.75rem;
            }

            .role-badge,
            .status-badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }

            .modal h3 {
                font-size: 1.1em;
            }

            .form-group label {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                font-size: 1.2em;
                padding: 10px 12px;
                width: 98%;
            }

            .container {
                width: 98%;
                padding: 12px;
                margin: 10px auto;
            }

            .users-table {
                font-size: 0.85rem;
            }

            .user-username {
                font-size: 0.85rem;
            }

            .user-fullname {
                font-size: 0.75rem;
            }

            .faculty-position {
                font-size: 0.7rem;
            }

            .role-badge,
            .status-badge {
                font-size: 0.7rem;
                padding: 3px 6px;
            }

            .modal h3 {
                font-size: 1em;
            }

            .form-group label {
                font-size: 0.85rem;
            }

            .filters select,
            .filters input {
                font-size: 14px; /* Prevents zoom on iOS */
                min-height: 44px; /* Touch target size */
            }

            .form-group input,
            .form-group select,
            .modal-buttons button {
                font-size: 16px; /* Prevents zoom on iOS */
                min-height: 44px; /* Touch target size */
            }
        }

        /* PWA safe area support (iOS notch, etc.) */
        @supports (padding: max(0px)) {
            body {
                padding-bottom: max(0px, env(safe-area-inset-bottom));
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            animation: slideIn 0.3s ease;
            transition: opacity 0.3s ease;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .user-username {
            font-weight: 600;
            color: black;
        }

        .user-fullname {
            font-size: 14px;
            color: black;
        }

        .faculty-info {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .faculty-position {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 11px;
            color: #006633;
            margin-top: 2px;
            display: inline-block;
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
    </style>
</head>
<body>
    <div class="header">Manage User Accounts</div>
    <?php if ($pendingSuccess || $pendingError): ?>
        <div id="notification" class="notification <?php echo $pendingSuccess ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($pendingSuccess ?? $pendingError); ?>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="header-actions">
            <button class="action-btn create" onclick="showCreateAccountModal()">
                <i class="fas fa-user-plus"></i> Create New Account
            </button>
        </div>

        <div class="filters">
            <select id="roleFilter">
                <option value="">All Roles</option>
                <option value="<?php echo ROLE_ADMIN; ?>">Admin</option>
                <option value="<?php echo ROLE_SECRETARY; ?>">Secretary</option>
                <option value="<?php echo ROLE_OFFICER; ?>">Officer</option>
                <option value="<?php echo ROLE_FACULTY; ?>">Faculty</option>
            </select>
            
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            
            <input type="text" id="searchInput" placeholder="Search users...">
        </div>

        <div class="table-responsive">
        <table class="users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Last Activity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr data-user-id="<?php echo $user['id']; ?>">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php
                                    // Determine profile picture - prioritize user profile picture, then faculty image
                                    $profilePath = "/SmartBulletin/images/default-avatar.jpg";
                                    if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'default-avatar.png') {
                                        $profilePath = "/SmartBulletin/multimedia/profile/" . htmlspecialchars($user['profile_picture']);
                                    } elseif (!empty($user['faculty_image'])) {
                                        $profilePath = "/SmartBulletin/" . htmlspecialchars($user['faculty_image']);
                                    }
                                ?>
                                <img src="<?php echo $profilePath; ?>" 
                                     alt="Profile" 
                                     onerror="this.src='/SmartBulletin/images/default-avatar.jpg'"
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div>
                                    <div class="user-username"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="user-fullname" 
                                         data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                         data-middle-name="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>"
                                         data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>">
                                        <?php 
                                        // Display faculty name if available, otherwise user name
                                        if ($user['role_id'] == ROLE_FACULTY && !empty($user['faculty_name'])) {
                                            echo htmlspecialchars($user['faculty_name']);
                                        } else {
                                            echo htmlspecialchars($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']);
                                        }
                                        ?>
                                    </div>
                                    <?php if ($user['role_id'] == ROLE_FACULTY && !empty($user['faculty_position'])): ?>
                                        <div class="faculty-position">
                                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($user['faculty_position']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge role-<?php echo strtolower($role_names[$user['role_id']]); ?>" data-role-id="<?php echo $user['role_id']; ?>">
                                <?php echo htmlspecialchars($role_names[$user['role_id']]); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td>
                            <?php 
                            if ($user['last_activity']) {
                                echo date('M j, Y g:i A', strtotime($user['last_activity']));
                            } else {
                                echo 'No activity';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="action-btn edit" onclick="editUser(<?php echo $user['id']; ?>, <?php echo $user['role_id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="action-btn <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>" 
                                        onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'true' : 'false'; ?>)">
                                    <i class="fas <?php echo $user['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h3><i class="fas fa-user-edit"></i> Edit User</h3>
            <form id="editUserForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-group">
                    <label class="required" for="edit_username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="edit_username" name="username" required placeholder="Enter username">
                </div>
                
                <div class="form-group">
                    <label class="required" for="edit_first_name"><i class="fas fa-id-card"></i> First Name</label>
                    <input type="text" id="edit_first_name" name="first_name" required placeholder="Enter first name">
                </div>
                
                <div class="form-group">
                    <label for="edit_middle_name"><i class="fas fa-id-card"></i> Middle Name</label>
                    <input type="text" id="edit_middle_name" name="middle_name" placeholder="Enter middle name">
                </div>
                
                <div class="form-group">
                    <label class="required" for="edit_last_name"><i class="fas fa-id-card"></i> Last Name</label>
                    <input type="text" id="edit_last_name" name="last_name" required placeholder="Enter last name">
                </div>
                
                <div class="form-group">
                    <label class="required" for="edit_role_id"><i class="fas fa-user-tag"></i> Role</label>
                    <select id="edit_role_id" name="role_id" required>
                        <option value="3">Officer</option>
                        <option value="2">Secretary</option>
                        <option value="4">Faculty</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="cancel" onclick="closeModal('editModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="confirm">
                        <i class="fas fa-check"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Account Modal -->
    <div class="modal" id="createAccountModal">
        <div class="modal-content">
            <h3><i class="fas fa-user-plus"></i> Create New Account</h3>
            <form id="createAccountForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="action" value="create_account">
                
                <div class="form-group">
                    <label class="required" for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter username">
                </div>
                
                <div class="form-group">
                    <label class="required" for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter password">
                </div>
                
                <div class="form-group">
                    <label class="required" for="first_name"><i class="fas fa-id-card"></i> First Name</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="Enter first name">
                </div>
                
                <div class="form-group">
                    <label for="middle_name"><i class="fas fa-id-card"></i> Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" placeholder="Enter middle name">
                </div>
                
                <div class="form-group">
                    <label class="required" for="last_name"><i class="fas fa-id-card"></i> Last Name</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Enter last name">
                </div>
                
                <div class="form-group">
                    <label class="required" for="role_id"><i class="fas fa-user-tag"></i> Role</label>
                    <select id="role_id" name="role_id" required>
                        <option value="3">Officer</option>
                        <option value="2">Secretary</option>
                        <option value="4">Faculty</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="cancel" onclick="closeModal('createAccountModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="confirm">
                        <i class="fas fa-check"></i> Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filter functionality
        document.getElementById('roleFilter').addEventListener('change', filterUsers);
        document.getElementById('statusFilter').addEventListener('change', filterUsers);
        document.getElementById('searchInput').addEventListener('input', filterUsers);

        function filterUsers() {
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const search = document.getElementById('searchInput').value.toLowerCase();
            
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const userRole = row.querySelector('.role-badge').getAttribute('data-role-id');
                const userStatus = row.querySelector('.status-badge').textContent.toLowerCase();
                const userName = row.querySelector('td:first-child').textContent.toLowerCase();
                
                const roleMatch = !role || userRole === role;
                const statusMatch = !status || userStatus === status;
                const searchMatch = !search || userName.includes(search);
                
                row.style.display = roleMatch && statusMatch && searchMatch ? '' : 'none';
            });
        }

        function showNotification(message, type) {
            // Remove any existing notification
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            // Create and show new notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        function editUser(userId, currentRoleId) {
            // Find the row containing the user data using data attribute
            const row = document.querySelector(`.users-table tbody tr[data-user-id="${userId}"]`);
            
            if (!row) return;
            
            // Get user data using specific class names
            const username = row.querySelector('.user-username').textContent.trim();
            const firstName = row.querySelector('.user-fullname').dataset.firstName;
            const middleName = row.querySelector('.user-fullname').dataset.middleName;
            const lastName = row.querySelector('.user-fullname').dataset.lastName;
            
            // Populate the form
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_middle_name').value = middleName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_role_id').value = currentRoleId;
            
            // Show the modal
            document.getElementById('editModal').classList.add('show');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            if (modalId === 'createAccountModal') {
                document.getElementById('createAccountForm').reset();
            }
        }

        async function updateUser(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'update_user');
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Failed to update user', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while updating the user', 'error');
            }
            
            closeModal('editModal');
            return false;
        }

        function toggleUserStatus(userId, currentStatus) {
            if (confirm(`Are you sure you want to ${currentStatus ? 'deactivate' : 'activate'} this user's account?`)) {
                // Submit the form directly
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                form.appendChild(actionInput);
                form.appendChild(userIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                if (event.target.id === 'createAccountModal') {
                    document.getElementById('createAccountForm').reset();
                }
            }
        }

        function showCreateAccountModal() {
            const modal = document.getElementById('createAccountModal');
            modal.classList.add('show');
            document.getElementById('createAccountForm').reset();
        }

        // Auto-hide notifications
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>