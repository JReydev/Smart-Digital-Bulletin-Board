<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/media_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and validate
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $duration = (int)$_POST['duration'];
    $time = $_POST['time'];
    $location = trim($_POST['location']);
    $created_by = $_SESSION['user_id'];

    // Basic validation
    if (empty($title) || empty($description) || empty($date) || empty($time) || empty($location)) {
        die('All fields are required.');
    }

    if ($duration < 1) {
        die('Duration must be at least 1 day.');
    }

    // Validate that the date is not in the past
    $today = date('Y-m-d');
    if ($date < $today) {
        die('Cannot select a past date for events.');
    }

    // If the date is today, validate that the time is not in the past
    if ($date === $today) {
        $current_time = date('H:i');
        if ($time < $current_time) {
            die('Cannot select a past time for today\'s events.');
        }
    }

    // Handle media upload
    $media_id = null;
    if(isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $media_id = handleMediaUpload($_FILES['media'], 'events');
    }

    // Insert into database using prepared statement
    $query = "INSERT INTO events (title, description, date, duration, time, location, created_by, media_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssisssi", $title, $description, $date, $duration, $time, $location, $created_by, $media_id);

    if ($stmt->execute()) {
        $stmt->close();
        header('Location: upcoming-events.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event</title>
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .popup {
            display: flex;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1002;
            overflow-y: auto;
            padding: 80px 0;
            backdrop-filter: blur(8px);
            align-items: flex-start;
            justify-content: center;
        }
        .popup-wrapper {
            width: 100%;
            min-height: calc(100vh - 280px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px;
        }
        .popup-content {
            background: #ffffff;
            padding: 25px;
            border-radius: 16px;
            width: min(600px, 90%);
            max-height: 80vh;
            position: relative;
            border: 1px solid #e0e0e0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            color: #333;
            transform: translateY(0);
            opacity: 1;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            margin: 20px auto;
        }
        .popup-header {
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .popup-header h3 {
            color: #7B0000;
            font-size: 1.3em;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .popup-header .close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .popup-header .close:hover {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
        }
        .popup-body {
            overflow-y: auto;
            flex: 1;
            margin: 0 -8px;
            padding: 0 8px;
        }
        .popup-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 15px;
        }
        .popup-footer button {
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .popup-footer button[type="button"] {
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
            color: #7B0000;
        }
        .popup-footer button[type="button"]:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .popup-footer button[type="submit"] {
            background: #7B0000;
            color: white;
            border: 1px solid #7B0000;
        }
        .popup-footer button[type="submit"]:hover {
            background: #8B0000;
            transform: translateY(-2px);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: #7B0000;
            font-weight: 500;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group label i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }
        input::placeholder, textarea::placeholder {
            color: #666;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.6;
        }
        .file-input-container {
            margin-top: 10px;
        }
        .file-input-label {
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
        .file-input-label:hover {
            background: #f8f9fa;
            border-color: #7B0000;
        }
        .file-input-label i {
            font-size: 1.5em;
            color: #7B0000;
        }
        input[type="file"] {
            display: none;
        }
        input[type="date"], input[type="time"] {
            color-scheme: light;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .popup {
                padding: 20px 0;
            }
            
            .popup-content {
                width: 95%;
                padding: 20px;
                margin: 10px auto;
            }
            
            .popup-footer {
                flex-direction: column;
                gap: 8px;
            }
            
            .popup-footer button {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .popup-content {
                width: 98%;
                padding: 15px;
                margin: 5px auto;
            }
            
            .popup-header h3 {
                font-size: 1.1em;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-group label {
                font-size: 0.9em;
            }
            
            input, textarea, select {
                padding: 10px 12px;
                font-size: 0.9em;
            }
            
            textarea {
                min-height: 100px;
            }
        }

        @media (max-width: 360px) {
            .popup-content {
                width: 100%;
                padding: 12px;
                margin: 0;
                border-radius: 0;
            }
            
            .popup-header h3 {
                font-size: 1em;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            .form-group label {
                font-size: 0.85em;
            }
            
            input, textarea, select {
                padding: 8px 10px;
                font-size: 0.85em;
            }
            
            textarea {
                min-height: 80px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<!-- Add Event Modal -->
<div class="popup">
    <div class="popup-wrapper">
        <div class="popup-content">
            <div class="popup-header">
                <h3><i class="fas fa-calendar-plus"></i> Add Event</h3>
                <span class="close" onclick="window.location.href='upcoming-events.php'">&times;</span>
            </div>
            
            <div class="popup-body">
                <form action="add_event.php" method="POST" enctype="multipart/form-data" id="eventForm">
                    <div class="form-group">
                        <label for="title"><i class="fas fa-heading"></i> Event Title</label>
                        <input type="text" name="title" id="title" required placeholder="Enter event title">
                    </div>

                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="description" required placeholder="Enter event description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date"><i class="far fa-calendar"></i> Date</label>
                        <input type="date" name="date" id="date" required>
                    </div>

                    <div class="form-group">
                        <label for="duration"><i class="far fa-clock"></i> Duration (days)</label>
                        <input type="number" name="duration" id="duration" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="time"><i class="far fa-clock"></i> Time</label>
                        <input type="time" name="time" id="time" required>
                    </div>

                    <div class="form-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" name="location" id="location" required placeholder="Enter event location">
                    </div>

                    <div class="form-group">
                        <label for="media"><i class="fas fa-image"></i> Media (optional)</label>
                        <div class="file-input-container">
                            <label for="media" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i> Choose a file or drag it here
                            </label>
                            <input type="file" name="media" id="media" accept="image/*,video/*">
                        </div>
                        <div id="media-preview" class="media-preview" style="display: none;">
                            <img id="image-preview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;">
                            <video id="video-preview" style="max-width: 100%; max-height: 300px; margin-top: 10px; border-radius: 8px; display: none;" controls></video>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="popup-footer">
                <button type="button" onclick="window.location.href='upcoming-events.php'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="eventForm">
                    <i class="fas fa-plus"></i> Add Event
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set minimum date to today for the date input
        const dateInput = document.querySelector('input[type="date"]');
        const timeInput = document.querySelector('input[type="time"]');
        
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        
        // Function to handle time validation
        function validateTime() {
            const selectedDate = dateInput.value;
            if (selectedDate === today) {
                const now = new Date();
                const currentHour = String(now.getHours()).padStart(2, '0');
                const currentMinute = String(now.getMinutes()).padStart(2, '0');
                const currentTime = `${currentHour}:${currentMinute}`;
                timeInput.min = currentTime;
            } else {
                timeInput.min = ''; // Reset min time if date is in future
            }
        }
        
        // Add event listeners
        dateInput.addEventListener('change', validateTime);
        timeInput.addEventListener('input', function() {
            if (dateInput.value === today && timeInput.value < timeInput.min) {
                alert('Cannot select a past time for today\'s events.');
                timeInput.value = timeInput.min;
            }
        });
        
        // Initial validation
        validateTime();

        // Media preview functionality
        const mediaInput = document.getElementById('media');
        const mediaPreview = document.getElementById('media-preview');
        const imagePreview = document.getElementById('image-preview');
        const videoPreview = document.getElementById('video-preview');

        mediaInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                mediaPreview.style.display = 'block';
                
                if (file.type.startsWith('image/')) {
                    imagePreview.style.display = 'block';
                    videoPreview.style.display = 'none';
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                    imagePreview.style.display = 'none';
                    videoPreview.style.display = 'block';
                    
                    const url = URL.createObjectURL(file);
                    videoPreview.src = url;
                    videoPreview.onload = function() {
                        URL.revokeObjectURL(url);
                    }
                }
            } else {
                mediaPreview.style.display = 'none';
                imagePreview.style.display = 'none';
                videoPreview.style.display = 'none';
                imagePreview.src = '';
                videoPreview.src = '';
            }
        });
    });
</script>

</body>
</html>
