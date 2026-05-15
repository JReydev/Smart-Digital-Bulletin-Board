<?php
header('Content-Type: application/json');

// Include database connection
$connectPath = __DIR__ . '/../database/connect.php';
include $connectPath;

if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Simple announcements data
    $announcements = [];
    $annQuery = "
        SELECT a.id, a.title, a.content as description, a.announcement_date as date
        FROM announcements a 
        WHERE (a.announcement_date >= CURDATE() OR a.announcement_date IS NULL)
        AND (a.is_archived = 0 OR a.is_archived IS NULL)
        LIMIT 5
    ";
    $annResult = $conn->query($annQuery);
    if ($annResult) {
        while ($row = $annResult->fetch_assoc()) {
            $row['type'] = 'announcements';
            $announcements[] = $row;
        }
    }

    // Simple events data
    $events = [];
    $evtQuery = "
        SELECT e.title, e.description, e.date, e.time
        FROM events e 
        WHERE CONCAT(e.date, ' ', e.time) >= NOW()
        AND (e.is_archived = 0 OR e.is_archived IS NULL)
        LIMIT 5
    ";
    $evtResult = $conn->query($evtQuery);
    if ($evtResult) {
        while ($row = $evtResult->fetch_assoc()) {
            $row['type'] = 'events';
            $row['extra_info'] = $row['date'] . ' at ' . $row['time'];
            $events[] = $row;
        }
    }

    // Simple welcome message
    $welcomeMsg = 'Welcome to Smart Bulletin Board!';
    $welcomeQuery = "SELECT message FROM welcome_message WHERE is_active = 1 LIMIT 1";
    $welcomeResult = $conn->query($welcomeQuery);
    if ($welcomeResult && $row = $welcomeResult->fetch_assoc()) {
        $welcomeMsg = $row['message'];
    }

    // Combine all data
    $allItems = array_merge($announcements, $events);

    // Create response
    $response = [
        'status' => 'success',
        'data' => $allItems,
        'welcome_and_news' => [
            'welcome_message' => $welcomeMsg,
            'news' => []
        ],
        'specializations' => [],
        'debug_info' => [
            'announcements_count' => count($announcements),
            'events_count' => count($events),
            'total_items' => count($allItems)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Fatal error: ' . $e->getMessage()
    ]);
}
?>
