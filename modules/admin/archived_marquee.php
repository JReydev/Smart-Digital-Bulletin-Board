<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';

// Get user role
$user_role = $_SESSION['role_id'] ?? 0;

// Only admins can view archived marquee items
if ($user_role != 1) {
    $_SESSION['error'] = 'You do not have permission to view archived marquee items. Only admins can access this page.';
    header("Location: manage_marquee.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Marquee Items</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #7B0000;
        }

        .page-header h1 {
            color: #7B0000;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            background: rgba(128, 128, 128, 0.1);
            border: 1px solid rgba(128, 128, 128, 0.2);
            padding: 12px 24px;
            font-size: 1em;
            cursor: pointer;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(128, 128, 128, 0.2);
            transform: translateY(-2px);
        }

        .news-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            opacity: 0.7;
        }

        .news-card:hover {
            opacity: 1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .news-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .news-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 5px;
        }

        .archived-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-left: 15px;
        }

        .news-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .news-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .news-content {
            color: #333;
            line-height: 1.8;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            border-left: 3px solid #7B0000;
        }

        .news-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .restore-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #28a745;
            text-decoration: none;
            transition: all 0.3s ease;
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
        }

        .restore-btn:hover {
            background: rgba(40, 167, 69, 0.2);
            transform: translateY(-2px);
        }

        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .priority-high {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .priority-medium {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .priority-low {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4em;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-state p {
            font-size: 1.1em;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            overflow-y: auto;
            padding: 120px 0;
        }

        .modal.show {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .modal-content {
            background: #ffffff;
            width: min(500px, 90%);
            margin: 20px auto;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: #28a745;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
            border: none;
            background: none;
            transition: color 0.3s;
        }

        .close:hover {
            color: #000;
        }

        .modal-body {
            padding: 20px;
        }

        .news-title-modal {
            font-weight: 600;
            color: #7B0000;
            margin: 10px 0;
            font-size: 1.2em;
        }

        .info-text {
            color: #666;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .cancel-btn, .confirm-restore-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cancel-btn {
            background: rgba(128, 128, 128, 0.1);
            color: #666;
            border: 1px solid rgba(128, 128, 128, 0.2);
        }

        .cancel-btn:hover {
            background: rgba(128, 128, 128, 0.2);
        }

        .confirm-restore-btn {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .confirm-restore-btn:hover {
            background: rgba(40, 167, 69, 0.2);
        }

        .success-message, .error-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
                margin: 20px auto;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .page-header h1 {
                font-size: 1.5em;
            }
            
            .back-button {
                width: 100%;
                justify-content: center;
            }
            
            .news-card {
                padding: 20px;
            }
            
            .news-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .news-title {
                font-size: 1.3em;
            }
            
            .news-meta {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .news-content {
                padding: 12px;
                font-size: 0.95em;
            }
            
            .news-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .restore-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 15px;
                border-radius: 15px;
            }
            
            .page-header h1 {
                font-size: 1.3em;
            }
            
            .news-card {
                padding: 15px;
            }
            
            .news-title {
                font-size: 1.2em;
            }
            
            .modal-content {
                width: 95%;
            }
            
            .success-message,
            .error-message {
                padding: 12px 15px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-archive"></i> Archived Marquee Items</h1>
        <a href="manage_marquee.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Manage Marquee
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php
    // Get archived news items
    $news_query = "SELECT n.*, u.username as publisher_name 
                   FROM news_update n 
                   LEFT JOIN users u ON n.published_by = u.id 
                   WHERE n.is_archived = 1 
                   ORDER BY n.published_at DESC";
    $news_result = mysqli_query($conn, $news_query);
    
    if (mysqli_num_rows($news_result) == 0) {
        echo '<div class="empty-state">
                <i class="fas fa-archive"></i>
                <h2>No Archived Marquee Items</h2>
                <p>There are currently no archived marquee items.</p>
              </div>';
    } else {
        while ($news = mysqli_fetch_assoc($news_result)) {
            $priority_class = 'priority-low';
            $priority_text = 'Low';
            if ($news['priority'] == 1) {
                $priority_class = 'priority-high';
                $priority_text = 'High';
            } elseif ($news['priority'] == 2) {
                $priority_class = 'priority-medium';
                $priority_text = 'Medium';
            }
            
            echo "<div class='news-card'>";
            
            echo "<div class='news-header'>";
            echo "<div>";
            echo "<span class='news-title'>" . htmlspecialchars($news['title']) . "</span>";
            echo "<span class='archived-badge'>Archived</span>";
            echo "</div>";
            echo "</div>";
            
            echo "<div class='news-meta'>";
            echo "<span class='news-meta-item'><i class='fas fa-user'></i> " . htmlspecialchars($news['publisher_name'] ?? 'Unknown') . "</span>";
            echo "<span class='news-meta-item'><i class='fas fa-calendar'></i> " . date('M d, Y', strtotime($news['published_at'])) . "</span>";
            echo "<span class='priority-badge $priority_class'><i class='fas fa-flag'></i> $priority_text Priority</span>";
            
            if ($news['expires_at']) {
                $is_expired = strtotime($news['expires_at']) < time();
                $expire_color = $is_expired ? '#dc3545' : '#666';
                echo "<span class='news-meta-item' style='color: $expire_color;'>";
                echo "<i class='fas fa-clock'></i> " . ($is_expired ? 'Expired: ' : 'Expires: ') . date('M d, Y', strtotime($news['expires_at']));
                echo "</span>";
            }
            echo "</div>";
            
            echo "<div class='news-content'>" . nl2br(htmlspecialchars($news['content'])) . "</div>";
            
            echo "<div class='news-actions'>";
            echo "<button type='button' class='restore-btn' onclick=\"showRestoreModal('{$news['id']}', '" . htmlspecialchars($news['title'], ENT_QUOTES) . "')\">
                    <i class='fas fa-undo'></i> Restore
                  </button>";
            echo "</div>";
            
            echo "</div>"; // news-card
        }
    }
    ?>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal" id="restoreModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-undo"></i> Restore Marquee Item</h3>
            <button type="button" class="close" onclick="closeRestoreModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to restore this marquee item?</p>
            <p class="news-title-modal" id="newsTitle"></p>
            <p class="info-text">
                <i class="fas fa-info-circle"></i> This item will be restored and will appear in the active marquee.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="cancel-btn" onclick="closeRestoreModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="confirm-restore-btn" onclick="confirmRestore()">
                <i class="fas fa-undo"></i> Restore
            </button>
        </div>
    </div>
</div>

<script>
let restoreId = null;

function showRestoreModal(id, title) {
    restoreId = id;
    document.getElementById('newsTitle').textContent = title;
    document.getElementById('restoreModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    setTimeout(() => {
        restoreId = null;
        document.getElementById('newsTitle').textContent = '';
    }, 300);
}

function confirmRestore() {
    if (!restoreId) return;
    
    const submitBtn = document.querySelector('.confirm-restore-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';
    
    fetch('restore_marquee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `news_id=${encodeURIComponent(restoreId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRestoreModal();
            showSuccess(data.message || 'Marquee item restored successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Failed to restore marquee item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'Failed to restore marquee item. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-undo"></i> Restore';
    });
}

function showSuccess(message) {
    const alert = document.createElement('div');
    alert.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10000; display: flex; align-items: center; gap: 10px;';
    alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 3000);
}

function showError(message) {
    const alert = document.createElement('div');
    alert.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10000; display: flex; align-items: center; gap: 10px;';
    alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('restoreModal');
    if (event.target === modal) {
        closeRestoreModal();
    }
}

// Add keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRestoreModal();
    }
});
</script>

</body>
</html>

