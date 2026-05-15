<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Get user role
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

// Check if user is admin
if ($user_role !== 1) {
    header('Location: ../homepage.php');
    exit();
}

$message = '';
$error = '';

// Handle manual cleanup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    try {
        // Clean up expired welcome messages
        $welcomeCleanupQuery = "
            UPDATE welcome_message 
            SET is_active = 0 
            WHERE is_active = 1 
            AND auto_expire = 1 
            AND expires_at IS NOT NULL 
            AND expires_at <= NOW()
        ";
        
        $welcomeResult = mysqli_query($conn, $welcomeCleanupQuery);
        $welcomeAffected = mysqli_affected_rows($conn);
        
        // Clean up expired news items
        $newsCleanupQuery = "
            UPDATE news_update 
            SET is_active = 0 
            WHERE is_active = 1 
            AND auto_expire = 1 
            AND expires_at IS NOT NULL 
            AND expires_at <= NOW()
        ";
        
        $newsResult = mysqli_query($conn, $newsCleanupQuery);
        $newsAffected = mysqli_affected_rows($conn);
        
        if ($welcomeResult && $newsResult) {
            $message = "Cleanup completed successfully! Deactivated $welcomeAffected welcome message(s) and $newsAffected news item(s).";
        } else {
            $error = 'Error during cleanup process.';
        }
        
    } catch (Exception $e) {
        $error = 'Error during cleanup: ' . $e->getMessage();
    }
}

// Get statistics
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM welcome_message WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())) as active_welcome,
        (SELECT COUNT(*) FROM news_update WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())) as active_news,
        (SELECT COUNT(*) FROM welcome_message WHERE is_active = 0 AND expires_at IS NOT NULL AND expires_at <= NOW()) as expired_welcome,
        (SELECT COUNT(*) FROM news_update WHERE is_active = 0 AND expires_at IS NOT NULL AND expires_at <= NOW()) as expired_news,
        (SELECT COUNT(*) FROM welcome_message WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at <= NOW()) as expired_active_welcome,
        (SELECT COUNT(*) FROM news_update WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at <= NOW()) as expired_active_news
";

$statsResult = mysqli_query($conn, $statsQuery);
$stats = $statsResult ? mysqli_fetch_assoc($statsResult) : null;

// Get upcoming expirations (next 7 days)
$upcomingQuery = "
    SELECT 'welcome' as type, id, message as title, expires_at 
    FROM welcome_message 
    WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'news' as type, id, title, expires_at 
    FROM news_update 
    WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY expires_at ASC
";

$upcomingResult = mysqli_query($conn, $upcomingQuery);

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
        .header {
            font-size: 1.4em;
            padding: 12px 15px;
            width: 95%;
        }
        
        .container {
            width: 95%;
            padding: 15px;
        }
        
        .card {
            padding: 20px;
        }
        
        .card h2 {
            font-size: 1.2em;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-number {
            font-size: 1.6em;
        }
        
        .btn {
            padding: 10px 18px;
            font-size: 0.9em;
            min-height: 44px; /* Touch target size */
            width: 100%;
            margin-bottom: 10px;
        }
        
        .table {
            font-size: 0.9em;
        }
        
        .table th,
        .table td {
            padding: 8px;
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
        }
        
        .card {
            padding: 15px;
        }
        
        .card h2 {
            font-size: 1.1em;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .stat-number {
            font-size: 1.4em;
        }
        
        .stat-label {
            font-size: 0.85em;
        }
        
        .btn {
            padding: 12px 16px;
            font-size: 0.85em;
        }
        
        .table {
            font-size: 0.85em;
        }
        
        .table th,
        .table td {
            padding: 6px;
        }
        
        /* Hide less critical columns on very small screens */
        .table th:nth-child(3),
        .table td:nth-child(3) {
            display: none;
        }
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
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        border-left: 4px solid #7B0000;
    }
    
    .stat-number {
        font-size: 2em;
        font-weight: bold;
        color: #7B0000;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9em;
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
    
    .btn-warning {
        background: #ffc107;
        color: #000;
    }
    
    .btn-warning:hover {
        background: #e0a800;
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
    
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #555;
    }
    
    .expired {
        color: #dc3545;
        font-weight: 600;
    }
    
    .expiring-soon {
        color: #ffc107;
        font-weight: 600;
    }
    
    .back-link {
        color: #7B0000;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 20px;
        display: inline-block;
    }
    
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
</style>

<title>Marquee Expiration Management - Admin</title>

<div class="container">
    <div class="header">
        <span>Marquee Expiration Management</span>
    </div>
    
    <a href="manage_marquee.php" class="back-link">← Back to Marquee Management</a>
    
    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="card">
        <h2>Current Statistics</h2>
        <?php if ($stats): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_welcome']; ?></div>
                    <div class="stat-label">Active Welcome Messages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_news']; ?></div>
                    <div class="stat-label">Active News Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['expired_welcome']; ?></div>
                    <div class="stat-label">Expired Welcome Messages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['expired_news']; ?></div>
                    <div class="stat-label">Expired News Items</div>
                </div>
            </div>
            
            <?php if ($stats['expired_active_welcome'] > 0 || $stats['expired_active_news'] > 0): ?>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> There are <?php echo ($stats['expired_active_welcome'] + $stats['expired_active_news']); ?> active items that have expired but haven't been cleaned up yet.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Manual Cleanup -->
    <div class="card">
        <h2>Manual Cleanup</h2>
        <p>Click the button below to manually clean up expired marquee content. This will deactivate all expired items that have auto-expire enabled.</p>
        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="cleanup">
            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to clean up expired content? This will deactivate expired items.');">
                Clean Up Expired Content
            </button>
        </form>
    </div>
    
    <!-- Upcoming Expirations -->
    <div class="card">
        <h2>Upcoming Expirations (Next 7 Days)</h2>
        <?php if ($upcomingResult && mysqli_num_rows($upcomingResult) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Expires At</th>
                        <th>Time Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($upcomingResult)): ?>
                        <?php 
                        $expiresAt = strtotime($item['expires_at']);
                        $now = time();
                        $timeRemaining = $expiresAt - $now;
                        $daysRemaining = floor($timeRemaining / (24 * 3600));
                        $hoursRemaining = floor(($timeRemaining % (24 * 3600)) / 3600);
                        ?>
                        <tr>
                            <td><strong><?php echo ucfirst($item['type']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($item['title'], 0, 50)) . (strlen($item['title']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo date('M j, Y H:i', $expiresAt); ?></td>
                            <td>
                                <?php if ($timeRemaining > 0): ?>
                                    <span class="<?php echo $daysRemaining <= 1 ? 'expiring-soon' : ''; ?>">
                                        <?php echo $daysRemaining > 0 ? $daysRemaining . ' day(s)' : ''; ?>
                                        <?php echo $hoursRemaining > 0 ? $hoursRemaining . ' hour(s)' : ''; ?>
                                        <?php if ($daysRemaining == 0 && $hoursRemaining == 0): ?>
                                            Less than 1 hour
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="expired">Expired</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No items expiring in the next 7 days.</p>
        <?php endif; ?>
    </div>
    
    <!-- Instructions -->
    <div class="card">
        <h2>Automated Cleanup Setup</h2>
        <p>To set up automated cleanup, add this cron job to run daily at midnight:</p>
        <code style="background: #f8f9fa; padding: 10px; border-radius: 5px; display: block; margin: 10px 0; word-break: break-all;">
            0 0 * * * php <?php echo realpath(__DIR__ . '/../../cleanup_expired_marquee.php'); ?>
        </code>
        <p><strong>Note:</strong> Make sure the cleanup script has proper permissions (chmod 755) and the web server can execute PHP scripts from the command line. The path above is the absolute path to the cleanup script on your server.</p>
    </div>
</div>
