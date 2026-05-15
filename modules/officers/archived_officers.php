<?php
include_once __DIR__ . '/../include/auth_required.php';

// Note: $conn is available from auth_required.php
if (!isset($conn)) {
    include __DIR__ . '/../../database/connect.php';
}

// Only admins can view archived officers
$user_role = $_SESSION['role_id'] ?? 0;
if ($user_role != 1) {
    $_SESSION['error'] = 'You do not have permission to view archived officers. Only admins can access this page.';
    header("Location: officers.php");
    exit;
}

$partylist_id = $_GET['partylist_id'] ?? null;
if (!$partylist_id) {
    $_SESSION['error'] = 'Partylist not specified.';
    header("Location: officers.php");
    exit;
}

$party = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM partylist WHERE id = $partylist_id"));
if (!$party) {
    $_SESSION['error'] = 'Partylist not found.';
    header("Location: officers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Officers - <?= htmlspecialchars($party['name']) ?></title>
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
        .officer-card { 
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            display: flex;
            gap: 20px;
            align-items: start;
            opacity: 0.7;
        }
        .officer-card:hover {
            opacity: 1;
            transform: translateX(5px);
            border-color: #7B0000;
        }
        .officer-info { flex: 1; }
        .officer-card h3 { 
            font-size: 1.4em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 10px;
        }
        .officer-card p { 
            font-size: 1.1em;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .archived-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            margin-left: 10px;
        }
        .officer-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .restore-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.9em;
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
            width: min(400px, 90%);
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
        .officer-name {
            font-weight: 600;
            color: #7B0000;
            margin: 10px 0;
            font-size: 1.1em;
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
            
            .officer-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .officer-info {
                width: 100%;
            }
            
            .officer-card h3 {
                font-size: 1.2em;
            }
            
            .officer-card p {
                font-size: 1em;
            }
            
            .officer-actions {
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
            
            .officer-card {
                padding: 15px;
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
        <h1><i class="fas fa-archive"></i> Archived Officers - <?= htmlspecialchars($party['name']) ?></h1>
        <a href="update_officer.php?partylist_id=<?= $partylist_id ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Manage Officers
        </a>
    </div>

    <?php
    $officers = mysqli_query($conn, "SELECT * FROM officers 
                                   WHERE partylist_id = $partylist_id AND is_archived = 1
                                   ORDER BY FIELD(position,
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
                                   ), id ASC");
    
    if (mysqli_num_rows($officers) == 0) {
        echo '<div class="empty-state">
                <i class="fas fa-archive"></i>
                <h2>No Archived Officers</h2>
                <p>There are currently no archived officers for this partylist.</p>
              </div>';
    } else {
        while ($row = mysqli_fetch_assoc($officers)) {
            echo "<div class='officer-card'>";
            
            echo "<div class='officer-info'>";
            echo "<h3>" . htmlspecialchars($row['name']) . "<span class='archived-badge'>Archived</span></h3>";
            echo "<p><strong>Position:</strong> " . htmlspecialchars($row['position']) . "</p>";
            
            echo "<div class='officer-actions'>";
            echo "<button type='button' class='restore-btn' onclick=\"showRestoreModal('{$row['id']}', '" . htmlspecialchars($row['name'], ENT_QUOTES) . "')\">
                    <i class='fas fa-undo'></i> Restore
                  </button>";
            echo "</div>";
            
            echo "</div>"; // officer-info
            echo "</div>"; // officer-card
        }
    }
    ?>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal" id="restoreModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-undo"></i> Restore Officer</h3>
            <button type="button" class="close" onclick="closeRestoreModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to restore this officer?</p>
            <p class="officer-name" id="officerName"></p>
            <p class="info-text">
                <i class="fas fa-info-circle"></i> This officer will be restored and will appear in the active officers list.
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

function showRestoreModal(id, name) {
    restoreId = id;
    document.getElementById('officerName').textContent = name;
    document.getElementById('restoreModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    setTimeout(() => {
        restoreId = null;
        document.getElementById('officerName').textContent = '';
    }, 300);
}

function confirmRestore() {
    if (!restoreId) return;
    
    const submitBtn = document.querySelector('.confirm-restore-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';
    
    fetch('restore_officer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin',
        body: `officer_id=${encodeURIComponent(restoreId)}`
    })
    .then(response => {
        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');
        
        // Try to get response text first to see what we're dealing with
        return response.text().then(text => {
            if (!response.ok) {
                // If not OK status, show the actual error
                const errorMsg = isJson ? 
                    (JSON.parse(text).message || 'Server error') : 
                    `HTTP ${response.status}: ${text.substring(0, 200)}`;
                throw new Error(errorMsg);
            }
            
            if (!isJson) {
                throw new Error('Expected JSON but got: ' + text.substring(0, 200));
            }
            
            return JSON.parse(text);
        });
    })
    .then(data => {
        if (data.success) {
            closeRestoreModal();
            showSuccess(data.message || 'Officer restored successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Failed to restore officer');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'Failed to restore officer. Please try again.');
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

