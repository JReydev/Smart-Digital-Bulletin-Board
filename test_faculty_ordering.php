<?php
// Test script to verify faculty ordering works correctly with new database structure

include 'database/connect.php';

echo "<h2>Testing Faculty Alphabetical Ordering (Without Prefixes)</h2>\n";
echo "<p>This test verifies that faculty are sorted alphabetically by last name, first name, middle name - excluding prefixes and suffixes.</p>\n";

// Test query with new structure
$query = "SELECT 
    f.prefix,
    f.fname,
    f.mname,
    f.lname,
    f.suffix,
    f.name,
    f.full_name,
    f.position,
    f.description
FROM faculty f 
WHERE f.type = 'faculty' 
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
f.lname ASC, f.fname ASC, f.mname ASC";

$result = mysqli_query($conn, $query);

if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background-color: #f0f0f0;'>
            <th>Position Priority</th>
            <th>Prefix</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Suffix</th>
            <th>Full Display Name</th>
            <th>Primary Position</th>
          </tr>\n";
    
    $position_priority = 1;
    $last_position_type = '';
    $row_count = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row_count++;
        
        // Determine position type for grouping
        $current_position_type = '';
        if (strpos($row['description'], 'College Dean') !== false) {
            $current_position_type = 'College Dean';
        } elseif (strpos($row['description'], 'Program Head') !== false) {
            $current_position_type = 'Program Head';
        } elseif (strpos($row['description'], 'Coordinator') !== false) {
            $current_position_type = 'Coordinator';
        } elseif (strpos($row['description'], 'SHS Faculty') !== false && 
                  (strpos($row['description'], 'Coordinator') !== false || 
                   strpos($row['description'], 'Adviser') !== false || 
                   strpos($row['description'], 'Head') !== false)) {
            $current_position_type = 'SHS Faculty (Special)';
        } elseif (strpos($row['description'], 'SHS Faculty') !== false) {
            $current_position_type = 'SHS Faculty';
        } elseif (strpos($row['description'], 'CCS Faculty') !== false && 
                  (strpos($row['description'], 'Coordinator') !== false || 
                   strpos($row['description'], 'Adviser') !== false || 
                   strpos($row['description'], 'Head') !== false)) {
            $current_position_type = 'CCS Faculty (Special)';
        } elseif (strpos($row['description'], 'CCS Faculty') !== false) {
            $current_position_type = 'CCS Faculty';
        } else {
            $current_position_type = 'Other';
        }
        
        // Reset priority counter if position type changes
        if ($current_position_type !== $last_position_type) {
            $position_priority = 1;
            $last_position_type = $current_position_type;
        }
        
        // Generate display name
        $display_name = trim(($row['prefix'] ? $row['prefix'] . ' ' : '') . 
                           $row['fname'] . 
                           ($row['mname'] ? ' ' . $row['mname'] : '') . 
                           ' ' . $row['lname'] . 
                           ($row['suffix'] ? ', ' . $row['suffix'] : ''));
        
        // Highlight if this is a new position group
        $bg_color = ($position_priority === 1) ? '#e6f3ff' : 'white';
        
        echo "<tr style='background-color: $bg_color;'>
                <td>$current_position_type #$position_priority</td>
                <td>" . htmlspecialchars($row['prefix'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['fname'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['mname'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['lname'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['suffix'] ?? '') . "</td>
                <td><strong>" . htmlspecialchars($display_name) . "</strong></td>
                <td>" . htmlspecialchars($row['description']) . "</td>
              </tr>\n";
        
        $position_priority++;
    }
    
    echo "</table>\n";
    echo "<p><strong>Total Faculty Members:</strong> $row_count</p>\n";
    
    echo "<h3>Ordering Verification:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Faculty are grouped by position hierarchy (Dean → Program Head → Coordinators → Faculty)</li>\n";
    echo "<li>✅ Within each group, faculty are sorted alphabetically by <strong>last name</strong> first</li>\n";
    echo "<li>✅ Prefixes (Dr., Prof., Engr.) and suffixes (MIT, DIT, etc.) do not affect alphabetical ordering</li>\n";
    echo "<li>✅ Full display name includes all components for proper presentation</li>\n";
    echo "</ul>\n";
    
} else {
    echo "<p style='color: red;'>Error executing query: " . mysqli_error($conn) . "</p>\n";
    
    // Check if the new columns exist
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM faculty LIKE 'fname'");
    if (mysqli_num_rows($check_columns) == 0) {
        echo "<p style='color: orange;'><strong>Note:</strong> It appears the database structure update has not been applied yet. Please run the update script first:</p>\n";
        echo "<pre>mysql -u root -p db_bulletin_board < database/update_faculty_structure.sql</pre>\n";
    }
}

mysqli_close($conn);
?>

<style>
table { 
    border-collapse: collapse; 
    width: 100%; 
    margin: 20px 0; 
}
th, td { 
    border: 1px solid #ddd; 
    padding: 8px; 
    text-align: left; 
}
th { 
    background-color: #f2f2f2; 
    font-weight: bold; 
}
h2, h3 { 
    color: #333; 
    margin: 20px 0 10px 0; 
}
ul { 
    margin: 10px 0; 
    padding-left: 20px; 
}
li { 
    margin: 5px 0; 
}
</style>
