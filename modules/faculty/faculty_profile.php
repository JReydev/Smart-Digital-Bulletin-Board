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

// Get faculty information
$stmt = $conn->prepare("SELECT f.*, m.file_path, u.profile_picture FROM faculty f LEFT JOIN multimedia_content m ON f.media_id = m.id LEFT JOIN users u ON f.user_id = u.id WHERE f.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();

if (!$faculty) {
    $_SESSION['error'] = 'Faculty profile not found';
    header('Location: /');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefix = !empty($_POST['prefix']) ? $_POST['prefix'] : NULL;
    $fname = $_POST['fname'];
    $mname = !empty($_POST['mname']) ? $_POST['mname'] : NULL;
    $lname = $_POST['lname'];
    $suffix = !empty($_POST['suffix']) ? $_POST['suffix'] : NULL;
    
    // Generate full name for backward compatibility
    $name = trim(($prefix ? $prefix . ' ' : '') . 
                 $fname . 
                 ($mname ? ' ' . $mname : '') . 
                 ' ' . $lname . 
                 ($suffix ? ', ' . $suffix : ''));
    
    $position = $faculty['position']; // Keep existing position - not editable by faculty
    $description = !empty($_POST['description']) ? $_POST['description'] : NULL;
    $specialization = !empty($_POST['specialization']) ? $_POST['specialization'] : NULL;
    
    // Keep existing media_id - profile images are managed through Account Settings
    $media_id = $faculty['media_id'];
    
    // Update faculty record
    $stmt = $conn->prepare("UPDATE faculty SET prefix = ?, fname = ?, mname = ?, lname = ?, suffix = ?, name = ?, position = ?, description = ?, specialization = ?, media_id = ? WHERE user_id = ?");
    $stmt->bind_param("sssssssssii", $prefix, $fname, $mname, $lname, $suffix, $name, $position, $description, $specialization, $media_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Profile updated successfully';
        header("Location: faculty_profile.php");
        exit;
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Faculty Profile</title>
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
        input[type="text"], textarea {
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
        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }
        .current-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 12px;
            margin-top: 10px;
            object-fit: cover;
            border: 1px solid #e0e0e0;
        }
        
        .image-info {
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #7B0000;
        }
        
        .image-info .help-text {
            margin: 0;
            font-size: 0.9em;
            color: #666;
        }
        
        .image-info .help-text a {
            color: #7B0000;
            text-decoration: none;
            font-weight: 500;
        }
        
        .image-info .help-text a:hover {
            text-decoration: underline;
        }
        
        .position-display {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #7B0000;
        }
        
        .position-text {
            font-size: 1.1em;
            font-weight: 500;
            color: #7B0000;
            margin: 0 0 10px 0;
            padding: 8px 12px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .position-display .help-text {
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
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .profile-header h1 {
            color: #7B0000;
            margin-bottom: 10px;
        }
        .profile-header p {
            color: #666;
        }
        
        /* Profile Preview Styles */
        .profile-preview {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #7B0000;
        }
        .profile-preview h3 {
            color: #7B0000;
            margin-bottom: 8px;
            font-size: 1.1em;
        }
        .profile-preview p {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }
        
        .profile-preview-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }
        
        .faculty-card-preview {
            display: flex;
            gap: 20px;
            align-items: start;
            padding: 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }
        
        .faculty-image-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
        }
        
        .faculty-info-preview {
            flex: 1;
        }
        
        .faculty-info-preview h3 {
            font-size: 1.2em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 10px;
        }
        
        .faculty-positions-preview,
        .faculty-specializations-preview {
            margin-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .position-badge-preview,
        .specialization-badge-preview {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .position-badge-preview {
            background: linear-gradient(135deg, #006633, #008844);
            color: white;
        }
        
        .specialization-badge-preview {
            background: linear-gradient(135deg, #7B0000, #A00000);
            color: white;
        }
        
        .faculty-description-preview {
            margin-bottom: 10px;
        }
        
        .description-text-preview {
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
        
        .description-text-preview i {
            color: #006633;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>

<div class="container">
    <div class="profile-header">
        <h1><i class="fas fa-user-graduate"></i> My Faculty Profile</h1>
        <p>Manage your faculty information and profile</p>
        <div class="profile-preview">
            <h3><i class="fas fa-eye"></i> Public Profile Preview</h3>
            <p>This is how your profile appears on the faculty page:</p>
        </div>
    </div>

    <!-- Profile Preview Section -->
    <div class="profile-preview-section">
        <div class="faculty-card-preview">
            <?php 
            // Determine which image to display - prioritize user profile picture
            $image_to_display = '';
            if (!empty($faculty['profile_picture']) && $faculty['profile_picture'] !== 'default-avatar.png') {
                // Use user's profile picture if it exists and is not default
                $image_to_display = '../../multimedia/profile/' . $faculty['profile_picture'];
            } elseif (!empty($faculty['file_path'])) {
                // Fall back to faculty multimedia content
                $image_to_display = '../../' . $faculty['file_path'];
            }
            
            if (!empty($image_to_display)): 
            ?>
                <img src="<?php echo htmlspecialchars($image_to_display); ?>" alt="Profile Preview" class="faculty-image-preview">
            <?php else: ?>
                <div class="faculty-image-preview"></div>
            <?php endif; ?>
            <div class="faculty-info-preview">
                <h3><?php echo htmlspecialchars($faculty['name']); ?></h3>
                
                <?php if (!empty($faculty['position'])): ?>
                    <div class="faculty-positions-preview">
                        <?php 
                        $positions = array_map('trim', explode(',', $faculty['position']));
                        foreach ($positions as $position): 
                            if (!empty($position)):
                        ?>
                            <span class="position-badge-preview"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($position); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($faculty['description'])): ?>
                    <div class="faculty-description-preview">
                        <p class="description-text-preview"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($faculty['description']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($faculty['specialization'])): ?>
                    <div class="faculty-specializations-preview">
                        <?php 
                        $specializations = array_map('trim', explode(',', $faculty['specialization']));
                        foreach ($specializations as $spec): 
                            if (!empty($spec)):
                        ?>
                            <span class="specialization-badge-preview"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($spec); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="prefix"><i class="fas fa-user-tie"></i> Prefix (Optional)</label>
            <input type="text" id="prefix" name="prefix" value="<?php echo htmlspecialchars($faculty['prefix'] ?? ''); ?>" placeholder="e.g., Dr., Prof., Engr., Mr., Mrs., Ms.">
        </div>

        <div class="form-group" style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label for="fname"><i class="fas fa-user"></i> First Name</label>
                <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($faculty['fname']); ?>" required>
            </div>
            <div style="flex: 1;">
                <label for="mname"><i class="fas fa-user"></i> Middle Name (Optional)</label>
                <input type="text" id="mname" name="mname" value="<?php echo htmlspecialchars($faculty['mname'] ?? ''); ?>" placeholder="Middle name or initial">
            </div>
            <div style="flex: 1;">
                <label for="lname"><i class="fas fa-user"></i> Last Name</label>
                <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($faculty['lname']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="suffix"><i class="fas fa-graduation-cap"></i> Suffix (Optional)</label>
            <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($faculty['suffix'] ?? ''); ?>" placeholder="e.g., Jr., Sr., III, MIT, DIT, PhD">
        </div>

        <div class="form-group">
            <label><i class="fas fa-briefcase"></i> Position</label>
            <div class="position-display">
                <p class="position-text"><?php echo htmlspecialchars($faculty['position']); ?></p>
                <p class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Your position is managed by administrators and cannot be changed here.
                </p>
            </div>
        </div>

        <div class="form-group">
            <label for="description"><i class="fas fa-info-circle"></i> Description (Optional)</label>
            <textarea id="description" name="description" placeholder="Add a brief description about yourself..."><?php echo htmlspecialchars($faculty['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="specialization"><i class="fas fa-graduation-cap"></i> Specializations (Optional)</label>
            <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($faculty['specialization'] ?? ''); ?>" placeholder="e.g., Information Technology, Mathematics, Research">
        </div>

        <div class="form-group">
            <label><i class="fas fa-image"></i> Profile Image</label>
            <div class="image-info">
                <p class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Your profile image is managed through your <a href="../account/account.php" style="color: #7B0000;">Account Settings</a>. 
                    Changes made there will automatically update your faculty profile photo.
                </p>
            </div>
            <?php 
            // Show current profile picture
            $current_image = '';
            if (!empty($faculty['profile_picture']) && $faculty['profile_picture'] !== 'default-avatar.png') {
                $current_image = '../../multimedia/profile/' . $faculty['profile_picture'];
            } elseif (!empty($faculty['file_path'])) {
                $current_image = '../../' . $faculty['file_path'];
            }
            
            if (!empty($current_image)): 
            ?>
                <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Current Profile" class="current-image">
            <?php endif; ?>
        </div>

        <div class="buttons">
            <button type="submit"><i class="fas fa-save"></i> Update Profile</button>
        </div>
    </form>
</div>

</body>
</html>
