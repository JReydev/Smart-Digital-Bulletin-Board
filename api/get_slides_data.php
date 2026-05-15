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

function getItems($conn, $type, $query) {
    $res = mysqli_query($conn, $query);
    
    if (!$res) {
        throw new Exception('Query failed: ' . mysqli_error($conn));
    }
    
    $items = [];
    
    if ($type === 'announcements') {
        $announcementsWithoutMedia = [];
        $announcementsWithMedia = [];
        
        while ($row = mysqli_fetch_assoc($res)) {
            $row['type'] = $type;
            if (empty($row['file_path'])) {
                $announcementsWithoutMedia[] = $row;
            } else {
                $media_files = explode(',', $row['file_path']);
                $announcement_id = $row['id'];
                
                // If there are multiple media files, set no_animation flag
                $no_animation = count($media_files) > 1;
                
                foreach ($media_files as $index => $file_path) {
                    $media_row = $row;
                    $media_row['file_path'] = $file_path;
                    $media_row['extra_info'] = $row['date'];
                    $media_row['no_animation'] = $no_animation && $index > 0; // Only set no_animation for slides after the first one
                    $media_row['announcement_id'] = $announcement_id;
                    $announcementsWithMedia[] = $media_row;
                }
            }
        }
        
        // Group announcements without media into sets of three
        for ($i = 0; $i < count($announcementsWithoutMedia); $i += 3) {
            $group = array_slice($announcementsWithoutMedia, $i, 3);
            if (count($group) > 0) {
                $items[] = [
                    'type' => 'announcements_group',
                    'announcements' => $group
                ];
            }
        }
        
        // Add announcements with media as individual items
        $items = array_merge($items, $announcementsWithMedia);
    } else {
        while ($row = mysqli_fetch_assoc($res)) {
            $row['type'] = $type;
            $items[] = $row;
        }
    }
    
    return $items;
}

function mergeAllItems($conn) {
    $all = [];

    // Get announcements with user type information
    $all = array_merge($all, getItems($conn, 'announcements', "
        SELECT 
            a.id,
            a.title, 
            a.content AS description, 
            GROUP_CONCAT(DISTINCT m.file_path ORDER BY am.display_order) as file_path,
            CASE 
                WHEN a.announcement_date IS NOT NULL THEN
                    CONCAT(
                        DATE_FORMAT(a.announcement_date, '%M %e, %Y'),
                        CASE 
                            WHEN a.announcement_time IS NOT NULL 
                            THEN CONCAT(' at ', TIME_FORMAT(a.announcement_time, '%l:%i %p'))
                            ELSE ''
                        END
                    )
                ELSE 'Tentative - Date to be announced'
            END as date,
            CASE 
                WHEN u.role_id = 1 THEN 'admin'
                WHEN u.role_id = 3 THEN 'officer'
                ELSE 'other'
            END as user_type
        FROM announcements a 
        LEFT JOIN announcement_media am ON a.id = am.announcement_id
        LEFT JOIN multimedia_content m ON am.media_id = m.id 
        LEFT JOIN users u ON a.created_by = u.id
        WHERE (a.announcement_date >= CURDATE() OR a.announcement_date IS NULL)
        AND (a.is_archived = 0 OR a.is_archived IS NULL)
        AND u.role_id IN (1, 3)
        GROUP BY 
            a.id,
            a.title,
            a.content,
            a.announcement_date,
            a.announcement_time,
            u.role_id
        ORDER BY 
            CASE WHEN a.announcement_date IS NULL THEN 1 ELSE 0 END ASC,
            a.announcement_date ASC, 
            a.announcement_time ASC
    "));

    // Get events
    $all = array_merge($all, getItems($conn, 'events', "
        SELECT 
            e.title, 
            e.description, 
            CONCAT(
                DATE_FORMAT(e.date, '%M %e, %Y'),
                ' at ',
                TIME_FORMAT(e.time, '%l:%i %p'),
                CASE WHEN e.location != '' THEN CONCAT(' in ', e.location) ELSE '' END
            ) AS extra_info,
            m.file_path 
        FROM events e 
        LEFT JOIN multimedia_content m ON e.media_id = m.id 
        WHERE CONCAT(e.date, ' ', e.time) >= NOW()
        AND (e.is_archived = 0 OR e.is_archived IS NULL)
        ORDER BY e.date ASC
    "));

    // Add calendar slide
    $currentYear = date('Y');
    $currentMonth = date('n');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
    $firstDayOfMonth = date('w', strtotime("$currentYear-$currentMonth-01"));
    
    // Fetch events for the current month
    $eventsQuery = "
        SELECT 
            e.title, 
            e.date, 
            e.duration, 
            TIME_FORMAT(e.time, '%l:%i %p') as time,
            e.location, 
            'event' as type
        FROM events e 
        WHERE (
            (YEAR(e.date) = $currentYear AND MONTH(e.date) = $currentMonth) OR
            (YEAR(DATE_ADD(e.date, INTERVAL e.duration - 1 DAY)) = $currentYear AND MONTH(DATE_ADD(e.date, INTERVAL e.duration - 1 DAY)) = $currentMonth)
        )
        AND (e.is_archived = 0 OR e.is_archived IS NULL)
        UNION ALL
        SELECT 
            a.title, 
            a.announcement_date as date, 
            1 as duration, 
            a.announcement_time as time, 
            NULL as location, 
            CASE 
                WHEN u.role_id = 1 THEN 'admin_announcement'
                WHEN u.role_id = 3 THEN 'officer_announcement'
                ELSE 'announcement'
            END as type
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE (
            (a.announcement_date IS NOT NULL AND YEAR(a.announcement_date) = $currentYear 
             AND MONTH(a.announcement_date) = $currentMonth AND a.announcement_date >= CURDATE())
            OR a.announcement_date IS NULL
        )
        AND (a.is_archived = 0 OR a.is_archived IS NULL)
        AND u.role_id IN (1, 3)
        ORDER BY date ASC, time ASC
    ";
    $eventsResult = mysqli_query($conn, $eventsQuery);
    
    // Organize events by date
    $events = [];
    // while ($row = mysqli_fetch_assoc($eventsResult)) {
    //     $startDate = date('Y-m-d', strtotime($row['date']));
    //     $duration = isset($row['duration']) ? (int)$row['duration'] : 1;
        
    //     for ($i = 0; $i < $duration; $i++) {
    //         $currentDate = date('Y-m-d', strtotime($startDate . " +$i days"));
    //         if (!isset($events[$currentDate])) {
    //             $events[$currentDate] = [];
    //         }
    //         $row['is_start'] = ($i === 0);
    //         $row['is_end'] = ($i === $duration - 1);
    //         $events[$currentDate][] = $row;
    //     }
    // }
    
    while ($row = mysqli_fetch_assoc($eventsResult)) {
    if (!empty($row['date'])) {
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
    } else {
        // Handle announcements/events without a date
        $row['is_start'] = true;
        $row['is_end'] = true;
        $events['unscheduled'][] = $row;
    }
}


    
    $all[] = [
        'type' => 'calendar',
        'title' => 'Calendar',
        'description' => '',
        'year' => $currentYear,
        'month' => $currentMonth,
        'daysInMonth' => $daysInMonth,
        'firstDayOfMonth' => $firstDayOfMonth,
        'events' => $events
    ];

    // Get faculty members (exclude archived)
    $facultyQuery = "
        SELECT 
            CONCAT(
                CONCAT_WS(' ',
                    NULLIF(f.prefix, ''),
                    f.fname,
                    NULLIF(f.mname, ''),
                    f.lname
                ),
                CASE WHEN f.suffix IS NOT NULL AND f.suffix != '' THEN CONCAT(', ', f.suffix) ELSE '' END
            ) AS title, 
            f.position, 
            f.description,
            f.specialization,
            m.file_path
        FROM faculty f 
        LEFT JOIN multimedia_content m ON f.media_id = m.id 
        WHERE (f.is_archived = 0 OR f.is_archived IS NULL)
        ORDER BY 
        CASE 
            WHEN f.position LIKE '%College Dean%' THEN 1
            WHEN f.position LIKE '%Program Head%' THEN 2
            WHEN f.position LIKE '%Coordinator%' THEN 3
            WHEN f.position LIKE '%SHS Faculty%' AND 
                 (f.position LIKE '%Coordinator%' OR 
                  f.position LIKE '%Adviser%' OR 
                  f.position LIKE '%Head%') THEN 4
            WHEN f.position LIKE '%SHS Faculty%' THEN 5
            WHEN f.position LIKE '%CCS Faculty%' AND 
                 (f.position LIKE '%Coordinator%' OR 
                  f.position LIKE '%Adviser%' OR 
                  f.position LIKE '%Head%') THEN 6
            WHEN f.position LIKE '%CCS Faculty%' THEN 7
            ELSE 8
        END,
        f.fname ASC, f.mname ASC, f.lname ASC
    ";
    $facultyResult = mysqli_query($conn, $facultyQuery);
    
    if (mysqli_num_rows($facultyResult) > 0) {
        $administrators = [];
        $regularFaculty = [];
        
        while ($member = mysqli_fetch_assoc($facultyResult)) {
            // Check if the member is an administrator
            if (strpos($member['position'], 'College Dean') !== false ||
                strpos($member['position'], 'Program Head') !== false ||
                (strpos($member['position'], 'Coordinator') !== false && strpos($member['position'], 'Canvas Coordinator') === false) ||
                strpos($member['position'], 'Secretary') !== false ||
                (strpos($member['position'], 'SHS Faculty') !== false && 
                 ((strpos($member['position'], 'Coordinator') !== false && strpos($member['position'], 'Canvas Coordinator') === false) || 
                  strpos($member['position'], 'Adviser') !== false || 
                  strpos($member['position'], 'Head') !== false)) ||
                (strpos($member['position'], 'CCS Faculty') !== false && 
                 ((strpos($member['position'], 'Coordinator') !== false && strpos($member['position'], 'Canvas Coordinator') === false) || 
                  strpos($member['position'], 'Adviser') !== false || 
                  strpos($member['position'], 'Head') !== false))) {
                $administrators[] = $member;
            } else {
                $regularFaculty[] = $member;
            }
        }
        
        // Add org chart image as first slide
        // Check which file extension exists
        $org_chart_path = './../images/organization-chart.jpg'; // default
        $possible_extensions = ['jpg', 'jpeg', 'png'];
        foreach ($possible_extensions as $ext) {
            $check_path = __DIR__ . '/../images/organization-chart.' . $ext;
            if (file_exists($check_path)) {
                $org_chart_path = './../images/organization-chart.' . $ext;
                break;
            }
        }
        
        $all[] = [
            'type' => 'org_chart_image',
            'title' => 'CCS ORGANIZATIONAL CHART',
            'description' => '',
            'image_path' => $org_chart_path
        ];
        
        // Add individual administrator slides
        if (!empty($administrators)) {
            foreach ($administrators as $admin) {
                $all[] = [
                    'type' => 'individual_admin',
                    'title' => $admin['title'],
                    'position' => $admin['position'],
                    'description' => $admin['description'],
                    'specialization' => $admin['specialization'],
                    'file_path' => $admin['file_path']
                ];
            }
        }
        
        // Add regular faculty slide if there are any
        if (!empty($regularFaculty)) {
            $all[] = [
                'type' => 'faculty',
                'title' => 'Faculty Members',
                'description' => '',
                'faculty' => $regularFaculty
            ];
        }
    }

    // Get officers (exclude archived)
    $officersQuery = "
        SELECT o.id, o.name AS title, o.position AS description, m.file_path, 
               p.name as partylist_name, p.id as partylist_id
        FROM officers o 
        LEFT JOIN multimedia_content m ON o.multimedia_id = m.id 
        LEFT JOIN partylist p ON o.partylist_id = p.id
        WHERE p.is_selected = 1 
        AND (o.is_archived = 0 OR o.is_archived IS NULL)
        AND (p.is_archived = 0 OR p.is_archived IS NULL)
        ORDER BY FIELD(o.position, 
            'President', 
            'Internal Vice President', 'External Vice President', 'Secretary', 'Assistant Secretary', 
            'Treasurer', 'Asst. Treasurer', 'Auditor', 'PRO Internal', 'PRO External', 
            '1st Year Representative', '2nd Year Representative', '3rd Year Representative', '4th Year Representative',
            'Marshall Head', 'Senior Multimedia', 'Multimedia Team', 'Senior Esports', 'Esports Team'
        )
    ";
    
    $officersResult = mysqli_query($conn, $officersQuery);
    
    if (mysqli_num_rows($officersResult) > 0) {
        $officers = [];
        $partylistInfo = null;
        
        while ($officer = mysqli_fetch_assoc($officersResult)) {
            if ($partylistInfo === null) {
                $partylistInfo = [
                    'id' => $officer['partylist_id'],
                    'name' => $officer['partylist_name']
                ];
            }
            
            $officers[] = $officer;
        }
        
        $all[] = [
            'type' => 'officers_tree',
            'title' => 'Student Officers - ' . $partylistInfo['name'],
            'description' => '',
            'officers' => $officers,
            'partylist' => $partylistInfo
        ];
    }

    return $all;
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
    
    // Get active news items for marquee (not expired, not archived)
    $newsQuery = "
        SELECT title, content, expires_at, auto_expire
        FROM news_update 
        WHERE is_active = 1 
        AND (is_archived = 0 OR is_archived IS NULL)
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
                'auto_expire' => $row['auto_expire']
            ];
        }
    }
    
    $result['news'] = $news;
    return $result;
}

function getFacultySpecializations($conn) {
    // Get administrators first (ordered by position hierarchy)
    $adminsQuery = "
        SELECT 
            CONCAT(
                CONCAT_WS(' ',
                    NULLIF(f.prefix, ''),
                    f.fname,
                    NULLIF(f.mname, ''),
                    f.lname
                ),
                CASE WHEN f.suffix IS NOT NULL AND f.suffix != '' THEN CONCAT(', ', f.suffix) ELSE '' END
            ) AS name,
            f.position,
            f.description,
            f.specialization,
            m.file_path,
            'administrator' as type
        FROM faculty f 
        LEFT JOIN multimedia_content m ON f.media_id = m.id 
        WHERE (f.specialization IS NOT NULL AND f.specialization != '')
        AND (f.is_archived = 0 OR f.is_archived IS NULL)
        AND (
            f.position LIKE '%College Dean%' OR
            f.position LIKE '%Program Head%' OR
            (f.position LIKE '%Coordinator%' AND f.position NOT LIKE '%Canvas Coordinator%') OR
            (f.position LIKE '%SHS Faculty%' AND 
             ((f.position LIKE '%Coordinator%' AND f.position NOT LIKE '%Canvas Coordinator%') OR 
              f.position LIKE '%Adviser%' OR 
              f.position LIKE '%Head%')) OR
            (f.position LIKE '%CCS Faculty%' AND 
             ((f.position LIKE '%Coordinator%' AND f.position NOT LIKE '%Canvas Coordinator%') OR 
              f.position LIKE '%Adviser%' OR 
              f.position LIKE '%Head%'))
        )
        ORDER BY 
        CASE 
            WHEN f.position LIKE '%College Dean%' THEN 1
            WHEN f.position LIKE '%Program Head%' THEN 2
            WHEN f.position LIKE '%Coordinator%' THEN 3
            WHEN f.position LIKE '%SHS Faculty%' AND 
                 (f.position LIKE '%Coordinator%' OR 
                  f.position LIKE '%Adviser%' OR 
                  f.position LIKE '%Head%') THEN 4
            WHEN f.position LIKE '%CCS Faculty%' AND 
                 (f.position LIKE '%Coordinator%' OR 
                  f.position LIKE '%Adviser%' OR 
                  f.position LIKE '%Head%') THEN 5
            ELSE 6
        END,
        f.fname ASC, f.mname ASC, f.lname ASC
    ";
    
    // Get regular faculty (ordered alphabetically)
    $facultyQuery = "
        SELECT 
            CONCAT(
                CONCAT_WS(' ',
                    NULLIF(f.prefix, ''),
                    f.fname,
                    NULLIF(f.mname, ''),
                    f.lname
                ),
                CASE WHEN f.suffix IS NOT NULL AND f.suffix != '' THEN CONCAT(', ', f.suffix) ELSE '' END
            ) AS name,
            f.position,
            f.description,
            f.specialization,
            m.file_path,
            'faculty' as type
        FROM faculty f 
        LEFT JOIN multimedia_content m ON f.media_id = m.id 
        WHERE (f.specialization IS NOT NULL AND f.specialization != '')
        AND (f.is_archived = 0 OR f.is_archived IS NULL)
        AND NOT (
            f.position LIKE '%College Dean%' OR
            f.position LIKE '%Program Head%' OR
            (f.position LIKE '%Coordinator%' AND f.position NOT LIKE '%Canvas Coordinator%') OR
            (f.position LIKE '%SHS Faculty%' AND 
             ((f.position LIKE '%Coordinator%' AND f.position NOT LIKE '%Canvas Coordinator%') OR 
              f.position LIKE '%Adviser%' OR 
              f.position LIKE '%Head%')) OR
            (f.position LIKE '%CCS Faculty%' AND 
             ((f.position LIKE '%Coordinator%' AND f.position NOT LIKE '%Canvas Coordinator%') OR 
              f.position LIKE '%Adviser%' OR 
              f.position LIKE '%Head%'))
        )
        ORDER BY 
        CASE 
            WHEN f.position LIKE '%College Dean%' THEN 1
            WHEN f.position LIKE '%Program Head%' THEN 2
            WHEN f.position LIKE '%Coordinator%' THEN 3
            WHEN f.position LIKE '%SHS Faculty%' AND 
                 (f.position LIKE '%Coordinator%' OR 
                  f.position LIKE '%Adviser%' OR 
                  f.position LIKE '%Head%') THEN 4
            WHEN f.position LIKE '%SHS Faculty%' THEN 5
            WHEN f.position LIKE '%CCS Faculty%' AND 
                 (f.position LIKE '%Coordinator%' OR 
                  f.position LIKE '%Adviser%' OR 
                  f.position LIKE '%Head%') THEN 6
            WHEN f.position LIKE '%CCS Faculty%' THEN 7
            ELSE 8
        END,
        f.fname ASC, f.mname ASC, f.lname ASC
    ";
    
    $specializations = [];
    
    // Add administrators first
    $adminsResult = mysqli_query($conn, $adminsQuery);
    if ($adminsResult && mysqli_num_rows($adminsResult) > 0) {
        while ($row = mysqli_fetch_assoc($adminsResult)) {
            $specializations[] = [
                'name' => $row['name'],
                'position' => $row['position'],
                'description' => $row['description'],
                'specialization' => $row['specialization'],
                'file_path' => $row['file_path'],
                'type' => $row['type']
            ];
        }
    }
    
    // Add regular faculty
    $facultyResult = mysqli_query($conn, $facultyQuery);
    if ($facultyResult && mysqli_num_rows($facultyResult) > 0) {
        while ($row = mysqli_fetch_assoc($facultyResult)) {
            $specializations[] = [
                'name' => $row['name'],
                'position' => $row['position'],
                'description' => $row['description'],
                'specialization' => $row['specialization'],
                'file_path' => $row['file_path'],
                'type' => $row['type']
            ];
        }
    }
    
    return $specializations;
}

// Handle the request
try {
    $items = mergeAllItems($conn);
    $welcomeAndNews = getWelcomeAndNews($conn);
    $specializations = getFacultySpecializations($conn);
    
    // Previous Officers: fetch officers from non-selected partylists
    $previousOfficersQuery = "
        SELECT 
            o.id,
            o.name AS title,
            o.position AS description,
            m.file_path,
            p.name as partylist_name,
            p.id as partylist_id
        FROM officers o
        LEFT JOIN multimedia_content m ON o.multimedia_id = m.id
        LEFT JOIN partylist p ON o.partylist_id = p.id
        WHERE p.is_selected = 0
          AND (o.is_archived = 0 OR o.is_archived IS NULL)
          AND (p.is_archived = 0 OR p.is_archived IS NULL)
          AND p.id = (
            SELECT MAX(pp.id) FROM partylist pp WHERE pp.is_selected = 0 AND (pp.is_archived = 0 OR pp.is_archived IS NULL)
          )
        ORDER BY FIELD(o.position,
            'President',
            'Internal Vice President', 'External Vice President', 'Secretary', 'Assistant Secretary',
            'Treasurer', 'Asst. Treasurer', 'Auditor', 'PRO Internal', 'PRO External',
            '1st Year Representative', '2nd Year Representative', '3rd Year Representative', '4th Year Representative',
            'Marshall Head', 'Senior Multimedia', 'Multimedia Team', 'Senior Esports', 'Esports Team'
        ), o.id ASC
    ";
    $prevResult = mysqli_query($conn, $previousOfficersQuery);
    $previousOfficers = [];
    if ($prevResult && mysqli_num_rows($prevResult) > 0) {
        while ($row = mysqli_fetch_assoc($prevResult)) {
            $previousOfficers[] = $row;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $items,
        'welcome_and_news' => $welcomeAndNews,
        'specializations' => $specializations,
        'previous_officers' => $previousOfficers
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 