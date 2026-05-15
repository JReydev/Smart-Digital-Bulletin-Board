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

// Get current month and year from GET parameters or default to current
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$month = max(1, min(12, $month)); // Keep month in valid range

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfMonth = date('w', strtotime("$year-$month-01"));

// Labels
$monthLabel = date('F', strtotime("$year-$month-01"));

// For navigation
$prevMonth = $month > 1 ? $month - 1 : 12;
$prevYear = $month > 1 ? $year : $year - 1;
$nextMonth = $month < 12 ? $month + 1 : 1;
$nextYear = $month < 12 ? $year : $year + 1;

// Fetch events from database
$eventsQuery = "
    SELECT e.title, e.date, e.duration, e.time, e.location, 'event' as type
    FROM events e 
    WHERE (
        (YEAR(e.date) = $year AND MONTH(e.date) = $month) OR
        (YEAR(DATE_ADD(e.date, INTERVAL e.duration - 1 DAY)) = $year AND MONTH(DATE_ADD(e.date, INTERVAL e.duration - 1 DAY)) = $month)
    )
    UNION ALL
    SELECT a.title, a.announcement_date as date, 1 as duration, a.announcement_time as time, NULL as location, 'announcement' as type
    FROM announcements a
    WHERE YEAR(a.announcement_date) = $year AND MONTH(a.announcement_date) = $month
    ORDER BY date ASC
";
$eventsResult = mysqli_query($conn, $eventsQuery);

// Organize events by date
$events = [];
while ($row = mysqli_fetch_assoc($eventsResult)) {
    $startDate = date('Y-m-d', strtotime($row['date']));
    $duration = isset($row['duration']) ? (int)$row['duration'] : 1;
    
    for ($i = 0; $i < $duration; $i++) {
        $currentDate = date('Y-m-d', strtotime($startDate . " +$i days"));
        if (!isset($events[$currentDate])) {
            $events[$currentDate] = [];
        }
        $row['is_start'] = ($i === 0);
        $row['is_end'] = ($i === $duration - 1);
        $events[$currentDate][] = $row;
    }
}

// Generate calendar HTML
$calendarHtml = generateCalendarHTML($year, $month, $monthLabel, $prevMonth, $prevYear, $nextMonth, $nextYear, $daysInMonth, $firstDayOfMonth, $events);

echo json_encode([
    'status' => 'success',
    'data' => [
        'year' => $year,
        'month' => $month,
        'monthLabel' => $monthLabel,
        'calendarHtml' => $calendarHtml,
        'events' => $events
    ]
]);

function generateCalendarHTML($year, $month, $monthLabel, $prevMonth, $prevYear, $nextMonth, $nextYear, $daysInMonth, $firstDayOfMonth, $events) {
    $html = '<div class="calendar-container">';
    $html .= '<div class="header">';
    $html .= '<a class="button arrow" href="?month=' . $prevMonth . '&year=' . $prevYear . '">←</a>';
    $html .= '<span class="month-label">' . strtoupper($monthLabel) . ' ' . $year . '</span>';
    $html .= '<a class="button arrow" href="?month=' . $nextMonth . '&year=' . $nextYear . '">→</a>';
    $html .= '</div>';
    
    $html .= '<div class="calendar">';
    
    // Weekday labels
    $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    foreach ($weekdays as $day) {
        $html .= '<div class="calendar-header">' . $day . '</div>';
    }
    
    // Empty cells before the first day of the month
    for ($i = 0; $i < $firstDayOfMonth; $i++) {
        $html .= '<div class="calendar-day empty"></div>';
    }
    
    // Calendar day cells
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dateKey = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $html .= '<div class="calendar-day">';
        $weekdayShort = date('D', strtotime($dateKey));
        $html .= '<span class="day-number mobile-only">' . $day . '</span>';
        $html .= '<span class="day-number desktop-only">' . $day . '</span>';
        
        if (isset($events[$dateKey])) {
            foreach ($events[$dateKey] as $event) {
                $typeClass = $event['type'] . '-type';
                $multiDayClass = '';
                if ($event['type'] === 'event' && $event['duration'] > 1) {
                    $multiDayClass = 'multi-day';
                    if ($event['is_start']) {
                        $multiDayClass .= ' start';
                    }
                    if ($event['is_end']) {
                        $multiDayClass .= ' end';
                    }
                }
                $html .= '<div class="event ' . $typeClass . ' ' . $multiDayClass . '">';
                if ($event['is_start']) {
                    $html .= htmlspecialchars($event['title']);
                    if ($event['type'] === 'event' && $event['time']) {
                        $html .= '<div class="event-details">';
                        $html .= date('h:i A', strtotime($event['time']));
                        if ($event['location']) {
                            $html .= ' - ' . htmlspecialchars($event['location']);
                        }
                        $html .= '</div>';
                    }
                }
                $html .= '</div>';
            }
        }
        $html .= '</div>';
    }
    
    // Fill the remaining cells to complete the last row
    $totalDisplayed = $firstDayOfMonth + $daysInMonth;
    $remaining = (7 - ($totalDisplayed % 7)) % 7;
    for ($i = 0; $i < $remaining; $i++) {
        $html .= '<div class="calendar-day empty"></div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
?>
