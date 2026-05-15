<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Check if user is faculty
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

if (!$user || $user['role_id'] != 4) { // 4 = Faculty role
    $_SESSION['error'] = 'Access denied. This page is for faculty members only.';
    header('Location: /');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $announcement_id = (int)$_POST['announcement_id'];
                $stmt = $conn->prepare("SELECT is_active FROM news_update WHERE id = ? AND published_by = ?");
                $stmt->bind_param("ii", $announcement_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $announcement = $result->fetch_assoc();
                
                if ($announcement) {
                    $new_status = !$announcement['is_active'];
                    $stmt = $conn->prepare("UPDATE news_update SET is_active = ? WHERE id = ? AND published_by = ?");
                    $stmt->bind_param("iii", $new_status, $announcement_id, $user_id);
                    $stmt->execute();
                }
                break;
                
            case 'delete':
                $announcement_id = (int)$_POST['announcement_id'];
                $stmt = $conn->prepare("DELETE FROM news_update WHERE id = ? AND published_by = ?");
                $stmt->bind_param("ii", $announcement_id, $user_id);
                $stmt->execute();
                break;
        }
    }
    header("Location: faculty_marquee_announcements.php");
    exit;
}

// Get faculty's marquee announcements
$stmt = $conn->prepare("
    SELECT id, title, content, priority, is_active, published_at, expires_at, auto_expire
    FROM news_update 
    WHERE published_by = ? 
    ORDER BY published_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Marquee Announcements</title>
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
        .header h1 {
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .header-actions {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .create-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #7B0000;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        .create-btn:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }
        .announcement-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .announcement-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .announcement-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 5px;
        }
        .announcement-content {
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .announcement-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #666;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .priority-badge {
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: 500;
            background: #e9ecef;
            color: #495057;
        }
        .priority-badge.high {
            background: #fff3cd;
            color: #856404;
        }
        .priority-badge.critical {
            background: #f8d7da;
            color: #721c24;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .action-btn.toggle {
            background: #17a2b8;
            color: white;
        }
        .action-btn.toggle:hover {
            background: #138496;
        }
        .action-btn.delete {
            background: #dc3545;
            color: white;
        }
        .action-btn.delete:hover {
            background: #c82333;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4em;
            color: #e0e0e0;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            margin-bottom: 10px;
            color: #7B0000;
        }
        .marquee-preview {
            background: #006633;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 0.9em;
            overflow-x: auto;
            white-space: nowrap;
        }
        .expired {
            opacity: 0.6;
            background: #f8f9fa;
        }
        .expired .announcement-title {
            color: #6c757d;
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>

    <div class="header">
        <span><i class="fas fa-bullhorn"></i> My Marquee Announcements</span>
    </div>
    
    <div class="container">
        <div class="header-actions">
            <a href="create_marquee_announcement.php" class="create-btn">
                <i class="fas fa-plus"></i> Create New Announcement
            </a>
        </div>

        <?php if (empty($announcements)): ?>
        <div class="empty-state">
            <i class="fas fa-bullhorn"></i>
            <h3>No Marquee Announcements Yet</h3>
            <p>You haven't created any marquee announcements. Create your first one to get started!</p>
            <a href="create_marquee_announcement.php" class="create-btn" style="margin-top: 20px;">
                <i class="fas fa-plus"></i> Create Your First Announcement
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $announcement): ?>
            <?php
            $is_expired = false;
            if ($announcement['expires_at']) {
                $is_expired = strtotime($announcement['expires_at']) < time();
            }
            ?>
            <div class="announcement-card <?php echo $is_expired ? 'expired' : ''; ?>">
                <div class="announcement-header">
                    <div>
                        <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                        <div class="announcement-content"><?php echo htmlspecialchars($announcement['content']); ?></div>
                    </div>
                    <div>
                        <span class="status-badge <?php echo $announcement['is_active'] ? 'active' : 'inactive'; ?>">
                            <i class="fas <?php echo $announcement['is_active'] ? 'fa-check-circle' : 'fa-pause-circle'; ?>"></i>
                            <?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="announcement-meta">
                    <div class="meta-item">
                        <i class="fas fa-sort"></i>
                        <span>Priority: </span>
                        <span class="priority-badge <?php echo $announcement['priority'] >= 7 ? ($announcement['priority'] >= 9 ? 'critical' : 'high') : ''; ?>">
                            <?php echo $announcement['priority']; ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Created: <?php echo date('M j, Y g:i A', strtotime($announcement['published_at'])); ?></span>
                    </div>
                    <?php if ($announcement['expires_at']): ?>
                        <div class="meta-item">
                            <i class="fas fa-calendar-times"></i>
                            <span>Expires: <?php echo date('M j, Y g:i A', strtotime($announcement['expires_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="marquee-preview">
                    <strong>Marquee Preview:</strong> <?php echo htmlspecialchars($announcement['title']); ?>: <?php echo htmlspecialchars($announcement['content']); ?>
                </div>

                <div class="actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                        <button type="submit" class="action-btn toggle">
                            <i class="fas <?php echo $announcement['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                            <?php echo $announcement['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                        <button type="submit" class="action-btn delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
