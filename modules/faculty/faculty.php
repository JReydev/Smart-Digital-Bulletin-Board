<?php
include_once __DIR__ . '/../include/auth_required.php';


include __DIR__ . '/../../database/connect.php';
// DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty</title>
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
                /* Header styles moved to header.php */
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
        .faculty-card { 
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            display: flex;
            gap: 20px;
            align-items: start;
        }
        .faculty-card:hover {
            background: #f8f9fa;
            transform: translateX(5px);
            border-color: #7B0000;
        }
        .faculty-image {
            width: 160px;
            height: 160px;
            object-fit: contain;
            border-radius: 12px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            max-width: 100%;
            max-height: 100%;
        }
        .faculty-info { flex: 1; }
        .faculty-card h3 { 
            font-size: 1.4em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 10px;
        }
        .faculty-card p { 
            font-size: 1.1em;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .buttons { 
            text-align: right;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        .add-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #7B0000;
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid #7B0000;
            padding: 12px 24px;
            font-size: 1.1em;
            cursor: pointer;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            justify-content: center;
            min-height: 44px; /* Touch target size */
        }
        .add-button:hover { 
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .add-button.archive-btn {
            background: rgba(128, 128, 128, 0.1);
            border-color: rgba(128, 128, 128, 0.2);
            color: #666;
        }
        .add-button.archive-btn:hover {
            background: rgba(128, 128, 128, 0.2);
        }
        .faculty-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .edit-btn, .delete-btn {
            padding: 10px 18px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7B0000;
            text-decoration: none;
            transition: all 0.3s ease;
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid #7B0000;
            min-height: 44px; /* Touch target size */
            min-width: 44px;
            justify-content: center;
        }
        .edit-btn:hover, .delete-btn:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .edit-btn i, .delete-btn i {
            font-size: 1em;
            color: #7B0000;
        }

        /* Delete Modal Styles */
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
            position: relative;
        }
        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        .modal-header {
            padding: 20px 20px 0;
            color: #7B0000;
            font-size: 1.4em;
            font-weight: 600;
        }
        .modal-body {
            padding: 20px;
            color: #333;
            text-align: center;
        }
        .modal-footer {
            padding: 20px;
            display: flex;
            justify-content: center;
            gap: 12px;
            border-top: 1px solid #e0e0e0;
        }
        .modal-footer button {
            padding: 8px 16px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .modal-footer .cancel-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
        }
        .modal-footer .cancel-btn:hover {
            background: rgba(123, 0, 0, 0.2);
        }
        .modal-footer .confirm-delete-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
        }
        .modal-footer .confirm-delete-btn:hover {
            background: rgba(123, 0, 0, 0.2);
        }
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: #666;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: auto;
            margin: 0;
        }
        .close:hover {
            color: #333;
        }
        .faculty-name {
            font-weight: 600;
            margin: 10px 0;
            color: #333;
        }
        .faculty-positions {
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .faculty-description {
            margin-bottom: 12px;
        }
        .description-text {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            border-left: 4px solid #006633;
            color: #555;
            font-style: italic;
            margin: 0;
            font-size: 0.95em;
            line-height: 1.5;
        }
        .description-text i {
            color: #006633;
            margin-right: 8px;
        }
        .position-badge {
            background: linear-gradient(135deg, #006633, #008844);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(30, 58, 138, 0.2);
            transition: all 0.3s ease;
        }
        .position-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
        }
        .position-badge i {
            font-size: 0.8em;
        }
        .faculty-specializations {
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .specialization-badge {
            background: linear-gradient(135deg, #7B0000, #A00000);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(123, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        .specialization-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(123, 0, 0, 0.3);
        }
        .specialization-badge i {
            font-size: 0.8em;
        }
        
        /* User Status Styles */
        .user-status {
            margin-bottom: 12px;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .user-status.user-active {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }
        .user-status.user-inactive {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }
        .user-status.no-account {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
        }
        .user-status i {
            font-size: 0.8em;
        }
        
        /* User Management Button Styles */
        .create-user-btn, .activate-btn, .deactivate-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .create-user-btn {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: 1px solid #17a2b8;
        }
        .create-user-btn:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            transform: translateY(-2px);
        }
        .activate-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: 1px solid #28a745;
        }
        .activate-btn:hover {
            background: linear-gradient(135deg, #20c997, #1e7e34);
            transform: translateY(-2px);
        }
        .deactivate-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: 1px solid #dc3545;
        }
        .deactivate-btn:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
        }
        .create-user-btn i, .activate-btn i, .deactivate-btn i {
            font-size: 1em;
        }
        @media (max-width: 768px) {
            .header {
                padding: 12px 15px;
                font-size: 1.6em;
            }
            .container {
                width: 95%;
                padding: 15px;
                margin: 15px auto;
            }
            .faculty-card {
                padding: 15px;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .faculty-image {
                width: 100%;
                max-width: 300px;
                height: auto;
                max-height: 300px;
                aspect-ratio: 1 / 1;
                object-fit: contain;
                margin-right: 0;
                margin-bottom: 15px;
            }
            .faculty-positions {
                justify-content: center;
            }
            .faculty-specializations {
                justify-content: center;
            }
            .faculty-actions {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .container {
                width: 98%;
                padding: 12px;
                margin: 10px auto;
            }
            .buttons {
                position: static;
                margin-bottom: 15px;
                gap: 8px;
            }
            .add-button {
                padding: 8px 12px;
                font-size: 0.8em;
                min-width: 120px;
                max-width: 160px;
                justify-content: center;
            }
            .faculty-card {
                padding: 12px;
            }
            .faculty-image {
                max-width: 250px;
                max-height: 250px;
                width: 100%;
                height: auto;
                aspect-ratio: 1 / 1;
                object-fit: contain;
                margin-bottom: 12px;
            }
            .faculty-card h3 {
                font-size: 1.2em;
            }
            .faculty-card p {
                font-size: 1em;
            }
            .position-badge, .specialization-badge {
                font-size: 0.8em;
                padding: 3px 8px;
            }
            .faculty-actions {
                flex-direction: column;
                gap: 10px;
            }
            .edit-btn, .delete-btn {
                width: 100%;
                justify-content: center;
                min-height: 44px; /* Touch target size */
                padding: 12px 20px;
                font-size: 0.95em;
            }
        }

        @media (max-width: 360px) {
            .container {
                padding: 10px;
                margin: 8px auto;
            }
            .faculty-card {
                padding: 10px;
            }
            .faculty-image {
                max-width: 200px;
                max-height: 200px;
                width: 100%;
                height: auto;
                aspect-ratio: 1 / 1;
                object-fit: contain;
                margin-bottom: 10px;
            }
            .faculty-card h3 {
                font-size: 1.1em;
            }
            .faculty-card p {
                font-size: 0.9em;
            }
            .position-badge, .specialization-badge {
                font-size: 0.75em;
                padding: 2px 6px;
            }
        }

        /* Landscape Mobile Orientation */
        @media (max-width: 768px) and (orientation: landscape) {
            .faculty-card {
                flex-direction: row;
                text-align: left;
            }
            .faculty-image {
                width: 120px;
                height: 120px;
                max-width: 120px;
                max-height: 120px;
                object-fit: contain;
                margin-right: 15px;
                margin-bottom: 0;
            }
            .faculty-positions, .faculty-specializations {
                justify-content: flex-start;
            }
            .faculty-actions {
                justify-content: flex-start;
                flex-direction: row;
            }
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>
<div class="header">
    <span>Faculty Members</span>
</div>

<div class="container">
    <?php
    // Check if current user is admin
    $current_user_id = $_SESSION['user_id'] ?? null;
    $is_admin = false;
    if ($current_user_id) {
        $user_role = $_SESSION['role_id'] ?? 0;
        $is_admin = ($user_role == 1); // 1 = Admin role
    }
    ?>
    
    <?php
    // Display success/error messages
    if (isset($_SESSION['success'])) {
        echo '<div style="padding: 15px; margin-bottom: 20px; background: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; border-radius: 8px; color: #28a745;">
                <i class="fas fa-check-circle"></i> ' . $_SESSION['success'] . '
              </div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div style="padding: 15px; margin-bottom: 20px; background: rgba(220, 53, 69, 0.1); border: 1px solid #dc3545; border-radius: 8px; color: #dc3545;">
                <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error'] . '
              </div>';
        unset($_SESSION['error']);
    }
    ?>
    
    <div class="buttons">
        <a href="add_faculty.php" class="add-button"><i class="fas fa-plus"></i> Add New Faculty</a>
        <?php if ($is_admin): ?>
        <a href="archived_faculty.php" class="add-button archive-btn">
            <i class="fas fa-archive"></i> View Archived
        </a>
        <?php endif; ?>
    </div>

    <?php
    $faculty = mysqli_query($conn, "SELECT f.*, m.file_path, u.username, u.is_active as user_active, u.profile_picture
                                   FROM faculty f 
                                   LEFT JOIN multimedia_content m ON f.media_id = m.id 
                                   LEFT JOIN users u ON f.user_id = u.id
                                   WHERE f.type = 'faculty' AND f.is_archived = 0
                                   ORDER BY 
                                   CASE 
                                       WHEN f.position LIKE '%College Dean%' THEN 1
                                       WHEN f.position LIKE '%Program Head%' THEN 2
                                       WHEN f.position LIKE '%Coordinator%' THEN 3
                                       WHEN f.position LIKE '%SHS Faculty%' AND 
                                            (f.position LIKE '%Coordinator%' OR 
                                             f.position LIKE '%Adviser%' OR 
                                             f.position LIKE '%Head%') THEN 4
                                       WHEN f.position LIKE '%SHS Faculty%' THEN 5
                                       WHEN f.position LIKE '%CCS Faculty%' AND 
                                            (f.position LIKE '%Coordinator%' OR 
                                             f.position LIKE '%Adviser%' OR 
                                             f.position LIKE '%Head%') THEN 6
                                       WHEN f.position LIKE '%CCS Faculty%' THEN 7
                                       ELSE 8
                                   END,
                                   f.lname ASC, f.fname ASC, f.mname ASC");
    while ($row = mysqli_fetch_assoc($faculty)) {
        // Generate display name from components
        $display_name = trim(($row['prefix'] ? $row['prefix'] . ' ' : '') . 
                           $row['fname'] . 
                           ($row['mname'] ? ' ' . $row['mname'] : '') . 
                           ' ' . $row['lname'] . 
                           ($row['suffix'] ? ', ' . $row['suffix'] : ''));
        
        echo "<div class='faculty-card'>";
        
        // Determine which image to display - prioritize user profile picture
        $image_to_display = '';
        if (!empty($row['profile_picture']) && $row['profile_picture'] !== 'default-avatar.png') {
            // Use user's profile picture if it exists and is not default
            $image_to_display = '../../multimedia/profile/' . $row['profile_picture'];
        } elseif (!empty($row['file_path'])) {
            // Fall back to faculty multimedia content
            $image_to_display = '../../' . $row['file_path'];
        }
        
        if (!empty($image_to_display)) {
            echo "<img src='{$image_to_display}' alt='{$display_name}' class='faculty-image'>";
        } else {
            echo "<div class='faculty-image'></div>";
        }
        echo "<div class='faculty-info'>";
        echo "<h3>{$display_name}</h3>";
        
        // Display positions as badges
        if (!empty($row['position'])) {
            $positions = array_map('trim', explode(',', $row['position']));
            echo "<div class='faculty-positions'>";
            foreach ($positions as $position) {
                if (!empty($position)) {
                    echo "<span class='position-badge'><i class='fas fa-briefcase'></i> {$position}</span>";
                }
            }
            echo "</div>";
        }
        
        // Display description if available
        if (!empty($row['description'])) {
            echo "<div class='faculty-description'>";
            echo "<p class='description-text'><i class='fas fa-info-circle'></i> {$row['description']}</p>";
            echo "</div>";
        }
        
        // Display specializations as badges
        if (!empty($row['specialization'])) {
            $specializations = array_map('trim', explode(',', $row['specialization']));
            echo "<div class='faculty-specializations'>";
            foreach ($specializations as $spec) {
                if (!empty($spec)) {
                    echo "<span class='specialization-badge'><i class='fas fa-graduation-cap'></i> {$spec}</span>";
                }
            }
            echo "</div>";
        }
        
        // Display user account status
        $user_status = '';
        if (!empty($row['username'])) {
            $status_class = $row['user_active'] ? 'user-active' : 'user-inactive';
            $status_text = $row['user_active'] ? 'Active User' : 'Inactive User';
            $user_status = "<div class='user-status {$status_class}'><i class='fas fa-user'></i> {$status_text} ({$row['username']})</div>";
        } else {
            $user_status = "<div class='user-status no-account'><i class='fas fa-user-times'></i> No User Account</div>";
        }
        echo $user_status;
        echo "<div class='faculty-actions'>";
        
        // Check if current user is faculty and viewing their own profile
        $current_user_id = $_SESSION['user_id'] ?? null;
        $is_own_profile = ($current_user_id && $row['user_id'] == $current_user_id);
        
        if ($is_own_profile) {
            // Show "My Profile" link for faculty viewing their own profile
            echo "<a href='faculty_profile.php' class='edit-btn'><i class='fas fa-user-edit'></i> My Profile</a>";
        } else {
            // Show admin edit button for other faculty
            echo "<a href='edit_faculty.php?id={$row['id']}' class='edit-btn'><i class='fas fa-edit'></i> Edit</a>";
        }
        
        // Add user account management buttons (only for admins)
        $is_admin = false;
        if ($current_user_id) {
            $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $is_admin = ($user && $user['role_id'] == 1); // 1 = Admin role
        }
        
        if ($is_admin && !$is_own_profile) {
            if (empty($row['username'])) {
                echo "<a href='create_user_account.php?id={$row['id']}' class='create-user-btn'><i class='fas fa-user-plus'></i> Create Account</a>";
            } else {
                $toggle_text = $row['user_active'] ? 'Deactivate' : 'Activate';
                $toggle_class = $row['user_active'] ? 'deactivate-btn' : 'activate-btn';
                echo "<a href='toggle_user_status.php?id={$row['id']}' class='{$toggle_class}'><i class='fas fa-user-{$toggle_text}'></i> {$toggle_text}</a>";
            }
        }
        
        // Show archive button only for admins or the faculty member themselves
        if ($is_admin || $row['user_id'] == $_SESSION['user_id']) {
            echo "<button type='button' class='delete-btn' onclick=\"showArchiveModal('{$row['id']}', '" . htmlspecialchars($display_name, ENT_QUOTES) . "')\">
                        <i class='fas fa-archive'></i> Archive
                    </button>";
        }
        
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    ?>
</div>

<!-- Archive Confirmation Modal -->
<div class="modal" id="archiveModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-archive"></i> Archive Faculty Member</h3>
            <button type="button" class="close" onclick="closeArchiveModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to archive this faculty member?</p>
            <p class="faculty-name" id="facultyName"></p>
            <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.9em; margin-top: 10px;">
                <i class="fas fa-info-circle"></i> This faculty profile will be moved to archives and can be restored later.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="cancel-btn" onclick="closeArchiveModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="confirm-delete-btn" onclick="confirmArchive()">
                <i class="fas fa-archive"></i> Archive
            </button>
        </div>
    </div>
</div>

<script>
let archiveId = null;

function showArchiveModal(id, name) {
    archiveId = id;
    document.getElementById('facultyName').textContent = name;
    document.getElementById('archiveModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeArchiveModal() {
    document.getElementById('archiveModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    setTimeout(() => {
        archiveId = null;
        document.getElementById('facultyName').textContent = '';
    }, 300);
}

function confirmArchive() {
    if (!archiveId) return;
    
    const submitBtn = document.querySelector('.confirm-delete-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
    
    fetch('archive_faculty.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `faculty_id=${encodeURIComponent(archiveId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeArchiveModal();
            showSuccess(data.message || 'Faculty member archived successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Failed to archive faculty member');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'Failed to archive faculty member. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-archive"></i> Archive';
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
    const modal = document.getElementById('archiveModal');
    if (event.target === modal) {
        closeArchiveModal();
    }
}

// Add keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeArchiveModal();
    }
});
</script>

</body>
</html>
