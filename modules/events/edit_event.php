<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/media_utils.php';

function respond($statusCode, $data) {
	header('Content-Type: application/json');
	http_response_code($statusCode);
	echo json_encode($data);
	exit;
}

// Utility: fetch event by id
function fetchEvent(mysqli $conn, int $eventId) {
	$query = "SELECT id, title, description, date, duration, time, location, media_id FROM events WHERE id = ?";
    $stmt = $conn->prepare($query);
	$stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
	return $result->fetch_assoc();
}

// GET: return event details as JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
		respond(400, [ 'success' => false, 'message' => 'Missing or invalid event id' ]);
	}
	$event_id = (int)$_GET['id'];
	$event = fetchEvent($conn, $event_id);
	if (!$event) {
		respond(404, [ 'success' => false, 'message' => 'Event not found' ]);
	}

	$media = null;
	if (!empty($event['media_id'])) {
		$ms = $conn->prepare("SELECT id, file_path FROM multimedia_content WHERE id = ?");
		$ms->bind_param("i", $event['media_id']);
		$ms->execute();
		$mr = $ms->get_result();
		$media = $mr->fetch_assoc() ?: null;
	}

	respond(200, [ 'success' => true, 'event' => $event, 'media' => $media ]);
}

// POST: update event fields and optionally replace/remove media
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!isset($_POST['event_id']) || !ctype_digit((string)$_POST['event_id'])) {
		respond(400, [ 'success' => false, 'message' => 'Missing or invalid event_id' ]);
	}
	$event_id = (int)$_POST['event_id'];
	$event = fetchEvent($conn, $event_id);
	if (!$event) {
		respond(404, [ 'success' => false, 'message' => 'Event not found' ]);
	}

	$title = isset($_POST['title']) ? trim($_POST['title']) : $event['title'];
	$description = isset($_POST['description']) ? trim($_POST['description']) : $event['description'];
	$date = isset($_POST['date']) ? $_POST['date'] : $event['date'];
	$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : (int)$event['duration'];
	$time = isset($_POST['time']) ? $_POST['time'] : $event['time'];
	$location = isset($_POST['location']) ? trim($_POST['location']) : $event['location'];

	if ($title === '' || $description === '' || $date === '' || $time === '' || $location === '') {
		respond(400, [ 'success' => false, 'message' => 'Required fields missing' ]);
	}

	$media_id = $event['media_id'];

	// Start transaction for safe media update/removal without breaking FKs
	$conn->begin_transaction();
	try {
		$old_media_id = $media_id ? (int)$media_id : null;
		$new_media_id = null;

		// Accept both remove_media and delete_media flags
		$wants_remove_media = (!empty($_POST['remove_media']) || !empty($_POST['delete_media'])) && $old_media_id;

		// If new media provided, upload it first
		if (isset($_FILES['media']) && isset($_FILES['media']['error']) && $_FILES['media']['error'] === UPLOAD_ERR_OK && !empty($_FILES['media']['name'])) {
            $new_media_id = handleMediaUpload($_FILES['media'], 'events');
            if ($new_media_id) {
                $media_id = $new_media_id;
            }
        }

		// If explicit removal requested and no new media uploaded, clear media reference
		if ($wants_remove_media && !$new_media_id) {
			$media_id = null;
		}

		$update = $conn->prepare("UPDATE events SET title = ?, description = ?, date = ?, duration = ?, time = ?, location = ?, media_id = ? WHERE id = ?");
		$update->bind_param("sssissii", $title, $description, $date, $duration, $time, $location, $media_id, $event_id);
		if (!$update->execute()) {
			throw new Exception($update->error ?: 'Failed to update event');
		}

		// After successful update, delete old media if it was replaced or removed
		if ($old_media_id && (($new_media_id && $old_media_id !== (int)$new_media_id) || $wants_remove_media)) {
			deleteMedia($conn, (int)$old_media_id);
		}

		$conn->commit();

		// Redirect by default after successful update unless client explicitly requests JSON
		if (!isset($_POST['return_json'])) {
			header('Location: upcoming-events.php');
			exit;
		}
		respond(200, [ 'success' => true, 'message' => 'Event updated', 'event_id' => $event_id, 'media_id' => $media_id ]);
	} catch (Exception $e) {
		$conn->rollback();
		respond(500, [ 'success' => false, 'message' => 'Failed to update event', 'error' => $e->getMessage() ]);
	}
}

respond(405, [ 'success' => false, 'message' => 'Method not allowed' ]);
