<?php
// This script adds authentication to all module pages
// Run this in the command line: php add_auth_to_modules.php

$directories = [
    'Modules/Calendar',
    'Modules/Announcement', 
    'Modules/Events',
    'Modules/Faculty',
    'Modules/Officers',
    'Modules/Account'
];

$authIncludeLine = "include_once __DIR__ . '/../include/auth_required.php';\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "Directory $dir does not exist.\n";
        continue;
    }
    
    $files = glob("$dir/*.php");
    foreach ($files as $file) {
        echo "Processing $file...\n";
        
        $content = file_get_contents($file);
        
        // Check if already has auth include
        if (strpos($content, 'auth_required.php') !== false) {
            echo "  Already has auth include.\n";
            continue;
        }
        
        // Find the first <?php tag
        $phpTagPos = strpos($content, '<?php');
        if ($phpTagPos === false) {
            echo "  No PHP tag found.\n";
            continue;
        }
        
        // Find the position after the PHP tag
        $insertPos = $phpTagPos + 5;
        
        // Get the part after the PHP tag to look for session_start
        $afterPhp = substr($content, $insertPos);
        
        // Check if there's a session_start
        if (preg_match('/\s*session_start\(\);/', $afterPhp, $matches, PREG_OFFSET_CAPTURE)) {
            // Remove session_start and add our include
            $sessionStartPos = $insertPos + $matches[0][1];
            $sessionStartLength = strlen($matches[0][0]);
            $content = substr_replace($content, "\n" . $authIncludeLine, $sessionStartPos, $sessionStartLength);
        } else {
            // Insert after the PHP tag
            $content = substr_replace($content, "\n" . $authIncludeLine, $insertPos, 0);
        }
        
        // Write back to the file
        file_put_contents($file, $content);
        echo "  Added auth include.\n";
    }
}

echo "Completed adding authentication to module pages.\n";
?> 