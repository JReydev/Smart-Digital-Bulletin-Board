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

// Helper function to update or insert configuration
function updateOrInsertConfig($conn, $key, $value, $description) {
    $check = "SELECT id FROM display_configuration WHERE config_key = '$key'";
    $result = mysqli_query($conn, $check);
    
    if (mysqli_num_rows($result) > 0) {
        return "UPDATE display_configuration SET config_value = '$value', updated_at = NOW() WHERE config_key = '$key'";
    } else {
        return "INSERT INTO display_configuration (config_key, config_value, description) VALUES ('$key', '$value', '$description')";
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_marquee_speed':
                $marquee_speed = mysqli_real_escape_string($conn, $_POST['marquee_speed']);
                
                // Validate marquee speed
                $valid_speeds = ['slow', 'normal', 'fast'];
                if (!in_array($marquee_speed, $valid_speeds)) {
                    $error = 'Invalid marquee speed selected.';
                    break;
                }
                
                // Check if configuration exists
                $check_query = "SELECT id FROM display_configuration WHERE config_key = 'marquee_speed'";
                $check_result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($check_result) > 0) {
                    // Update existing configuration
                    $query = "UPDATE display_configuration SET config_value = '$marquee_speed', updated_at = NOW() WHERE config_key = 'marquee_speed'";
                } else {
                    // Insert new configuration
                    $query = "INSERT INTO display_configuration (config_key, config_value) VALUES ('marquee_speed', '$marquee_speed')";
                }
                
                if (mysqli_query($conn, $query)) {
                    $message = 'Marquee speed updated successfully!';
                } else {
                    $error = 'Failed to update marquee speed: ' . mysqli_error($conn);
                }
                break;
                
            case 'update_slide_interval':
                $slide_interval = (int)$_POST['slide_interval'];
                
                // Validate slide interval (between 2 and 30 seconds)
                if ($slide_interval < 2 || $slide_interval > 30) {
                    $error = 'Slide interval must be between 2 and 30 seconds.';
                    break;
                }
                
                // Check if configuration exists
                $check_query = "SELECT id FROM display_configuration WHERE config_key = 'slide_interval'";
                $check_result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($check_result) > 0) {
                    // Update existing configuration
                    $query = "UPDATE display_configuration SET config_value = '$slide_interval', updated_at = NOW() WHERE config_key = 'slide_interval'";
                } else {
                    // Insert new configuration
                    $query = "INSERT INTO display_configuration (config_key, config_value) VALUES ('slide_interval', '$slide_interval')";
                }
                
                if (mysqli_query($conn, $query)) {
                    $message = 'Slide interval updated successfully!';
                } else {
                    $error = 'Failed to update slide interval: ' . mysqli_error($conn);
                }
                break;
                
            case 'upload_org_chart':
                if (isset($_FILES['org_chart_image']) && $_FILES['org_chart_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['org_chart_image'];
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    // Validate file type
                    if (!in_array($file['type'], $allowed_types)) {
                        $error = 'Invalid file type. Only JPG, JPEG, and PNG files are allowed.';
                        break;
                    }
                    
                    // Validate file size
                    if ($file['size'] > $max_size) {
                        $error = 'File size exceeds 5MB limit.';
                        break;
                    }
                    
                    // Get file extension
                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    // Target directory and filename
                    $target_dir = __DIR__ . '/../../images/';
                    $target_file = $target_dir . 'organization-chart.' . $file_extension;
                    
                    // Delete old organization chart files (jpg, jpeg, png)
                    $old_files = ['organization-chart.jpg', 'organization-chart.jpeg', 'organization-chart.png'];
                    foreach ($old_files as $old_file) {
                        $old_path = $target_dir . $old_file;
                        if (file_exists($old_path)) {
                            unlink($old_path);
                        }
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $target_file)) {
                        $message = 'Organization chart image uploaded successfully!';
                    } else {
                        $error = 'Failed to upload organization chart image.';
                    }
                } else {
                    $error = 'No file uploaded or upload error occurred.';
                }
                break;
                
            case 'update_mission_vision':
                $university_mission = mysqli_real_escape_string($conn, $_POST['university_mission'] ?? '');
                $university_vision = mysqli_real_escape_string($conn, $_POST['university_vision'] ?? '');
                $college_mission = mysqli_real_escape_string($conn, $_POST['college_mission'] ?? '');
                $college_vision = mysqli_real_escape_string($conn, $_POST['college_vision'] ?? '');
                
                // Validate that all fields are provided
                if (empty($university_mission) || empty($university_vision) || empty($college_mission) || empty($college_vision)) {
                    $error = 'All Mission and Vision fields are required.';
                    break;
                }
                
                // Update or insert all four values
                $queries = [
                    updateOrInsertConfig($conn, 'university_mission', $university_mission, 'University Mission Statement'),
                    updateOrInsertConfig($conn, 'university_vision', $university_vision, 'University Vision Statement'),
                    updateOrInsertConfig($conn, 'college_mission', $college_mission, 'College Mission Statement'),
                    updateOrInsertConfig($conn, 'college_vision', $college_vision, 'College Vision Statement')
                ];
                
                $all_success = true;
                foreach ($queries as $query) {
                    if (!mysqli_query($conn, $query)) {
                        $all_success = false;
                        break;
                    }
                }
                
                if ($all_success) {
                    $message = 'University and College Mission & Vision updated successfully!';
                } else {
                    $error = 'Failed to update Mission and Vision: ' . mysqli_error($conn);
                }
                break;
        }
    }
}

// Get current configuration
$config_query = "SELECT config_key, config_value FROM display_configuration WHERE config_key IN ('marquee_speed', 'slide_interval', 'university_mission', 'university_vision', 'college_mission', 'college_vision')";
$config_result = mysqli_query($conn, $config_query);

$current_marquee_speed = 'normal'; // Default value
$current_slide_interval = 5; // Default value (5 seconds)
$current_university_mission = ''; // Default value
$current_university_vision = ''; // Default value
$current_college_mission = 'The College of Computer Studies aims to provide innovative and quality instruction to the advancement of technology, intends to develop an entrepreneurial learning environment towards sustainability  and growth; and develops responsible and morally upright citizens.'; // Default value
$current_college_vision = 'We  are  committed  to  provide accessible,  responsive,  and  quality Information Technology Education (ITE) programs and to become the Institution of choice in producing competent and responsible IT professionals  who  are  sensitive to  the needs  and  demands  of the industry.'; // Default value

if ($config_result) {
    while ($row = mysqli_fetch_assoc($config_result)) {
        if ($row['config_key'] === 'marquee_speed') {
            $current_marquee_speed = $row['config_value'];
        } elseif ($row['config_key'] === 'slide_interval') {
            $current_slide_interval = (int)$row['config_value'];
        } elseif ($row['config_key'] === 'university_mission') {
            $current_university_mission = $row['config_value'];
        } elseif ($row['config_key'] === 'university_vision') {
            $current_university_vision = $row['config_value'];
        } elseif ($row['config_key'] === 'college_mission') {
            $current_college_mission = $row['config_value'];
        } elseif ($row['config_key'] === 'college_vision') {
            $current_college_vision = $row['config_value'];
        }
    }
}

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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card h2 i {
            font-size: 0.9em;
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
        
        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group .help-text {
            display: block;
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
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
        
        .config-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .config-preview h3 {
            color: #495057;
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        
        .config-preview p {
            color: #6c757d;
            font-size: 0.95em;
            margin-bottom: 8px;
        }
        
        .config-preview .preview-value {
            font-weight: 600;
            color: #7B0000;
        }
        
        .speed-indicator {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .speed-box {
            flex: 1;
            padding: 20px 15px;
            border: 3px solid #ddd;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            background: #fff;
        }
        
        .speed-box:hover {
            border-color: #7B0000;
            background: rgba(123, 0, 0, 0.05);
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(123, 0, 0, 0.2);
        }
        
        .speed-box.active {
            border-color: #7B0000;
            background: rgba(123, 0, 0, 0.1);
            box-shadow: 0 4px 12px rgba(123, 0, 0, 0.3);
        }
        
        .speed-icon {
            font-size: 2.5em;
            color: #999;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .speed-box:hover .speed-icon {
            color: #7B0000;
            transform: scale(1.1);
        }
        
        .speed-box.active .speed-icon {
            color: #7B0000;
        }
        
        .speed-box h4 {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .speed-box.active h4 {
            color: #7B0000;
        }
        
        .speed-box p {
            font-size: 0.9em;
            color: #666;
            margin: 0;
        }
        
        .speed-check {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5em;
            color: #7B0000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .speed-box.active .speed-check {
            opacity: 1;
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
            
            .form-group select,
            .form-group input[type="number"] {
                padding: 10px;
                font-size: 13px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .speed-indicator {
                flex-direction: column;
                gap: 12px;
            }
            
            .speed-box {
                padding: 15px 12px;
            }
            
            .speed-icon {
                font-size: 2em;
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
            
            .form-group select,
            .form-group input[type="number"] {
                padding: 8px;
                font-size: 12px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 12px;
            }
        }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<title>Display Configuration - Admin</title>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <span><i class="fas fa-cogs"></i> Display Configuration</span>
        </div>
        
        <!-- Message container for AJAX responses -->
        <div id="messageContainer"></div>
        
        <?php if ($message): ?>
            <div class="message success" id="initialMessage"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error" id="initialError"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Marquee Speed Configuration -->
        <div class="card">
            <h2><i class="fas fa-text-width"></i> Marquee Speed Configuration</h2>
            <form id="marqueeSpeedForm" onsubmit="return false;">
                <input type="hidden" name="action" value="update_marquee_speed">
                <input type="hidden" name="marquee_speed" id="marquee_speed" value="<?php echo $current_marquee_speed; ?>" required>
                
                <div class="form-group">
                    <label>Marquee Scroll Speed</label>
                    <small class="help-text">Click on a speed option below to select the scrolling speed for the news marquee.</small>
                </div>
                
                <div class="speed-indicator">
                    <div class="speed-box <?php echo $current_marquee_speed === 'slow' ? 'active' : ''; ?>" data-speed="slow" onclick="selectSpeed('slow')">
                        <div class="speed-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <h4>Slow</h4>
                        <p>0.5 px/frame</p>
                        <div class="speed-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="speed-box <?php echo $current_marquee_speed === 'normal' ? 'active' : ''; ?>" data-speed="normal" onclick="selectSpeed('normal')">
                        <div class="speed-icon">
                            <i class="fas fa-walking"></i>
                        </div>
                        <h4>Normal</h4>
                        <p>1.0 px/frame</p>
                        <div class="speed-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="speed-box <?php echo $current_marquee_speed === 'fast' ? 'active' : ''; ?>" data-speed="fast" onclick="selectSpeed('fast')">
                        <div class="speed-icon">
                            <i class="fas fa-running"></i>
                        </div>
                        <h4>Fast</h4>
                        <p>2.0 px/frame</p>
                        <div class="speed-check">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="config-preview">
                    <h3>Current Setting</h3>
                    <p>Marquee Speed: <span class="preview-value" id="speedPreview"><?php echo ucfirst($current_marquee_speed); ?></span></p>
                    <p><small><i class="fas fa-info-circle"></i> Click a speed option above to automatically save your selection.</small></p>
                </div>
            </form>
        </div>
        
        <!-- Slide Interval Configuration -->
        <div class="card">
            <h2><i class="fas fa-images"></i> Slide Interval Configuration</h2>
            <form id="slideIntervalForm" onsubmit="return false;">
                <input type="hidden" name="action" value="update_slide_interval">
                <div class="form-group">
                    <label for="slide_interval">Slide Transition Interval (seconds)</label>
                    <input type="number" name="slide_interval" id="slide_interval" min="2" max="30" value="<?php echo $current_slide_interval; ?>" required>
                    <small class="help-text">Set the time (in seconds) each slide is displayed before transitioning to the next. Range: 2-30 seconds.</small>
                </div>
                
                <div class="config-preview">
                    <h3>Current Setting</h3>
                    <p>Slide Interval: <span class="preview-value"><?php echo $current_slide_interval; ?> second<?php echo $current_slide_interval !== 1 ? 's' : ''; ?></span></p>
                    <p><small>Each slide (announcements, events, faculty, officers, calendar) will display for <?php echo $current_slide_interval; ?> second<?php echo $current_slide_interval !== 1 ? 's' : ''; ?> before moving to the next.</small></p>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Slide Interval</button>
            </form>
        </div>
        
        <!-- Organization Chart Image Upload -->
        <div class="card">
            <h2><i class="fas fa-sitemap"></i> Organization Chart Image</h2>
            <form id="orgChartForm" enctype="multipart/form-data" onsubmit="return false;">
                <input type="hidden" name="action" value="upload_org_chart">
                <div class="form-group">
                    <label for="org_chart_image">Upload Organization Chart Image</label>
                    <input type="file" name="org_chart_image" id="org_chart_image" accept=".jpg,.jpeg,.png" required style="padding: 10px; border: 2px dashed #7B0000; border-radius: 5px; background: #f9f9f9;">
                    <small class="help-text">Upload a new organization chart image (JPG, JPEG, or PNG). Maximum file size: 5MB. The image will be displayed in the bulletin board's organization section.</small>
                </div>
                
                <?php
                // Check if organization chart exists
                $org_chart_exists = false;
                $org_chart_file = '';
                $possible_files = ['organization-chart.jpg', 'organization-chart.jpeg', 'organization-chart.png'];
                foreach ($possible_files as $file) {
                    if (file_exists(__DIR__ . '/../../images/' . $file)) {
                        $org_chart_exists = true;
                        $org_chart_file = $file;
                        break;
                    }
                }
                ?>
                
                <?php if ($org_chart_exists): ?>
                <div class="config-preview">
                    <h3>Current Organization Chart</h3>
                    <img src="../../images/<?php echo $org_chart_file; ?>?v=<?php echo time(); ?>" alt="Current Organization Chart" style="max-width: 100%; max-height: 300px; border: 2px solid #ddd; border-radius: 5px; margin-top: 10px;">
                    <p style="margin-top: 10px;"><small>Current file: <span class="preview-value"><?php echo $org_chart_file; ?></span></small></p>
                </div>
                <?php else: ?>
                <div class="config-preview">
                    <p style="color: #666;"><i class="fas fa-info-circle"></i> No organization chart image uploaded yet.</p>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Organization Chart</button>
            </form>
        </div>
        
        <!-- Mission & Vision Configuration -->
        <div class="card">
            <h2><i class="fas fa-graduation-cap"></i> University & College Mission & Vision</h2>
            <form id="missionVisionForm" onsubmit="return false;">
                <input type="hidden" name="action" value="update_mission_vision">
                
                <h3 style="color: #7B0000; margin-top: 20px; margin-bottom: 15px; font-size: 1.2em; border-bottom: 2px solid #7B0000; padding-bottom: 8px;">University Mission & Vision</h3>
                
                <div class="form-group">
                    <label for="university_mission">University Mission Statement</label>
                    <textarea name="university_mission" id="university_mission" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($current_university_mission); ?></textarea>
                    <small class="help-text">Enter the university's mission statement. This will be displayed on the bulletin board when viewing the Officers section.</small>
                </div>
                
                <div class="form-group">
                    <label for="university_vision">University Vision Statement</label>
                    <textarea name="university_vision" id="university_vision" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($current_university_vision); ?></textarea>
                    <small class="help-text">Enter the university's vision statement. This will be displayed on the bulletin board when viewing the Officers section.</small>
                </div>
                
                <h3 style="color: #7B0000; margin-top: 30px; margin-bottom: 15px; font-size: 1.2em; border-bottom: 2px solid #7B0000; padding-bottom: 8px;">College Mission & Vision</h3>
                
                <div class="form-group">
                    <label for="college_mission">College Mission Statement</label>
                    <textarea name="college_mission" id="college_mission" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($current_college_mission); ?></textarea>
                    <small class="help-text">Enter the college's mission statement. This will be displayed on the bulletin board when viewing the Officers section.</small>
                </div>
                
                <div class="form-group">
                    <label for="college_vision">College Vision Statement</label>
                    <textarea name="college_vision" id="college_vision" rows="5" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($current_college_vision); ?></textarea>
                    <small class="help-text">Enter the college's vision statement. This will be displayed on the bulletin board when viewing the Officers section.</small>
                </div>
                
                <div class="config-preview">
                    <h3>Preview</h3>
                    <p><strong>University Mission:</strong></p>
                    <p class="preview-value" id="universityMissionPreview" style="white-space: pre-wrap; word-wrap: break-word; margin-bottom: 15px; padding: 10px; background: #fff; border-left: 3px solid #7B0000; border-radius: 5px;"><?php echo htmlspecialchars($current_university_mission); ?></p>
                    <p><strong>University Vision:</strong></p>
                    <p class="preview-value" id="universityVisionPreview" style="white-space: pre-wrap; word-wrap: break-word; margin-bottom: 15px; padding: 10px; background: #fff; border-left: 3px solid #006633; border-radius: 5px;"><?php echo htmlspecialchars($current_university_vision); ?></p>
                    <p><strong>College Mission:</strong></p>
                    <p class="preview-value" id="collegeMissionPreview" style="white-space: pre-wrap; word-wrap: break-word; margin-bottom: 15px; padding: 10px; background: #fff; border-left: 3px solid #7B0000; border-radius: 5px;"><?php echo htmlspecialchars($current_college_mission); ?></p>
                    <p><strong>College Vision:</strong></p>
                    <p class="preview-value" id="collegeVisionPreview" style="white-space: pre-wrap; word-wrap: break-word; margin-bottom: 15px; padding: 10px; background: #fff; border-left: 3px solid #006633; border-radius: 5px;"><?php echo htmlspecialchars($current_college_vision); ?></p>
                    <p><small><i class="fas fa-info-circle"></i> These statements will appear in the right panel when the Officers section is displayed on the bulletin board. Order: University Mission & Vision first, then College Mission & Vision.</small></p>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Mission & Vision</button>
            </form>
        </div>
</div>

<script>
// Show message function
function showMessage(message, type = 'success') {
    const container = document.getElementById('messageContainer');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.opacity = '0';
    messageDiv.style.transition = 'opacity 0.3s ease';
    
    container.appendChild(messageDiv);
    
    // Fade in
    setTimeout(() => {
        messageDiv.style.opacity = '1';
    }, 10);
    
    // Auto-dismiss after 4 seconds
    setTimeout(() => {
        messageDiv.style.opacity = '0';
        setTimeout(() => {
            messageDiv.remove();
        }, 300);
    }, 4000);
}

// Auto-dismiss initial messages
document.addEventListener('DOMContentLoaded', function() {
    const initialMessage = document.getElementById('initialMessage');
    const initialError = document.getElementById('initialError');
    
    if (initialMessage) {
        setTimeout(() => {
            initialMessage.style.opacity = '0';
            initialMessage.style.transition = 'opacity 0.3s ease';
            setTimeout(() => initialMessage.remove(), 300);
        }, 4000);
    }
    
    if (initialError) {
        setTimeout(() => {
            initialError.style.opacity = '0';
            initialError.style.transition = 'opacity 0.3s ease';
            setTimeout(() => initialError.remove(), 300);
        }, 4000);
    }
});

// Handle speed box selection
function selectSpeed(speed) {
    // Update hidden input value
    document.getElementById('marquee_speed').value = speed;
    
    // Remove active class from all boxes
    document.querySelectorAll('.speed-box').forEach(box => {
        box.classList.remove('active');
    });
    
    // Add active class to selected box
    const selectedBox = document.querySelector(`.speed-box[data-speed="${speed}"]`);
    if (selectedBox) {
        selectedBox.classList.add('active');
    }
    
    // Update preview text
    const previewValue = document.getElementById('speedPreview');
    if (previewValue) {
        previewValue.textContent = speed.charAt(0).toUpperCase() + speed.slice(1);
    }
    
    // Submit form via AJAX
    submitMarqueeSpeedForm();
}

// Submit marquee speed form via AJAX
function submitMarqueeSpeedForm() {
    const form = document.getElementById('marqueeSpeedForm');
    const formData = new FormData(form);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Parse response to check for success/error
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const successMsg = doc.querySelector('.message.success');
        const errorMsg = doc.querySelector('.message.error');
        
        if (successMsg) {
            showMessage(successMsg.textContent, 'success');
        } else if (errorMsg) {
            showMessage(errorMsg.textContent, 'error');
        }
    })
    .catch(error => {
        showMessage('An error occurred while updating marquee speed.', 'error');
        console.error('Error:', error);
    });
}

// Submit slide interval form via AJAX
document.getElementById('slideIntervalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const successMsg = doc.querySelector('.message.success');
        const errorMsg = doc.querySelector('.message.error');
        
        if (successMsg) {
            showMessage(successMsg.textContent, 'success');
        } else if (errorMsg) {
            showMessage(errorMsg.textContent, 'error');
        }
        
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    })
    .catch(error => {
        showMessage('An error occurred while updating slide interval.', 'error');
        console.error('Error:', error);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Update preview when slide interval changes
document.getElementById('slide_interval').addEventListener('input', function() {
    const interval = this.value;
    const previewValue = this.closest('.card').querySelector('.preview-value');
    const previewSmall = this.closest('.card').querySelector('.config-preview small');
    
    if (previewValue && previewSmall) {
        const plural = interval !== '1' ? 's' : '';
        previewValue.textContent = interval + ' second' + plural;
        previewSmall.textContent = `Each slide (announcements, events, faculty, officers, calendar) will display for ${interval} second${plural} before moving to the next.`;
    }
});

// Update preview when mission or vision changes
document.getElementById('university_mission').addEventListener('input', function() {
    const mission = this.value;
    const preview = document.getElementById('universityMissionPreview');
    if (preview) {
        preview.textContent = mission;
    }
});

document.getElementById('university_vision').addEventListener('input', function() {
    const vision = this.value;
    const preview = document.getElementById('universityVisionPreview');
    if (preview) {
        preview.textContent = vision;
    }
});

document.getElementById('college_mission').addEventListener('input', function() {
    const mission = this.value;
    const preview = document.getElementById('collegeMissionPreview');
    if (preview) {
        preview.textContent = mission;
    }
});

document.getElementById('college_vision').addEventListener('input', function() {
    const vision = this.value;
    const preview = document.getElementById('collegeVisionPreview');
    if (preview) {
        preview.textContent = vision;
    }
});

// Submit mission and vision form via AJAX
document.getElementById('missionVisionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const successMsg = doc.querySelector('.message.success');
        const errorMsg = doc.querySelector('.message.error');
        
        if (successMsg) {
            showMessage(successMsg.textContent, 'success');
        } else if (errorMsg) {
            showMessage(errorMsg.textContent, 'error');
        }
        
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    })
    .catch(error => {
        showMessage('An error occurred while updating Mission & Vision.', 'error');
        console.error('Error:', error);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Submit organization chart form via AJAX
document.getElementById('orgChartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('org_chart_image');
    if (!fileInput.files || !fileInput.files[0]) {
        showMessage('Please select a file to upload.', 'error');
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const successMsg = doc.querySelector('.message.success');
        const errorMsg = doc.querySelector('.message.error');
        
        if (successMsg) {
            showMessage(successMsg.textContent, 'success');
            // Reload the page after 1 second to show the new image
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else if (errorMsg) {
            showMessage(errorMsg.textContent, 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showMessage('An error occurred while uploading the image.', 'error');
        console.error('Error:', error);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Add keyboard accessibility
document.querySelectorAll('.speed-box').forEach(box => {
    // Make boxes keyboard accessible
    box.setAttribute('tabindex', '0');
    box.setAttribute('role', 'radio');
    
    // Handle keyboard events
    box.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const speed = this.getAttribute('data-speed');
            selectSpeed(speed);
        }
    });
    
    // Update aria-checked based on active state
    const isActive = box.classList.contains('active');
    box.setAttribute('aria-checked', isActive);
});

// Update aria-checked when selection changes
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'class') {
            const box = mutation.target;
            const isActive = box.classList.contains('active');
            box.setAttribute('aria-checked', isActive);
        }
    });
});

document.querySelectorAll('.speed-box').forEach(box => {
    observer.observe(box, { attributes: true });
});
</script>
</body>
</html>

