<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';
$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT f.*, m.file_path 
                              FROM faculty f 
                              LEFT JOIN multimedia_content m ON f.media_id = m.id 
                              WHERE f.id = $id");
$row = mysqli_fetch_assoc($result);

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
    
    $position = $_POST['position'];
    $description = !empty($_POST['description']) ? $_POST['description'] : NULL;
    $specialization = !empty($_POST['specialization']) ? $_POST['specialization'] : NULL;
    $user_id = 1; // or from session
    
    // Handle image upload
    $media_id = $row['media_id']; // Keep existing media_id by default
    if (isset($_FILES['faculty_image']) && $_FILES['faculty_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../multimedia/faculty/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['faculty_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['faculty_image']['tmp_name'], $upload_path)) {
                $file_path = 'multimedia/faculty/' . $new_filename;
                
                // Delete old file if exists
                if (!empty($row['file_path']) && file_exists(__DIR__ . '/../../' . $row['file_path'])) {
                    unlink(__DIR__ . '/../../' . $row['file_path']);
                }
                
                // Update or insert into multimedia_content table
                if ($media_id) {
                    $stmt = $conn->prepare("UPDATE multimedia_content SET file_path = ? WHERE id = ?");
                    $stmt->bind_param("si", $file_path, $media_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO multimedia_content (file_path, uploaded_by) VALUES (?, ?)");
                    $stmt->bind_param("si", $file_path, $user_id);
                    $stmt->execute();
                    $media_id = $conn->insert_id;
                }
                $stmt->execute();
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE faculty SET prefix = ?, fname = ?, mname = ?, lname = ?, suffix = ?, name = ?, position = ?, description = ?, specialization = ?, media_id = ? WHERE id = ?");
    $stmt->bind_param("sssssssssii", $prefix, $fname, $mname, $lname, $suffix, $name, $position, $description, $specialization, $media_id, $id);
    $stmt->execute();
    header("Location: faculty.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Faculty</title>
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
        
        .header { 
            background: #7B0000;
            color: white;
            padding: 15px 20px;
            font-size: 2em;
            font-weight: 600;
            letter-spacing: -0.5px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
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

        .form-group:last-child {
            margin-bottom: 0;
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

        input[type="text"], 
        textarea {
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

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }

        input::placeholder, 
        textarea::placeholder {
            color: #666;
        }

        .file-input-wrapper {
            margin-top: 10px;
        }

        .file-input-wrapper input[type="file"] {
            display: none;
        }

        .file-input-wrapper label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: #ffffff;
            border: 2px dashed #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            color: #666;
        }

        .file-input-wrapper label:hover {
            background: #f8f9fa;
            border-color: #7B0000;
        }

        .file-input-wrapper label i {
            font-size: 1.5em;
            color: #7B0000;
        }

        .file-input-wrapper .file-info {
            margin-top: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
            font-size: 0.9em;
            color: #333;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .file-input-wrapper .file-info.show {
            display: flex;
        }

        .file-input-wrapper .file-info i {
            color: #7B0000;
        }

        .file-input-wrapper .remove-file {
            color: #7B0000;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .file-input-wrapper .remove-file:hover {
            background: rgba(123, 0, 0, 0.1);
        }

        .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .help-text i {
            color: #7B0000;
        }

        /* Tags Styles for both Specialization and Description */
        .specialization-container,
        .description-container {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            background: #ffffff;
            min-height: 50px;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .specialization-container:focus-within,
        .description-container:focus-within {
            border-color: #7B0000;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }

        .specialization-tags,
        .description-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
            min-height: 20px;
        }

        .specialization-tag,
        .description-tag {
            background: #7B0000;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeIn 0.3s ease;
        }

        .specialization-tag .remove-tag,
        .description-tag .remove-tag {
            cursor: pointer;
            font-size: 0.8em;
            padding: 2px;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .specialization-tag .remove-tag:hover,
        .description-tag .remove-tag:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .specialization-input-container,
        .description-input-container {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .specialization-input-container input,
        .description-input-container input {
            border: none;
            outline: none;
            flex: 1;
            padding: 8px 0;
            background: transparent;
            font-size: 1em;
        }

        .add-specialization-btn,
        .add-description-btn {
            background: #7B0000;
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .add-specialization-btn:hover,
        .add-description-btn:hover {
            background: #8B0000;
            transform: scale(1.1);
        }

        .specialization-suggestions,
        .description-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }

        .suggestion-item {
            background: #f8f9fa;
            color: #7B0000;
            border: 1px solid #e0e0e0;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .suggestion-item:hover {
            background: #7B0000;
            color: white;
            transform: translateY(-1px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        .current-image-container {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .current-image-container img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #e0e0e0;
        }

        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            justify-content: center;
        }

        button, .buttons a {
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
        }

        .submit-btn {
            background: #7B0000;
            color: white;
            border: 1px solid #7B0000;
        }

        .submit-btn:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
        }

        .cancel-btn:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
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
            .buttons {
                flex-direction: column;
            }
            .buttons button,
            .buttons a {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<?php include '../include/header.php'; ?>
<div class="header">
    <span>Edit Faculty Member</span>
</div>

<div class="container">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="prefix"><i class="fas fa-user-tie"></i> Prefix (Optional)</label>
            <input type="text" id="prefix" name="prefix" placeholder="e.g., Dr., Prof., Engr., Mr., Mrs., Ms." value="<?php echo htmlspecialchars($row['prefix'] ?? ''); ?>">
        </div>

        <div class="form-group" style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label for="fname"><i class="fas fa-user"></i> First Name</label>
                <input type="text" id="fname" name="fname" required placeholder="First name" value="<?php echo htmlspecialchars($row['fname'] ?? ''); ?>">
            </div>
            <div style="flex: 1;">
                <label for="mname"><i class="fas fa-user"></i> Middle Name (Optional)</label>
                <input type="text" id="mname" name="mname" placeholder="Middle name or initial" value="<?php echo htmlspecialchars($row['mname'] ?? ''); ?>">
            </div>
            <div style="flex: 1;">
                <label for="lname"><i class="fas fa-user"></i> Last Name</label>
                <input type="text" id="lname" name="lname" required placeholder="Last name" value="<?php echo htmlspecialchars($row['lname'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="suffix"><i class="fas fa-graduation-cap"></i> Suffix (Optional)</label>
            <input type="text" id="suffix" name="suffix" placeholder="e.g., Jr., Sr., III, MIT, DIT, PhD" value="<?php echo htmlspecialchars($row['suffix'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="position"><i class="fas fa-briefcase"></i> Positions</label>
            <div class="description-container">
                <div class="description-tags" id="positionTags"></div>
                <div class="description-input-container">
                    <input type="text" id="positionInput" placeholder="Add position (e.g., Program Head, CCS Faculty)" onkeypress="handlePositionKeyPress(event)">
                    <button type="button" class="add-description-btn" onclick="addPosition()"><i class="fas fa-plus"></i></button>
                </div>
                <input type="hidden" id="position" name="position" value="<?php echo htmlspecialchars($row['position'] ?? $row['description'] ?? ''); ?>" required>
                <div class="description-suggestions">
                    <div class="suggestion-item" onclick="addPositionSuggestion('College Dean')">College Dean</div>
                    <div class="suggestion-item" onclick="addPositionSuggestion('Program Head')">Program Head</div>
                    <div class="suggestion-item" onclick="addPositionSuggestion('CCS Faculty')">CCS Faculty</div>
                    <div class="suggestion-item" onclick="addPositionSuggestion('SHS Faculty')">SHS Faculty</div>
                    <div class="suggestion-item" onclick="addPositionSuggestion('Research Coordinator')">Research Coordinator</div>
                    <div class="suggestion-item" onclick="addPositionSuggestion('Practicum Coordinator')">Practicum Coordinator</div>
                    <div class="suggestion-item" onclick="addPositionSuggestion('Alumni Coordinator')">Alumni Coordinator</div>
                    <div class="suggestion-item" onclick="addPositionSuggestion('College Secretary')">College Secretary</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="description"><i class="fas fa-info-circle"></i> Short Description (Optional)</label>
            <textarea id="description" name="description" placeholder="Add a brief description about this faculty member..."><?php echo htmlspecialchars($row['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="specialization"><i class="fas fa-graduation-cap"></i> Specializations</label>
            <div class="specialization-container">
                <div class="specialization-tags" id="specializationTags"></div>
                <div class="specialization-input-container">
                    <input type="text" id="specializationInput" placeholder="Add specialization (e.g., Information Technology, Mathematics)" onkeypress="handleSpecializationKeyPress(event)">
                    <button type="button" class="add-specialization-btn" onclick="addSpecialization()"><i class="fas fa-plus"></i></button>
                </div>
                <input type="hidden" id="specialization" name="specialization" value="<?php echo htmlspecialchars($row['specialization'] ?? ''); ?>">
                <div class="specialization-suggestions">
                    <div class="suggestion-item" onclick="addSuggestion('Information Technology')">Information Technology</div>
                    <div class="suggestion-item" onclick="addSuggestion('Mathematics')">Mathematics</div>
                    <div class="suggestion-item" onclick="addSuggestion('Computer Science')">Computer Science</div>
                    <div class="suggestion-item" onclick="addSuggestion('Research')">Research</div>
                    <div class="suggestion-item" onclick="addSuggestion('Administration')">Administration</div>
                    <div class="suggestion-item" onclick="addSuggestion('Senior High School')">Senior High School</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="faculty_image"><i class="fas fa-image"></i> Profile Image</label>
            <?php if (!empty($row['file_path'])): ?>
            <div class="current-image-container">
                <label><i class="fas fa-image"></i> Current Image</label>
                <img src="../../<?php echo htmlspecialchars($row['file_path']); ?>" alt="Current faculty image" class="current-image">
            </div>
            <?php endif; ?>
            
            <div class="file-input-wrapper">
                <label for="faculty_image">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Choose a new file or drag it here</span>
                </label>
                <input type="file" id="faculty_image" name="faculty_image" accept="image/*">
                <div class="file-info">
                    <i class="fas fa-file-image"></i>
                    <span class="file-name"></span>
                    <span class="remove-file"><i class="fas fa-times"></i></span>
                </div>
                <p class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Supported formats: JPG, JPEG, PNG, GIF (Max size: 5MB)
                </p>
                <img src="" alt="Preview" class="preview-image">
            </div>
        </div>

        <div class="buttons">
            <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save Changes</button>
            <a href="faculty.php" class="cancel-btn"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </form>
</div>

<script>
const fileInput = document.getElementById('faculty_image');
const fileInfo = document.querySelector('.file-info');
const fileName = document.querySelector('.file-name');
const removeFile = document.querySelector('.remove-file');
const previewImage = document.querySelector('.preview-image');

fileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        fileName.textContent = file.name;
        fileInfo.classList.add('show');
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

removeFile.addEventListener('click', function(e) {
    e.preventDefault();
    fileInput.value = '';
    fileInfo.classList.remove('show');
    previewImage.style.display = 'none';
    previewImage.src = '';
});

// Specialization Management
let specializations = [];

// Position Management
let positions = [];

// Initialize with existing data
document.addEventListener('DOMContentLoaded', function() {
    // Initialize specializations
    const existingSpecs = document.getElementById('specialization').value;
    if (existingSpecs) {
        specializations = existingSpecs.split(', ').filter(s => s.trim() !== '');
        renderSpecializationTags();
    }
    
    // Initialize positions
    const existingPositions = document.getElementById('position').value;
    if (existingPositions) {
        positions = existingPositions.split(', ').filter(p => p.trim() !== '');
        renderPositionTags();
    }
});

function updateSpecializationField() {
    document.getElementById('specialization').value = specializations.join(', ');
}

function renderSpecializationTags() {
    const container = document.getElementById('specializationTags');
    container.innerHTML = '';
    
    specializations.forEach((spec, index) => {
        const tag = document.createElement('div');
        tag.className = 'specialization-tag';
        tag.innerHTML = `
            <span>${spec}</span>
            <span class="remove-tag" onclick="removeSpecialization(${index})">
                <i class="fas fa-times"></i>
            </span>
        `;
        container.appendChild(tag);
    });
    
    updateSpecializationField();
}

function addSpecialization() {
    const input = document.getElementById('specializationInput');
    const value = input.value.trim();
    
    if (value && !specializations.includes(value)) {
        specializations.push(value);
        renderSpecializationTags();
        input.value = '';
    }
}

function removeSpecialization(index) {
    specializations.splice(index, 1);
    renderSpecializationTags();
}

function addSuggestion(suggestion) {
    if (!specializations.includes(suggestion)) {
        specializations.push(suggestion);
        renderSpecializationTags();
    }
}

function handleSpecializationKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addSpecialization();
    }
}

// Position Management Functions
function updatePositionField() {
    document.getElementById('position').value = positions.join(', ');
}

function renderPositionTags() {
    const container = document.getElementById('positionTags');
    container.innerHTML = '';
    
    positions.forEach((pos, index) => {
        const tag = document.createElement('div');
        tag.className = 'description-tag';
        tag.innerHTML = `
            <span>${pos}</span>
            <span class="remove-tag" onclick="removePosition(${index})">
                <i class="fas fa-times"></i>
            </span>
        `;
        container.appendChild(tag);
    });
    
    updatePositionField();
}

function addPosition() {
    const input = document.getElementById('positionInput');
    const value = input.value.trim();
    
    if (value && !positions.includes(value)) {
        positions.push(value);
        renderPositionTags();
        input.value = '';
    }
}

function removePosition(index) {
    positions.splice(index, 1);
    renderPositionTags();
}

function addPositionSuggestion(suggestion) {
    if (!positions.includes(suggestion)) {
        positions.push(suggestion);
        renderPositionTags();
    }
}

function handlePositionKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addPosition();
    }
}
</script>

</body>
</html>
