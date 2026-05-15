<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Suppress display errors to prevent HTML output in JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    include __DIR__ . '/../database/connect.php';
    
    // Check if connection was established
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection variable not set'));
    }
    
    // Ensure proper UTF-8 encoding
    @mysqli_set_charset($conn, 'utf8mb4');
    
    // Get configuration settings
    $config_query = "SELECT config_key, config_value FROM display_configuration WHERE config_key IN ('marquee_speed', 'slide_interval', 'university_mission', 'university_vision', 'college_mission', 'college_vision')";
    $config_result = mysqli_query($conn, $config_query);
    
    if (!$config_result) {
        throw new Exception('Failed to retrieve configuration: ' . mysqli_error($conn));
    }
    
    // Default values
    $config = [
        'marquee_speed' => 'normal',
        'slide_interval' => 5,
        'university_mission' => '',
        'university_vision' => '',
        'college_mission' => 'The College of Computer Studies aims to provide innovative and quality instruction to the advancement of technology, intends to develop an entrepreneurial learning environment towards sustainability  and growth; and develops responsible and morally upright citizens.',
        'college_vision' => 'We  are  committed  to  provide accessible,  responsive,  and  quality Information Technology Education (ITE) programs and to become the Institution of choice in producing competent and responsible IT professionals  who  are  sensitive to  the needs  and  demands  of the industry.'
    ];
    
    // Override with database values if they exist
    while ($row = mysqli_fetch_assoc($config_result)) {
        if ($row['config_key'] === 'marquee_speed') {
            $config['marquee_speed'] = $row['config_value'];
        } elseif ($row['config_key'] === 'slide_interval') {
            $config['slide_interval'] = (int)$row['config_value'];
        } elseif ($row['config_key'] === 'university_mission') {
            $config['university_mission'] = $row['config_value'];
        } elseif ($row['config_key'] === 'university_vision') {
            $config['university_vision'] = $row['config_value'];
        } elseif ($row['config_key'] === 'college_mission') {
            $config['college_mission'] = $row['config_value'];
        } elseif ($row['config_key'] === 'college_vision') {
            $config['college_vision'] = $row['config_value'];
        }
    }
    
    // Convert marquee speed to actual pixel values
    $marquee_speed_values = [
        'slow' => 0.5,
        'normal' => 1.0,
        'fast' => 2.0
    ];
    
    $config['marquee_speed_value'] = $marquee_speed_values[$config['marquee_speed']] ?? 1.0;
    $config['slide_interval_ms'] = $config['slide_interval'] * 1000; // Convert to milliseconds
    
    echo json_encode([
        'status' => 'success',
        'config' => $config,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'config' => [
            'marquee_speed' => 'normal',
            'marquee_speed_value' => 1.0,
            'slide_interval' => 5,
            'slide_interval_ms' => 5000,
            'university_mission' => '',
            'university_vision' => '',
            'college_mission' => 'The College of Computer Studies aims to provide innovative and quality instruction to the advancement of technology, intends to develop an entrepreneurial learning environment towards sustainability  and growth; and develops responsible and morally upright citizens.',
            'college_vision' => 'We  are  committed  to  provide accessible,  responsive,  and  quality Information Technology Education (ITE) programs and to become the Institution of choice in producing competent and responsible IT professionals  who  are  sensitive to  the needs  and  demands  of the industry.'
        ],
        'timestamp' => time()
    ]);
}
?>

