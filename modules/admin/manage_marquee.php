<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Get user role (constants are defined in header.php)
$user_role = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $user_role = $user['role_id'];
    }
}

// Check if user is admin (ROLE_ADMIN constant will be available after header inclusion)
if ($user_role !== 1) {  // Using literal value 1 for ROLE_ADMIN since constants aren't defined yet
    header('Location: ../homepage.php');
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_welcome':
                $welcome_message = mysqli_real_escape_string($conn, $_POST['welcome_message']);
                $expires_at = !empty($_POST['expires_at']) ? "'" . mysqli_real_escape_string($conn, $_POST['expires_at']) . "'" : "NULL";
                $auto_expire = isset($_POST['auto_expire']) ? 1 : 0;
                
                $query = "UPDATE welcome_message SET message = '$welcome_message', expires_at = $expires_at, auto_expire = $auto_expire, updated_at = NOW() WHERE is_active = 1";
                if (mysqli_query($conn, $query)) {
                    $message = 'Welcome message updated successfully!';
                } else {
                    $error = 'Failed to update welcome message.';
                }
                break;
                
            case 'add_news':
                $title = mysqli_real_escape_string($conn, $_POST['title']);
                $content = mysqli_real_escape_string($conn, $_POST['content']);
                $priority = (int)$_POST['priority'];
                $expires_at = !empty($_POST['expires_at']) ? "'" . mysqli_real_escape_string($conn, $_POST['expires_at']) . "'" : "NULL";
                $auto_expire = isset($_POST['auto_expire']) ? 1 : 0;
                $user_id = $_SESSION['user_id'];
                
                $query = "INSERT INTO news_update (title, content, published_by, is_active, priority, expires_at, auto_expire) 
                         VALUES ('$title', '$content', $user_id, 1, $priority, $expires_at, $auto_expire)";
                if (mysqli_query($conn, $query)) {
                    $message = 'News item added successfully!';
                } else {
                    $error = 'Failed to add news item.';
                }
                break;
                
            case 'toggle_news':
                $news_id = (int)$_POST['news_id'];
                $is_active = (int)$_POST['is_active'];
                $query = "UPDATE news_update SET is_active = $is_active WHERE id = $news_id";
                if (mysqli_query($conn, $query)) {
                    $message = 'News status updated successfully!';
                } else {
                    $error = 'Failed to update news status.';
                }
                break;
                
            case 'delete_news':
                $news_id = (int)$_POST['news_id'];
                $user_id = $_SESSION['user_id'];
                $user_role = $_SESSION['role_id'];
                
                // Check permissions: only creator or admin can archive
                $check_query = "SELECT published_by FROM news_update WHERE id = $news_id";
                $check_result = mysqli_query($conn, $check_query);
                
                if ($check_result && mysqli_num_rows($check_result) > 0) {
                    $news = mysqli_fetch_assoc($check_result);
                    
                    if ($news['published_by'] == $user_id || $user_role == 1) {
                        // Archive instead of delete
                        $query = "UPDATE news_update SET is_archived = 1 WHERE id = $news_id";
                        if (mysqli_query($conn, $query)) {
                            $message = 'News item archived successfully!';
                        } else {
                            $error = 'Failed to archive news item.';
                        }
                    } else {
                        $error = 'You do not have permission to archive this news item. Only the creator or an admin can archive it.';
                    }
                } else {
                    $error = 'News item not found.';
                }
                break;
        }
    }
}

// Get current welcome message
$welcome_query = "SELECT message, expires_at, auto_expire FROM welcome_message WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1";
$welcome_result = mysqli_query($conn, $welcome_query);
$current_welcome = '';
$current_expires_at = '';
$current_auto_expire = 0;
if ($welcome_result && mysqli_num_rows($welcome_result) > 0) {
    $welcome_row = mysqli_fetch_assoc($welcome_result);
    $current_welcome = $welcome_row['message'];
    $current_expires_at = $welcome_row['expires_at'];
    $current_auto_expire = $welcome_row['auto_expire'];
}

// Get all news items (exclude archived)
$news_query = "SELECT id, title, content, is_active, priority, published_at, expires_at, auto_expire
               FROM news_update WHERE is_archived = 0 ORDER BY priority ASC, published_at DESC";
$news_result = mysqli_query($conn, $news_query);

// Include header for navigation
include __DIR__ . '/../include/header.php';
?>

<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #7B0000;
            color: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #7B0000;
            margin-bottom: 20px;
            font-size: 1.4em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #7B0000;
            color: #fff;
        }
        
        .btn-primary:hover {
            background: #5a0000;
        }
        
        .btn-success {
            background: #28a745;
            color: #fff;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 700px; /* allow horizontal scroll on small screens */
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            word-break: break-word;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .status-active {
            color: #28a745;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Responsive table container */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .col {
            flex: 1;
            min-width: 250px;
        }
        
        .back-link {
            color: #7B0000;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
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
            align-items: center;
            justify-content: center;
            width: 98%;
            max-width: 1200px;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                font-size: 1.4em;
                padding: 10px 12px;
                margin: 10px auto;
                width: 98%;
            }
            
            .card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .card h2 {
                font-size: 1.2em;
            }
            
            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 10px;
                font-size: 13px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .table {
                font-size: 13px;
            }
            
            .table th,
            .table td {
                padding: 8px;
            }

            /* Hide Published column on small screens */
            .table th:nth-child(6),
            .table td:nth-child(6) {
                display: none;
            }
            
            .row {
                flex-direction: column;
                gap: 15px;
            }
            
            .col {
                min-width: unset;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .header {
                font-size: 1.2em;
                padding: 8px 10px;
                margin: 5px auto;
                width: 100%;
            }
            
            .card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .card h2 {
                font-size: 1.1em;
                margin-bottom: 15px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 8px;
                font-size: 12px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 12px;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 6px;
            }
            
            .btn-sm {
                padding: 4px 8px;
                font-size: 10px;
            }
        }
        
        @media (max-width: 360px) {
            .container {
                padding: 5px;
            }
            
            .header {
                font-size: 1.1em;
                padding: 6px 8px;
            }
            
            .card {
                padding: 12px;
            }
            
            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 6px;
                font-size: 11px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 11px;
            }
        }

        /* PWA safe area support */
        @supports (padding: max(0px)) {
            body {
                padding-bottom: max(0px, env(safe-area-inset-bottom));
            }
        }
</style>

<title>Manage Marquee - Admin</title>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <span>Manage Marquee Content</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display: flex; gap: 10px;">
                <a href="archived_marquee.php" class="btn" style="background: rgba(128, 128, 128, 0.1); border: 1px solid rgba(128, 128, 128, 0.2); color: #666;">
                    <i class="fas fa-archive"></i> View Archived
                </a>
            </div>
            <div>
                <a href="marquee_expiration.php" class="btn btn-primary">Expiration Management</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <!-- Welcome Message Section -->
        <div class="card">
            <h2>Welcome Message</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_welcome">
                <div class="form-group">
                    <label for="welcome_message">Welcome Message</label>
                    <textarea name="welcome_message" id="welcome_message" placeholder="Enter the welcome message that will appear first in the marquee" required><?php echo htmlspecialchars($current_welcome); ?></textarea>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="expires_at">Expiration Date & Time (Optional)</label>
                            <input type="datetime-local" name="expires_at" id="expires_at" value="<?php echo $current_expires_at ? date('Y-m-d\TH:i', strtotime($current_expires_at)) : ''; ?>">
                            <small style="color: #666; font-size: 0.9em;">Leave empty for no expiration</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="auto_expire" id="auto_expire" <?php echo $current_auto_expire ? 'checked' : ''; ?>>
                                Auto-expire when date is reached
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Welcome Message</button>
            </form>
        </div>
        
        <!-- Add News Section -->
        <div class="card">
            <h2>Add News Item</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_news">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="title">News Title</label>
                            <input type="text" name="title" id="title" placeholder="Enter news title" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="priority">Priority (lower = higher priority)</label>
                            <input type="number" name="priority" id="priority" value="1" min="1" max="100" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="content">News Content</label>
                    <textarea name="content" id="content" placeholder="Enter news content" required></textarea>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="expires_at">Expiration Date & Time (Optional)</label>
                            <input type="datetime-local" name="expires_at" id="expires_at">
                            <small style="color: #666; font-size: 0.9em;">Leave empty for no expiration</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="auto_expire" id="auto_expire">
                                Auto-expire when date is reached
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add News Item</button>
            </form>
        </div>
        
        <!-- News Items List -->
        <div class="card">
            <h2>Current News Items</h2>
            <?php if (mysqli_num_rows($news_result) > 0): ?>
                <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Priority</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($news = mysqli_fetch_assoc($news_result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($news['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($news['content'], 0, 100)) . (strlen($news['content']) > 100 ? '...' : ''); ?></td>
                                <td><?php echo $news['priority']; ?></td>
                                <td>
                                    <?php if ($news['expires_at']): ?>
                                        <?php 
                                        $expires_at = strtotime($news['expires_at']);
                                        $now = time();
                                        $is_expired = $expires_at <= $now;
                                        ?>
                                        <span style="color: <?php echo $is_expired ? '#dc3545' : '#28a745'; ?>; font-weight: 600;">
                                            <?php echo date('M j, Y H:i', $expires_at); ?>
                                            <?php if ($is_expired): ?>
                                                <br><small style="color: #dc3545;">(Expired)</small>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $news['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $news['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($news['published_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_news">
                                        <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $news['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $news['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $news['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to archive this news item?');">
                                        <input type="hidden" name="action" value="delete_news">
                                        <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <p>No news items found. Add some news items to display in the marquee.</p>
            <?php endif; ?>
        </div>
</div>
