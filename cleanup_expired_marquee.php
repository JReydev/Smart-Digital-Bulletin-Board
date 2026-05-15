<?php
/**
 * Marquee Content Cleanup Script
 * 
 * This script removes expired marquee content from the database.
 * It can be run manually or via cron job for automatic cleanup.
 * 
 * Usage:
 * - Manual: php cleanup_expired_marquee.php
 * - Cron: 0 0 * * * php /path/to/cleanup_expired_marquee.php
 */

// Include database connection
require_once __DIR__ . '/database/connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Log function for tracking cleanup activities
function logCleanup($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    echo $logMessage;
    
    // Also write to log file
    $logFile = __DIR__ . '/logs/marquee_cleanup.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Start cleanup process
logCleanup("Starting marquee content cleanup process");

try {
    // Check if auto-expire is enabled and clean up expired welcome messages
    $welcomeCleanupQuery = "
        UPDATE welcome_message 
        SET is_active = 0 
        WHERE is_active = 1 
        AND auto_expire = 1 
        AND expires_at IS NOT NULL 
        AND expires_at <= NOW()
    ";
    
    $welcomeResult = mysqli_query($conn, $welcomeCleanupQuery);
    $welcomeAffected = mysqli_affected_rows($conn);
    
    if ($welcomeResult) {
        logCleanup("Deactivated $welcomeAffected expired welcome message(s)");
    } else {
        logCleanup("Error cleaning up welcome messages: " . mysqli_error($conn));
    }
    
    // Check if auto-expire is enabled and clean up expired news items
    $newsCleanupQuery = "
        UPDATE news_update 
        SET is_active = 0 
        WHERE is_active = 1 
        AND auto_expire = 1 
        AND expires_at IS NOT NULL 
        AND expires_at <= NOW()
    ";
    
    $newsResult = mysqli_query($conn, $newsCleanupQuery);
    $newsAffected = mysqli_affected_rows($conn);
    
    if ($newsResult) {
        logCleanup("Deactivated $newsAffected expired news item(s)");
    } else {
        logCleanup("Error cleaning up news items: " . mysqli_error($conn));
    }
    
    // Optional: Permanently delete very old expired content (older than 30 days)
    $deleteOldQuery = "
        DELETE FROM news_update 
        WHERE is_active = 0 
        AND expires_at IS NOT NULL 
        AND expires_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    
    $deleteResult = mysqli_query($conn, $deleteOldQuery);
    $deleteAffected = mysqli_affected_rows($conn);
    
    if ($deleteResult) {
        logCleanup("Permanently deleted $deleteAffected old expired news item(s)");
    } else {
        logCleanup("Error deleting old expired content: " . mysqli_error($conn));
    }
    
    // Get statistics for reporting
    $statsQuery = "
        SELECT 
            (SELECT COUNT(*) FROM welcome_message WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())) as active_welcome,
            (SELECT COUNT(*) FROM news_update WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())) as active_news,
            (SELECT COUNT(*) FROM welcome_message WHERE is_active = 0 AND expires_at IS NOT NULL AND expires_at <= NOW()) as expired_welcome,
            (SELECT COUNT(*) FROM news_update WHERE is_active = 0 AND expires_at IS NOT NULL AND expires_at <= NOW()) as expired_news
    ";
    
    $statsResult = mysqli_query($conn, $statsQuery);
    if ($statsResult && $statsRow = mysqli_fetch_assoc($statsResult)) {
        logCleanup("Cleanup completed. Statistics:");
        logCleanup("- Active welcome messages: " . $statsRow['active_welcome']);
        logCleanup("- Active news items: " . $statsRow['active_news']);
        logCleanup("- Expired welcome messages: " . $statsRow['expired_welcome']);
        logCleanup("- Expired news items: " . $statsRow['expired_news']);
    }
    
    logCleanup("Marquee content cleanup process completed successfully");
    
} catch (Exception $e) {
    logCleanup("Error during cleanup process: " . $e->getMessage());
    exit(1);
}

// Close database connection
mysqli_close($conn);

logCleanup("Cleanup script finished");
?>
