<?php
// Suppress display errors to prevent HTML output in JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    include __DIR__ . '/../database/connect.php';
    
    // Check if connection was established
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection variable not set'));
    }
    
    // Ensure proper UTF-8 encoding in hosted environments to avoid json_encode failures
    @mysqli_set_charset($conn, 'utf8mb4');
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ]);
    exit;
}

function cleanupExpiredContent($conn) {
    // Deactivate expired welcome messages that have auto_expire enabled
    $welcomeCleanupQuery = "
        UPDATE welcome_message 
        SET is_active = 0 
        WHERE is_active = 1 
        AND auto_expire = 1 
        AND expires_at IS NOT NULL 
        AND expires_at <= NOW()
    ";
    
    $welcomeResult = mysqli_query($conn, $welcomeCleanupQuery);
    if ($welcomeResult) {
        $welcomeCount = mysqli_affected_rows($conn);
        if ($welcomeCount > 0) {
            error_log("Deactivated $welcomeCount expired welcome message(s)");
        }
    } else {
        error_log("Error cleaning up expired welcome messages: " . mysqli_error($conn));
    }
    
    // Deactivate expired news items that have auto_expire enabled
    $newsCleanupQuery = "
        UPDATE news_update 
        SET is_active = 0 
        WHERE is_active = 1 
        AND auto_expire = 1 
        AND expires_at IS NOT NULL 
        AND expires_at <= NOW()
    ";
    
    $newsResult = mysqli_query($conn, $newsCleanupQuery);
    if ($newsResult) {
        $newsCount = mysqli_affected_rows($conn);
        if ($newsCount > 0) {
            error_log("Deactivated $newsCount expired news item(s)");
        }
    } else {
        error_log("Error cleaning up expired news: " . mysqli_error($conn));
    }
}

function getWelcomeAndNews($conn) {
    $result = [];
    
    // Get active welcome message (not expired)
    $welcomeQuery = "
        SELECT message, expires_at, auto_expire 
        FROM welcome_message 
        WHERE is_active = 1 
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY updated_at DESC 
        LIMIT 1
    ";
    $welcomeResult = mysqli_query($conn, $welcomeQuery);
    
    if ($welcomeResult && mysqli_num_rows($welcomeResult) > 0) {
        $welcomeRow = mysqli_fetch_assoc($welcomeResult);
        $result['welcome_message'] = $welcomeRow['message'];
    } else {
        $result['welcome_message'] = 'Welcome to Smart Bulletin Board - Your Digital Campus Hub!';
    }
    
    // Get active news items for marquee (not expired and not archived)
    $newsQuery = "
        SELECT title, content, expires_at, auto_expire, priority, published_at
        FROM news_update 
        WHERE is_active = 1 
        AND is_archived = 0
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY priority ASC, published_at DESC 
        LIMIT 10
    ";
    $newsResult = mysqli_query($conn, $newsQuery);
    
    $news = [];
    if ($newsResult && mysqli_num_rows($newsResult) > 0) {
        while ($row = mysqli_fetch_assoc($newsResult)) {
            $news[] = [
                'title' => $row['title'],
                'content' => $row['content'],
                'expires_at' => $row['expires_at'],
                'auto_expire' => $row['auto_expire'],
                'priority' => $row['priority'],
                'published_at' => $row['published_at']
            ];
        }
    }
    
    $result['news'] = $news;
    return $result;
}

// Handle the request
try {
    // Clean up expired content (welcome messages and news items)
    cleanupExpiredContent($conn);
    
    $marqueeData = getWelcomeAndNews($conn);
    
    echo json_encode([
        'status' => 'success',
        'data' => $marqueeData,
        'timestamp' => time(),
        'cache_duration' => 120 // 2 minutes cache suggestion
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
