<?php
// ======== CONFIGURATION ========
$useHosted = false; // Set to false when working locally

if ($useHosted) {
    // 🔐 Hosted server settings
    $servername = "localhost";
    $username = "SmartBulletin";
    $password = "CCS@olfuSmartBulletin";
    $dbname = "db_bulletin_board";
} else {
    // 🖥️ Localhost settings
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "db_bulletin_board";
}

// ======== CONNECT TO DATABASE ========
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 to support special characters
$conn->set_charset("utf8mb4");
?>
