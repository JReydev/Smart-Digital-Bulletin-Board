<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';

$conn->query("DELETE FROM announcements");

header("Location: announcement.php");
exit;
