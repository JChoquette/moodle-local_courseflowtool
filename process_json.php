<?php
require_once('../../config.php');
require_once('lib.php');

$courseid = local_courseflowtool_require_course_access();

header('Content-Type: application/json');


// Enable error reporting (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents('php://input'), true);


if (!$data || empty($data['json'])) {
    echo json_encode(['message' => 'Invalid JSON data.']);
    exit;
}

// Decode the JSON string
$json = json_decode($data['json'], true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['message' => 'Error decoding JSON.']);
    exit;
}

// Store the JSON in the session for preview
$_SESSION['courseflow_import_data'] = $json;

// Respond with redirect instruction
echo json_encode(['redirect' => 'preview_import.php?courseid=' .$courseid]);
exit;
