<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

function getActiveThemeName() {
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $month = (int)$now->format('n');
    $day = (int)$now->format('j');
    $weekday = (int)$now->format('w');
    $year = (int)$now->format('Y');

    // Manual override via query param (e.g., ?theme=christmas)
    $allowed = [
        'christmas','newyear','chinesenewyear','valentines','kagitingan','holyweek',
        'laborday','independence','nationalheroes','bonifacio','undas','rizal','halloween'
    ];
    if (isset($_GET['theme'])) {
        $override = strtolower(trim($_GET['theme']));
        if (in_array($override, $allowed, true)) {
            return $override;
        }
        if ($override === 'default') {
            return '';
        }
    }

    // Priority-based theme system
    // Higher priority number = takes precedence when dates overlap
    // Priority levels:
    // 1 = Long countdown periods (100 days before)
    // 5 = Regular theme periods
    // 10 = Extended celebration periods
    // 20 = Core celebration dates
    
    $activeThemes = [];
    
    // Calculate days until Christmas (Dec 25)
    $christmas = new DateTime("$year-12-25", new DateTimeZone('Asia/Manila'));
    $daysUntilChristmas = $now->diff($christmas)->days;
    $isBeforeChristmas = $now < $christmas;
    
    // Christmas: 100 days before Dec 25 through Dec 25
    if ($isBeforeChristmas && $daysUntilChristmas <= 100) {
        $activeThemes[] = ['name' => 'christmas', 'priority' => 1];
    } elseif ($month === 12 && $day >= 1 && $day <= 25) {
        $activeThemes[] = ['name' => 'christmas', 'priority' => 20];
    }
    
    // Rizal Day: Dec 30 (single-day window)
    if ($month === 12 && $day === 30) {
        $activeThemes[] = ['name' => 'rizal', 'priority' => 20];
    }
    
    // New Year: Dec 31 – Jan 2
    if (($month === 12 && $day >= 31) || ($month === 1 && $day <= 2)) {
        $activeThemes[] = ['name' => 'newyear', 'priority' => 20];
    }
    
    // Chinese New Year (movable): rough window Jan 20 – Feb 20
    if (($month === 1 && $day >= 20) || ($month === 2 && $day <= 20)) {
        $activeThemes[] = ['name' => 'chinesenewyear', 'priority' => 5];
    }
    
    // Valentines: Feb 1 – 14 (higher priority than Chinese New Year)
    if ($month === 2 && $day <= 14) {
        $activeThemes[] = ['name' => 'valentines', 'priority' => 10];
    }
    
    // Holy Week (movable): rough window Mar 20 – Apr 5
    if (($month === 3 && $day >= 20) || ($month === 4 && $day <= 5)) {
        $activeThemes[] = ['name' => 'holyweek', 'priority' => 10];
    }
    
    // Araw ng Kagitingan: Apr 9 (window Apr 7 – Apr 10)
    if ($month === 4 && $day >= 7 && $day <= 10) {
        $activeThemes[] = ['name' => 'kagitingan', 'priority' => 10];
    }
    
    // Labor Day: May 1 (window Apr 30 – May 2)
    if (($month === 4 && $day === 30) || ($month === 5 && $day <= 2)) {
        $activeThemes[] = ['name' => 'laborday', 'priority' => 10];
    }
    
    // Independence Day: Jun 1 – 14 (focus around Jun 12)
    if ($month === 6 && $day <= 14) {
        $activeThemes[] = ['name' => 'independence', 'priority' => 10];
    }
    
    // National Heroes Day: last Monday of August (approx window Aug 25 – 31)
    if ($month === 8 && $day >= 25) {
        $activeThemes[] = ['name' => 'nationalheroes', 'priority' => 10];
    }
    
    // Halloween: Oct 1 – Oct 30 (extended Halloween season)
    if ($month === 10 && $day >= 1 && $day <= 30) {
        $activeThemes[] = ['name' => 'halloween', 'priority' => 10];
    }
    
    // Undas (Araw ng mga Patay): Oct 31 – Nov 2
    if (($month === 10 && $day >= 31) || ($month === 11 && $day <= 2)) {
        $activeThemes[] = ['name' => 'undas', 'priority' => 20];
    }
    
    // Bonifacio Day: Nov 30 (window Nov 29 – Nov 30 only, not Dec 1)
    if ($month === 11 && $day >= 29 && $day <= 30) {
        $activeThemes[] = ['name' => 'bonifacio', 'priority' => 10];
    }
    
    // If no themes active, return empty
    if (empty($activeThemes)) {
        return '';
    }
    
    // Sort by priority (highest first)
    usort($activeThemes, function($a, $b) {
        return $b['priority'] - $a['priority'];
    });
    
    // Return the highest priority theme
    return $activeThemes[0]['name'];
}

try {
    $theme_name = getActiveThemeName();
    $theme_class = $theme_name ? ('theme-' . $theme_name) : '';
    
    echo json_encode([
        'status' => 'success',
        'theme_name' => $theme_name,
        'theme_class' => $theme_class,
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to get theme',
        'theme_name' => '',
        'theme_class' => '',
        'timestamp' => time()
    ]);
}
?>
