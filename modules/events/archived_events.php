<?php
include_once __DIR__ . '/../include/auth_required.php';
include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Check if is_archived column exists, if not, add it
$check_column = "SHOW COLUMNS FROM events LIKE 'is_archived'";
$column_result = mysqli_query($conn, $check_column);
if (mysqli_num_rows($column_result) == 0) {
    // Add the column if it doesn't exist
    $add_column = "ALTER TABLE events ADD COLUMN is_archived TINYINT(1) DEFAULT 0";
    mysqli_query($conn, $add_column);
}

// Fetch archived events
$query = "SELECT e.*, m.file_path, u.username as created_by_name 
          FROM events e 
          LEFT JOIN multimedia_content m ON e.media_id = m.id 
          LEFT JOIN users u ON e.created_by = u.id 
          WHERE e.is_archived = 1
          ORDER BY e.date DESC, e.time DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die('Error fetching archived events: ' . mysqli_error($conn));
}

$archived_events = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .back-button .button-text {
            display: inline;
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
            position: relative;
        }
        .back-button {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.5em;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%) translateX(-5px);
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
        .event-card { 
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            opacity: 0.8; /* Slightly faded to indicate archived status */
        }
        .event-card:hover {
            background: #f8f9fa;
            transform: translateX(5px);
            border-color: #7B0000;
            opacity: 1;
        }
        .event-card h3 { 
            font-size: 1.4em;
            font-weight: 600;
            color: #7B0000;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .event-card p { 
            font-size: 1.1em;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .event-meta { 
            color: #666;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
        }
        .event-meta i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }
        .event-media {
            margin: 15px 0;
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .event-media img, .event-media video {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 12px;
        }
        .event-details {
            padding: 10px 0;
        }
        .event-actions {
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
            color: #7B0000;
            text-decoration: none;
            transition: all 0.3s ease;
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .restore-btn:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .restore-btn i {
            font-size: 1em;
            color: #7B0000;
        }
        .event-description {
            white-space: pre-wrap;
            margin: 15px 0;
        }
        .archive-info {
            background: rgba(128, 128, 128, 0.1);
            padding: 10px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.9em;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .archive-info i {
            color: #666;
        }
        .no-archives {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .no-archives i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #ccc;
        }
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
                margin: 15px auto;
            }
            .event-card {
                padding: 15px;
            }
            .event-media img, .event-media video {
                max-height: 300px;
            }
            .header {
                font-size: 1.5em;
                padding: 12px 16px;
            }
            .back-button {
                font-size: 0.9em;
                padding: 8px;
                border-radius: 50%;
                width: auto;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .back-button .button-text { display: none; }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .container {
                width: 98%;
                padding: 12px;
                margin: 10px auto;
            }
            .event-card {
                padding: 12px;
                margin-bottom: 15px;
            }
            .event-card h3 {
                font-size: 1.1em;
            }
            .event-card p {
                font-size: 0.95em;
            }
            .event-media img, .event-media video {
                max-height: 200px;
            }
            .header {
                font-size: 1.3em;
                padding: 10px 12px;
            }
            .back-button {
                font-size: 1em;
                padding: 8px;
                border-radius: 50%;
                left: 12px;
            }
            .back-button .button-text { display: none; }
        }

        /* Delete Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            overflow-y: auto;
            padding: 80px 0;
            backdrop-filter: blur(5px);
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
            padding: 15px 20px;
            color: #7B0000;
            font-size: 1.4em;
            font-weight: 600;
        }
        .modal-body {
            padding: 15px 20px;
            color: #333;
            text-align: center;
        }
        .modal-footer {
            padding: 15px 20px;
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
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .modal-footer .cancel-btn:hover {
            background: rgba(123, 0, 0, 0.2);
        }
        .modal-footer .confirm-restore-btn {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid rgba(123, 0, 0, 0.2);
        }
        .modal-footer .confirm-restore-btn:hover {
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
        
        /* Form Styles for Restore Modal */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #7B0000;
            font-weight: 500;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group label i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .help-text i {
            color: #7B0000;
            font-size: 14px;
        }
        .restore-info {
            background: rgba(123, 0, 0, 0.05);
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.9em;
            color: #7B0000;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .restore-info i {
            color: #7B0000;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1em;
            font-weight: 500;
        }
        .alert-success {
            background: rgba(46, 160, 67, 0.1);
            color: #2ea043;
            border: 1px solid rgba(46, 160, 67, 0.2);
        }
        .alert-error {
            background: rgba(248, 81, 73, 0.1);
            color: #f85149;
            border: 1px solid rgba(248, 81, 73, 0.2);
        }
        .alert i {
            font-size: 1.2em;
        }
    </style>
</head>
<body>

<div class="header">
    <a href="upcoming-events.php" class="back-button">
        <i class="fas fa-arrow-left"></i> <span class="button-text">Back to Events</span>
    </a>
    <span><i class="fas fa-archive"></i> Archived Events</span>
</div>

<div class="container">
    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($archived_events)): ?>
        <div class="no-archives">
            <i class="fas fa-box-open"></i>
            <p>No archived events found</p>
            <p style="margin-top: 10px; font-size: 0.9em;">Events are automatically archived after they end, or can be manually archived by their creators.</p>
        </div>
    <?php else: ?>
        <?php foreach ($archived_events as $event): ?>
            <?php
            $end_date = date('Y-m-d', strtotime($event['date'] . ' + ' . ($event['duration'] - 1) . ' days'));
            $date_display = $event['date'] == $end_date ? 
                date('F j, Y', strtotime($event['date'])) : 
                date('F j', strtotime($event['date'])) . ' - ' . date('F j, Y', strtotime($end_date));

            // Normalize media path for robustness across historical data and OS differences
            $filePath = isset($event['file_path']) ? $event['file_path'] : '';
            $mediaSrc = '';
            if ($filePath) {
                $filePath = str_replace('\\', '/', $filePath); // normalize windows backslashes
                $filePath = ltrim($filePath, '/'); // remove leading slash
                if (strpos($filePath, 'SmartBulletin/') === 0) {
                    $filePath = substr($filePath, strlen('SmartBulletin/'));
                }
                if (strpos($filePath, 'multimedia/') === 0) {
                    $mediaSrc = '../../' . $filePath;
                } elseif (strpos($filePath, 'events/') === 0) {
                    $mediaSrc = '../../multimedia/' . $filePath;
                } elseif (strpos($filePath, 'announcements/') === 0 || strpos($filePath, 'faculty/') === 0 || strpos($filePath, 'officers/') === 0) {
                    $mediaSrc = '../../multimedia/' . $filePath;
                } else {
                    $mediaSrc = '../../multimedia/events/' . basename($filePath);
                }
            }
            ?>
            <div class="event-card">
                <?php if($event['file_path']): ?>
                    <div class="event-media">
                        <?php
                        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                            <img src="<?php echo $mediaSrc; ?>" alt="Event Media">
                        <?php elseif(in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                            <video controls>
                                <source src="<?php echo $mediaSrc; ?>" type="video/<?php echo $ext; ?>">
                            </video>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="event-details">
                    <h3>
                        <i class="fas fa-archive"></i>
                        <?php echo htmlspecialchars($event['title']); ?>
                    </h3>
                    <p class="event-meta"><i class="far fa-calendar"></i> <?php echo $date_display; ?></p>
                    <p class="event-meta"><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($event['time'])); ?></p>
                    <p class="event-meta"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                    <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    <p class="event-meta"><i class="far fa-clock"></i> Duration: <?php echo $event['duration']; ?> day<?php echo $event['duration'] > 1 ? 's' : ''; ?></p>
                    <p class="event-meta"><i class="far fa-user"></i> Posted by: <?php echo htmlspecialchars($event['created_by_name']); ?></p>
                    
                    <div class="archive-info">
                        <i class="fas fa-info-circle"></i>
                        This event has been archived and is no longer displayed in the upcoming events list.
                    </div>

                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $event['created_by']): ?>
                        <div class="event-actions">
                            <button type="button" class="restore-btn" onclick="showRestoreForm(<?php echo $event['id']; ?>, '<?php echo addslashes(htmlspecialchars($event['title'])); ?>', '<?php echo $event['date']; ?>', '<?php echo $event['time']; ?>', <?php echo $event['duration']; ?>, '<?php echo addslashes(htmlspecialchars($event['location'])); ?>')">
                                <i class="fas fa-undo"></i> Restore Event
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Restore Event Form Modal -->
<div class="modal" id="restoreModal">
    <div class="modal-content" style="max-width: 600px; width: 90%;">
        <div class="modal-header">
            <h3><i class="fas fa-undo"></i> Restore Event</h3>
            <button type="button" class="close" onclick="closeRestoreForm()">&times;</button>
        </div>
        <form id="restoreEventForm" action="restore_event.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="event_id" id="restoreEventId">
                
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Event Title</label>
                    <input type="text" id="restoreEventTitle" readonly style="background: #f5f5f5; color: #666;">
                </div>

                <div class="form-group">
                    <label for="restoreDate"><i class="far fa-calendar"></i> New Date</label>
                    <input type="date" name="date" id="restoreDate" required>
                    <div class="help-text">
                        <i class="fas fa-info-circle"></i>
                        Update the event date to make it relevant for upcoming events
                    </div>
                </div>

                <div class="form-group">
                    <label for="restoreTime"><i class="far fa-clock"></i> Time</label>
                    <input type="time" name="time" id="restoreTime" required>
                </div>

                <div class="form-group">
                    <label for="restoreDuration"><i class="far fa-clock"></i> Duration (days)</label>
                    <input type="number" name="duration" id="restoreDuration" min="1" required>
                </div>

                <div class="form-group">
                    <label for="restoreLocation"><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" name="location" id="restoreLocation" required>
                </div>

                <div class="restore-info">
                    <i class="fas fa-info-circle"></i>
                    This will restore the event and make it visible in the upcoming events list with the updated information.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeRestoreForm()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="confirm-restore-btn">
                    <i class="fas fa-undo"></i> Restore Event
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentEventId = null;

function showRestoreForm(eventId, eventTitle, eventDate, eventTime, eventDuration, eventLocation) {
    currentEventId = eventId;
    
    // Populate form fields
    document.getElementById('restoreEventId').value = eventId;
    document.getElementById('restoreEventTitle').value = eventTitle;
    document.getElementById('restoreDate').value = eventDate;
    document.getElementById('restoreTime').value = eventTime;
    document.getElementById('restoreDuration').value = eventDuration;
    document.getElementById('restoreLocation').value = eventLocation;
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('restoreDate').min = today;
    
    // Show modal
    document.getElementById('restoreModal').classList.add('show');
}

function closeRestoreForm() {
    document.getElementById('restoreModal').classList.remove('show');
    setTimeout(() => {
        currentEventId = null;
        document.getElementById('restoreEventForm').reset();
    }, 300);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('restoreModal');
    if (event.target === modal) {
        closeRestoreForm();
    }
}

// Add keyboard support
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRestoreForm();
    }
});

// Form validation and date/time handling
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('restoreDate');
    const timeInput = document.getElementById('restoreTime');
    
    // Function to handle time validation
    function validateTime() {
        const today = new Date().toISOString().split('T')[0];
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
        const today = new Date().toISOString().split('T')[0];
        if (dateInput.value === today && timeInput.value < timeInput.min) {
            alert('Cannot select a past time for today\'s events.');
            timeInput.value = timeInput.min;
        }
    });
    
    // Form submission handling
    document.getElementById('restoreEventForm').addEventListener('submit', function(e) {
        const selectedDate = dateInput.value;
        const today = new Date().toISOString().split('T')[0];
        
        if (selectedDate < today) {
            e.preventDefault();
            alert('Cannot select a past date for events.');
            dateInput.focus();
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';
    });
});
</script>

</body>
</html>
