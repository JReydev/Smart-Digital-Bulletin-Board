<?php
header('Content-Type: application/json');

// Suppress display errors to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Check if database connection file exists
$connectPath = __DIR__ . '/../database/connect.php';
if (!file_exists($connectPath)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database connection file missing',
        'data' => []
    ]);
    exit;
}

include $connectPath;

// Test if $conn variable was created
if (!isset($conn)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection variable not created',
        'data' => []
    ]);
    exit;
}

// Test if connection has errors
if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error,
        'data' => []
    ]);
    exit;
}

try {
    $orgData = [];
    
    // Get faculty data
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
        ORDER BY 
        CASE 
            WHEN f.position LIKE '%College Dean%' THEN 1
            WHEN f.position LIKE '%Program Head%' THEN 2
            WHEN f.position LIKE '%Coordinator%' THEN 3
            WHEN f.position LIKE '%Secretary%' THEN 4
            ELSE 5
        END,
        f.lname ASC, f.fname ASC
        LIMIT 20
    ";
    
    $facultyResult = $conn->query($facultyQuery);
    if ($facultyResult) {
        $administrators = [];
        $regularFaculty = [];
        
        while ($member = $facultyResult->fetch_assoc()) {
            // Check if the member is an administrator
            if (strpos($member['position'], 'College Dean') !== false ||
                strpos($member['position'], 'Program Head') !== false ||
                strpos($member['position'], 'Coordinator') !== false ||
                strpos($member['position'], 'Secretary') !== false) {
                $administrators[] = $member;
            } else {
                $regularFaculty[] = $member;
            }
        }
        
        // Add org chart image as first slide
        // Check which file extension exists
        $org_chart_path = 'images/organization-chart.jpg'; // default
        $possible_extensions = ['jpg', 'jpeg', 'png'];
        foreach ($possible_extensions as $ext) {
            $check_path = __DIR__ . '/../images/organization-chart.' . $ext;
            if (file_exists($check_path)) {
                $org_chart_path = 'images/organization-chart.' . $ext;
                break;
            }
        }
        
        $orgData[] = [
            'type' => 'org_chart_image',
            'title' => 'CCS ORGANIZATIONAL CHART',
            'description' => '',
            'image_path' => $org_chart_path
        ];
        
        // Add individual administrator slides
        if (!empty($administrators)) {
            foreach ($administrators as $admin) {
                $orgData[] = [
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
            $orgData[] = [
                'type' => 'faculty',
                'title' => 'Faculty Members',
                'description' => '',
                'faculty' => $regularFaculty
            ];
        }
    }

    // Get officers data
    $officersQuery = "
        SELECT o.id, o.name AS title, o.position AS description, m.file_path, 
               p.name as partylist_name, p.id as partylist_id
        FROM officers o 
        LEFT JOIN multimedia_content m ON o.multimedia_id = m.id 
        LEFT JOIN partylist p ON o.partylist_id = p.id
        WHERE p.is_selected = 1
        ORDER BY FIELD(o.position, 
            'President', 
            'Internal Vice President', 'External Vice President', 'Secretary', 'Assistant Secretary', 
            'Treasurer', 'Asst. Treasurer', 'Auditor', 'PRO Internal', 'PRO External', 
            '1st Year Representative', '2nd Year Representative', '3rd Year Representative', '4th Year Representative',
            'Marshall Head', 'Senior Multimedia', 'Multimedia Team', 'Senior Esports', 'Esports Team'
        )
    ";
    
    $officersResult = $conn->query($officersQuery);
    if ($officersResult && $officersResult->num_rows > 0) {
        $officers = [];
        $partylistInfo = null;
        
        while ($officer = $officersResult->fetch_assoc()) {
            if ($partylistInfo === null) {
                $partylistInfo = [
                    'id' => $officer['partylist_id'],
                    'name' => $officer['partylist_name']
                ];
            }
            
            $officers[] = $officer;
        }
        
        $orgData[] = [
            'type' => 'officers_tree',
            'title' => 'Student Officers - ' . $partylistInfo['name'],
            'description' => '',
            'officers' => $officers,
            'partylist' => $partylistInfo
        ];
    }

    // Get faculty specializations
    $specializations = [];
    $specializationsQuery = "
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
            CASE 
                WHEN f.position LIKE '%College Dean%' OR 
                     f.position LIKE '%Program Head%' OR 
                     f.position LIKE '%Coordinator%' THEN 'administrator'
                ELSE 'faculty'
            END as type
        FROM faculty f 
        LEFT JOIN multimedia_content m ON f.media_id = m.id 
        WHERE (f.specialization IS NOT NULL AND f.specialization != '')
        ORDER BY 
        CASE 
            WHEN f.position LIKE '%College Dean%' THEN 1
            WHEN f.position LIKE '%Program Head%' THEN 2
            WHEN f.position LIKE '%Coordinator%' THEN 3
            ELSE 4
        END,
        f.lname ASC, f.fname ASC
    ";
    
    $specializationsResult = $conn->query($specializationsQuery);
    if ($specializationsResult) {
        while ($row = $specializationsResult->fetch_assoc()) {
            $specializations[] = [
                'name' => $row['name'],
                'description' => $row['description'],
                'specialization' => $row['specialization'],
                'file_path' => $row['file_path'],
                'type' => $row['type']
            ];
        }
    }

    // Return organization data
    echo json_encode([
        'status' => 'success',
        'data' => $orgData,
        'specializations' => $specializations
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
        'data' => []
    ]);
} catch (Error $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Fatal error: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>
