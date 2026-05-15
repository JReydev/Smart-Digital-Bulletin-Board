<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized access";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get the form data
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $created_by = (int)$_SESSION['user_id'];
        $media_id = null;
        $announcement_date = !empty($_POST['announcement_date']) ? trim($_POST['announcement_date']) : null;
        $announcement_time = !empty($_POST['announcement_time']) ? trim($_POST['announcement_time']) : null;

        // Validate inputs
        if (empty($title) || empty($content)) {
            throw new Exception('Title and content are required.');
        }

        // Validate title length
        if (strlen($title) < 3 || strlen($title) > 255) {
            throw new Exception('Title must be between 3 and 255 characters.');
        }

        // Validate title format (allow special characters)
        if (!preg_match('/^[A-Za-z0-9\s\-_.,!?()&@#$%^*+=|\\/:\'"`~]+$/', $title)) {
            throw new Exception('Title contains invalid characters.');
        }

        // Validate date format only if date is provided
        if (!empty($announcement_date)) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $announcement_date)) {
                throw new Exception('Invalid date format. Use YYYY-MM-DD format.');
            }

            // Validate that the date is not in the past
            $today = date('Y-m-d');
            if ($announcement_date < $today) {
                throw new Exception('Cannot select a past date for announcements.');
            }

            // If the date is today, validate that the time is not in the past
            if ($announcement_date === $today && !empty($announcement_time)) {
                $current_time = date('H:i');
                if ($announcement_time < $current_time) {
                    throw new Exception('Cannot select a past time for today\'s announcements.');
                }
            }
        }

        // Validate time format if provided
        if (!empty($announcement_time) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $announcement_time)) {
            throw new Exception('Invalid time format. Use HH:MM format.');
        }

        // If time is provided without date, show warning
        if (!empty($announcement_time) && empty($announcement_date)) {
            throw new Exception('Time cannot be set without a date. Please provide a date or leave both fields empty for tentative announcements.');
        }

        // Start transaction
        $conn->begin_transaction();

        // Handle media upload if present
        $media_ids = [];
        if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
            $uploadDir = __DIR__ . '/../../multimedia/announcements/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception('Failed to create upload directory.');
                }
            }

            // Process each uploaded file
            foreach ($_FILES['media']['name'] as $index => $filename) {
                if ($_FILES['media']['error'][$index] === UPLOAD_ERR_OK) {
                    // Validate file type and size
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    if (!in_array($_FILES['media']['type'][$index], $allowed_types)) {
                        throw new Exception('Only image files (JPEG, PNG, GIF) are allowed.');
                    }

                    if ($_FILES['media']['size'][$index] > $max_size) {
                        throw new Exception('File size exceeds the 5MB limit.');
                    }

                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    $newFileName = uniqid('media_', true) . '.' . $ext;
                    $targetPath = $uploadDir . $newFileName;
                    //$relativePath = 'multimedia/announcements/' . $newFileName;
                    $relativePath = 'announcements/' . $newFileName;

                    if (move_uploaded_file($_FILES['media']['tmp_name'][$index], $targetPath)) {
                        $stmt = $conn->prepare("INSERT INTO multimedia_content (file_path, uploaded_by, uploaded_at) VALUES (?, ?, NOW())");
                        if ($stmt === false) {
                            throw new Exception('MySQL prepare failed: ' . $conn->error);
                        }
                        
                        $stmt->bind_param("si", $relativePath, $created_by);
                        if (!$stmt->execute()) {
                            throw new Exception('Error executing multimedia content insert: ' . $stmt->error);
                        }
                        
                        $media_ids[] = $conn->insert_id;
                        $stmt->close();
                    } else {
                        throw new Exception('Failed to move uploaded file.');
                    }
                }
            }
        }

        // Insert into announcements
        $current_datetime = date('Y-m-d H:i:s');
        $is_archived = false;
        
        // Check if announcement is already passed
        if (!empty($announcement_date) && !empty($announcement_time)) {
            $announcement_datetime = $announcement_date . ' ' . $announcement_time;
            $is_archived = ($announcement_datetime < $current_datetime);
        } else {
            $is_archived = false; // New announcements without date/time (tentative) are not archived initially
        }

        $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_by, created_at, announcement_date, announcement_time, is_archived) VALUES (?, ?, ?, NOW(), ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception('MySQL prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("ssissi", $title, $content, $created_by, $announcement_date, $announcement_time, $is_archived);

        if (!$stmt->execute()) {
            throw new Exception('Error executing announcements insert: ' . $stmt->error);
        }

        $announcement_id = $conn->insert_id;

        // Insert media associations with display order
        if (!empty($media_ids)) {
            $display_order = 1; // Start from 1 for consistency with reorder_media.php
            foreach ($media_ids as $media_id) {
                $stmt = $conn->prepare("INSERT INTO announcement_media (announcement_id, media_id, display_order) VALUES (?, ?, ?)");
                if ($stmt === false) {
                    throw new Exception('MySQL prepare failed: ' . $conn->error);
                }
                
                $stmt->bind_param("iii", $announcement_id, $media_id, $display_order);
                if (!$stmt->execute()) {
                    throw new Exception('Error executing announcement_media insert: ' . $stmt->error);
                }
                
                $display_order++;
                $stmt->close();
            }
        }

        // Commit transaction
        $conn->commit();
        header('Location: announcement.php');
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        http_response_code(400);
        echo $e->getMessage();
        exit;
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Announcement</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to today for the date input
            const dateInput = document.querySelector('input[name="announcement_date"]');
            const timeInput = document.querySelector('input[name="announcement_time"]');
            
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
                    alert('Cannot select a past time for today\'s announcements.');
                    timeInput.value = timeInput.min;
                }
            });
            
            // Initial validation
            validateTime();
        });
    </script>
</head>
