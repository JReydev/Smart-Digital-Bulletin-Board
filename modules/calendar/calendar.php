<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';
include __DIR__ . '/../include/header.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Interactive Calendar</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            text-align: center;
            background: #ffffff;
            margin: 0;
            padding: 100px 0 0 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        .calendar-container {
            width: 90%;
            max-width: 1100px;
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            margin: 20px 0;
            position: relative;
            z-index: 1;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #7B0000;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            color: white;
        }

        .button {
            padding: 10px 18px;
            background: #7B0000;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 44px; /* Touch target size */
            min-width: 44px;
        }

        .button:hover {
            background: #8B0000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(123, 0, 0, 0.3);
        }

        .button.arrow {
            font-size: 15px;
            padding: 8px 20px;
            font-weight: bold;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            background: #f8f9fa;
            margin-top: 10px;
            border-radius: 10px;
            padding: 2px;
        }

        .calendar-header {
            background: #7B0000;
            color: white;
            padding: 15px;
            font-weight: bold;
            text-align: center;
            border-radius: 8px;
        }

        .calendar-day {
            background: #ffffff;
            color: #333;
            font-weight: bold;
            font-size: 16px;
            height: 120px;
            position: relative;
            overflow: auto;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .calendar-day:hover {
            background: #f8f9fa;
        }

        .day-number {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 14px;
            color: #666;
        }

        .event {
            margin-top: 25px;
            font-size: 14px;
            color: #333;
            padding: 8px 12px;
            border-radius: 5px;
            text-align: left;
            margin-left: 5px;
            margin-right: 5px;
            cursor: pointer;
            position: relative;
            height: auto;
            min-height: 40px;
            overflow: hidden;
            white-space: normal;
            text-overflow: ellipsis;
            transition: all 0.3s ease;
            line-height: 1.4;
        }

        .event.event-type {
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
            color: #7B0000;
            font-weight: 600;
        }

        .event.event-type:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .event.event-type.multi-day {
            border-radius: 0;
            margin-right: 0;
            margin-left: 0;
            padding-right: 5px;
            padding-left: 5px;
            position: relative;
            z-index: 1;
        }

        .event.event-type.multi-day.start {
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
            margin-left: 5px;
        }

        .event.event-type.multi-day.end {
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
            margin-right: 5px;
        }

        .event.event-type.multi-day:not(.start):not(.end) {
            margin-left: -5px;
            margin-right: -5px;
        }

        .event.announcement-type {
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.2);
            color: #7B0000;
        }

        .event.announcement-type:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .empty {
            background: #f8f9fa;
        }

        .event-details {
            font-size: 12px;
            margin-top: 4px;
            color: #666;
            font-weight: normal;
            display: block;
        }

        /* Custom Scrollbar */
        .calendar-day::-webkit-scrollbar {
            width: 4px;
        }

        .calendar-day::-webkit-scrollbar-track {
            background: #f8f9fa;
        }

        .calendar-day::-webkit-scrollbar-thumb {
            background: rgba(123, 0, 0, 0.2);
            border-radius: 2px;
        }

        .calendar-day::-webkit-scrollbar-thumb:hover {
            background: rgba(123, 0, 0, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .calendar {
                display: flex;
                flex-direction: column;
            }

            .calendar-day.empty {
                display: none;
            }

            .calendar-header {
                display: none;
            }

            .calendar-day {
                height: auto;
                min-height: 100px;
                padding: 10px;
                font-size: 14px;
                text-align: left;
                border-radius: 10px;
                margin-bottom: 10px;
            }

            .day-number {
                position: static;
                display: block;
                font-weight: bold;
                margin-bottom: 8px;
                font-size: 16px;
            }

            .event {
                height: auto;
                font-size: 13px;
                margin-top: 8px;
                padding: 6px 10px;
            }

            .header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                font-size: 16px;
            }

            .button {
                font-size: 14px;
                padding: 6px 12px;
            }

            .calendar-container {
                padding: 15px 10px;
            }

            .button.arrow {
                font-size: 20px;
                padding: 6px 12px;
            }

            .month-label {
                flex-grow: 1;
                text-align: center;
                font-size: 16px;
            }
        }

        /* Enhanced Mobile Styles */
        @media (max-width: 480px) {
            .calendar-container {
                width: 95%;
                padding: 10px 8px;
                margin: 10px auto;
            }

            .header {
                padding: 8px;
                font-size: 14px;
            }

            .button {
                font-size: 12px;
                padding: 4px 8px;
            }

            .button.arrow {
                font-size: 16px;
                padding: 4px 8px;
            }

            .month-label {
                font-size: 14px;
            }

            .calendar-day {
                min-height: 80px;
                padding: 8px;
                font-size: 13px;
                margin-bottom: 8px;
            }

            .day-number {
                font-size: 14px;
                margin-bottom: 6px;
            }

            .event {
                font-size: 12px;
                margin-top: 6px;
                padding: 4px 8px;
            }

            .event-details {
                font-size: 11px;
                margin-top: 2px;
            }
        }

        @media (max-width: 360px) {
            .calendar-container {
                width: 98%;
                padding: 8px 5px;
                margin: 8px auto;
            }

            .header {
                padding: 6px;
                font-size: 12px;
            }

            .button {
                font-size: 11px;
                padding: 3px 6px;
            }

            .button.arrow {
                font-size: 14px;
                padding: 3px 6px;
            }

            .month-label {
                font-size: 12px;
            }

            .calendar-day {
                min-height: 70px;
                padding: 6px;
                font-size: 12px;
                margin-bottom: 6px;
            }

            .day-number {
                font-size: 13px;
                margin-bottom: 4px;
            }

            .event {
                font-size: 11px;
                margin-top: 4px;
                padding: 3px 6px;
            }

            .event-details {
                font-size: 10px;
            }
        }

        /* Landscape Mobile Orientation */
        @media (max-width: 768px) and (orientation: landscape) {
            .calendar-container {
                padding: 8px 10px;
                margin: 5px auto;
            }

            .header {
                padding: 6px;
                font-size: 14px;
            }

            .calendar-day {
                min-height: 60px;
                padding: 6px;
                margin-bottom: 6px;
            }

            .event {
                font-size: 12px;
                padding: 3px 6px;
            }
        }
    .day-number {
        font-size: 14px;
        color: #666;
        display: block;
        margin-bottom: 5px;
        text-align: left;
        font-weight: bold;
        white-space: nowrap;
    }
    .mobile-only {
    display: none;
    }

    .desktop-only {
        display: inline;
    }

    @media (max-width: 768px) {
        .mobile-only {
            display: inline;
        }

        .desktop-only {
            display: none;
        }
    }

    .month-label {
    flex-grow: 1;
    text-align: center;
}

    </style>
</head>
<body>
    <div class="calendar-container">
        <div class="header">
            <a class="button arrow" href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">←</a>
            <span class="month-label"><?= strtoupper($monthLabel) . " " . $year ?></span>
            <a class="button arrow" href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">→</a>
        </div>

        <div class="calendar">
        <div class="agenda-view" style="display: none;">
    <?php
    foreach ($events as $date => $dayEvents) {
        echo "<div class='agenda-day'>";
        echo "<h4>" . date('l, F j, Y', strtotime($date)) . "</h4>";
        foreach ($dayEvents as $event) {
            echo "<div class='agenda-event'>";
            echo "<strong>" . htmlspecialchars($event['title']) . "</strong>";
            if ($event['type'] === 'event' && $event['time']) {
                echo "<div class='event-details'>" . date('h:i A', strtotime($event['time']));
                if ($event['location']) {
                    echo " - " . htmlspecialchars($event['location']);
                }
                echo "</div>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    ?>
</div>
            <?php
            // Weekday labels
            $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            foreach ($weekdays as $day) {
                echo "<div class='calendar-header'>$day</div>";
            }

            // Empty cells before the first day of the month
            for ($i = 0; $i < $firstDayOfMonth; $i++) {
                echo "<div class='calendar-day empty'></div>";
            }

            // Calendar day cells
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateKey = sprintf("%04d-%02d-%02d", $year, $month, $day);
                echo "<div class='calendar-day'>";
                $weekdayShort = date('D', strtotime($dateKey));
                echo "<span class='day-number mobile-only'>$day ($weekdayShort)</span>";
                echo "<span class='day-number desktop-only'>$day</span>";
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
                        echo "<div class='event $typeClass $multiDayClass'>";
                        if ($event['is_start']) {
                            echo htmlspecialchars($event['title']);
                            if ($event['type'] === 'event' && $event['time']) {
                                echo "<div class='event-details'>";
                                echo date('h:i A', strtotime($event['time']));
                                if ($event['location']) {
                                    echo " - " . htmlspecialchars($event['location']);
                                }
                                echo "</div>";
                            }
                        }
                        echo "</div>";
                    }
                }
                echo "</div>";
            }

            // Fill the remaining cells to complete the last row
            $totalDisplayed = $firstDayOfMonth + $daysInMonth;
            $remaining = (7 - ($totalDisplayed % 7)) % 7;
            for ($i = 0; $i < $remaining; $i++) {
                echo "<div class='calendar-day empty'></div>";
            }
            ?>
        </div>
    </div>
    <script>
        document.querySelectorAll('.toggle-button').forEach(button => {
            button.addEventListener('click', () => {
                const view = button.dataset.view;
                document.querySelectorAll('.toggle-button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                if (view === 'month') {
                    document.querySelector('.calendar').style.display = 'grid';
                    document.querySelector('.agenda-view').style.display = 'none';
                } else {
                    document.querySelector('.calendar').style.display = 'none';
                    document.querySelector('.agenda-view').style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>