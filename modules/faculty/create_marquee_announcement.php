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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $priority = (int)($_POST['priority'] ?? 5);
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $auto_expire = isset($_POST['auto_expire']) ? 1 : 0;
    
    // Validate input
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = 'Title and content are required';
    } else {
        // Insert into news_update table
        $stmt = $conn->prepare("INSERT INTO news_update (title, content, published_by, priority, expires_at, auto_expire, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssiisi", $title, $content, $user_id, $priority, $expires_at, $auto_expire);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Marquee announcement created successfully!';
            header("Location: faculty_marquee_announcements.php");
            exit;
        } else {
            $_SESSION['error'] = 'Failed to create marquee announcement';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Marquee Announcement</title>
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
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        .form-group {
            margin-bottom: 24px;
        }
        label { 
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #7B0000;
            font-size: 1em;
            margin-bottom: 12px;
        }
        label i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }
        input[type="text"], textarea, input[type="number"], input[type="datetime-local"] {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.6;
        }
        input[type="text"]:focus, textarea:focus, input[type="number"]:focus, input[type="datetime-local"]:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        .checkbox-group label {
            margin: 0;
            font-size: 0.9em;
            color: #666;
        }
        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            justify-content: center;
        }
        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid #7B0000;
            background: #7B0000;
            color: white;
        }
        button:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }
        .cancel-btn {
            background: #6c757d;
            border-color: #6c757d;
        }
        .cancel-btn:hover {
            background: #5a6268;
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
        .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        .marquee-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #006633;
            margin-bottom: 30px;
        }
        .marquee-info h3 {
            color: #006633;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .marquee-info p {
            color: #666;
            margin-bottom: 8px;
        }
        .priority-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-top: 15px;
        }
        .priority-info h4 {
            color: #856404;
            margin-bottom: 8px;
        }
        .priority-info ul {
            margin-left: 20px;
            color: #856404;
        }
        .priority-info li {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>

    <div class="header">
        <span><i class="fas fa-bullhorn"></i> Create Marquee Announcement</span>
    </div>
    
    <div class="container">

    <div class="marquee-info">
        <h3><i class="fas fa-info-circle"></i> About Marquee Announcements</h3>
        <p><strong>Marquee announcements</strong> appear in the scrolling text display at the top of the bulletin board.</p>
        <p>These announcements are separate from regular announcements and are designed for quick, important messages.</p>
        <p>They will scroll continuously and be visible to all users viewing the bulletin board.</p>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="title"><i class="fas fa-heading"></i> Announcement Title</label>
            <input type="text" id="title" name="title" required placeholder="Enter a brief title for your announcement">
            <div class="help-text">Keep it short and clear - this will be displayed prominently</div>
        </div>

        <div class="form-group">
            <label for="content"><i class="fas fa-align-left"></i> Announcement Content</label>
            <textarea id="content" name="content" required placeholder="Enter the full content of your announcement..."></textarea>
            <div class="help-text">This is the main message that will scroll in the marquee</div>
        </div>

        <div class="form-group">
            <label for="priority"><i class="fas fa-sort"></i> Priority</label>
            <input type="number" id="priority" name="priority" value="5" min="1" max="10">
            <div class="help-text">Higher numbers = higher priority (1-10)</div>
        </div>

        <div class="priority-info">
            <h4><i class="fas fa-lightbulb"></i> Priority Guidelines</h4>
            <ul>
                <li><strong>1-3:</strong> Low priority (general information)</li>
                <li><strong>4-6:</strong> Medium priority (important notices)</li>
                <li><strong>7-8:</strong> High priority (urgent announcements)</li>
                <li><strong>9-10:</strong> Critical priority (emergency notices)</li>
            </ul>
        </div>

        <div class="form-group">
            <label for="expires_at"><i class="fas fa-calendar-times"></i> Expiration Date (Optional)</label>
            <input type="datetime-local" id="expires_at" name="expires_at">
            <div class="help-text">Leave empty if the announcement should not expire</div>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="auto_expire" name="auto_expire">
            <label for="auto_expire">Enable automatic expiration</label>
        </div>

        <div class="buttons">
            <a href="faculty_marquee_announcements.php" class="cancel-btn">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit">
                <i class="fas fa-bullhorn"></i> Create Marquee Announcement
            </button>
        </div>
    </form>
</div>

<script>
// Set minimum datetime to current time
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('expires_at').min = minDateTime;
});
</script>

</body>
</html>
