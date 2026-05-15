<?php
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

// Only admins can view archived partylists
$user_role = $_SESSION['role_id'] ?? 0;
if ($user_role != 1) {
    $_SESSION['error'] = 'You do not have permission to view archived partylists. Only admins can access this page.';
    header("Location: officers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Partylists</title>
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
        .partylist-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            opacity: 0.7;
        }
        .partylist-card:hover {
            opacity: 1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .partylist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #7B0000;
        }
        .partylist-name {
            font-size: 1.8em;
            font-weight: 600;
            color: #7B0000;
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
        .officers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .officer-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            text-align: center;
        }
        .officer-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .officer-position {
            color: #666;
            font-size: 0.9em;
        }
        .partylist-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        .restore-btn i {
            font-size: 1em;
            color: #28a745;
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
        .officer-count {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
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
        .partylist-name-modal {
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
        .warning-text {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9em;
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
            
            .partylist-card {
                padding: 20px;
            }
            
            .partylist-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .partylist-name {
                font-size: 1.5em;
            }
            
            .officers-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .officer-item {
                padding: 12px;
            }
            
            .partylist-actions {
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
            
            .partylist-card {
                padding: 15px;
            }
            
            .partylist-name {
                font-size: 1.3em;
            }
            
            .modal-content {
                width: 95%;
            }
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-archive"></i> Archived Partylists</h1>
        <a href="officers.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Officers
        </a>
    </div>

    <?php
    // Get archived partylists
    $partylists = mysqli_query($conn, "SELECT * FROM partylist WHERE is_archived = 1 ORDER BY name ASC");
    
    if (mysqli_num_rows($partylists) == 0) {
        echo '<div class="empty-state">
                <i class="fas fa-archive"></i>
                <h2>No Archived Partylists</h2>
                <p>There are currently no archived partylists.</p>
              </div>';
    } else {
        while ($partylist = mysqli_fetch_assoc($partylists)) {
            echo "<div class='partylist-card'>";
            
            echo "<div class='partylist-header'>";
            echo "<div>";
            echo "<span class='partylist-name'>{$partylist['name']}</span>";
            echo "<span class='archived-badge'>Archived</span>";
            
            // Count archived officers in this partylist
            $officer_count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM officers WHERE partylist_id = {$partylist['id']} AND is_archived = 1");
            $officer_count = mysqli_fetch_assoc($officer_count_query)['count'];
            echo "<div class='officer-count'><i class='fas fa-users'></i> {$officer_count} archived officer(s)</div>";
            echo "</div>";
            echo "</div>";
            
            // Get archived officers for this partylist
            $officers = mysqli_query($conn, "
                SELECT * FROM officers 
                WHERE partylist_id = {$partylist['id']} AND is_archived = 1
                ORDER BY FIELD(position,
                    'President',
                    'Internal Vice President', 'External Vice President', 'Secretary', 'Assistant Secretary',
                    'Treasurer', 'Asst. Treasurer', 'Auditor', 'PRO Internal', 'PRO External',
                    '1st Year Representative', '2nd Year Representative', '3rd Year Representative', '4th Year Representative',
                    'Marshall Head', 'Senior Multimedia', 'Multimedia Team', 'Senior Esports', 'Esports Team'
                ), id ASC
                LIMIT 10
            ");
            
            if (mysqli_num_rows($officers) > 0) {
                echo "<div class='officers-grid'>";
                while ($officer = mysqli_fetch_assoc($officers)) {
                    echo "<div class='officer-item'>";
                    echo "<div class='officer-name'>{$officer['name']}</div>";
                    echo "<div class='officer-position'>{$officer['position']}</div>";
                    echo "</div>";
                }
                echo "</div>";
                
                if ($officer_count > 10) {
                    echo "<p style='text-align: center; color: #666; margin-top: 15px; font-size: 0.9em;'>";
                    echo "<i class='fas fa-info-circle'></i> Showing 10 of {$officer_count} officers";
                    echo "</p>";
                }
            }
            
            echo "<div class='partylist-actions'>";
            echo "<button type='button' class='restore-btn' onclick=\"showRestoreModal('{$partylist['id']}', '" . htmlspecialchars($partylist['name'], ENT_QUOTES) . "', {$officer_count})\">
                    <i class='fas fa-undo'></i> Restore Partylist
                  </button>";
            echo "</div>";
            
            echo "</div>"; // partylist-card
        }
    }
    ?>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal" id="restoreModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-undo"></i> Restore Partylist</h3>
            <button type="button" class="close" onclick="closeRestoreModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to restore this partylist?</p>
            <p class="partylist-name-modal" id="partylistName"></p>
            <div class="warning-text">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Note:</strong> This will also restore <span id="officerCount">0</span> associated officer(s).
            </div>
            <p class="info-text">
                <i class="fas fa-info-circle"></i> The partylist and all its officers will be restored and will appear in the active officers list.
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

function showRestoreModal(id, name, officerCount) {
    restoreId = id;
    document.getElementById('partylistName').textContent = name;
    document.getElementById('officerCount').textContent = officerCount;
    document.getElementById('restoreModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    setTimeout(() => {
        restoreId = null;
        document.getElementById('partylistName').textContent = '';
        document.getElementById('officerCount').textContent = '0';
    }, 300);
}

function confirmRestore() {
    if (!restoreId) return;
    
    const submitBtn = document.querySelector('.confirm-restore-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';
    
    fetch('restore_partylist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `partylist_id=${encodeURIComponent(restoreId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRestoreModal();
            showSuccess(data.message || 'Partylist restored successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Failed to restore partylist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'Failed to restore partylist. Please try again.');
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

