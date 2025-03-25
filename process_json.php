<?php
/**
 * CourseFlow Import Tool for Moodle
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
global $DB;


$courseid = local_courseflowtool_require_course_access();
require_sesskey();

header('Content-Type: application/json');


// Enable error reporting (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents('php://input'), true);


if (!$data || empty($data['json'])) {
    echo json_encode(['message' => get_string('json_process_invalid','local_courseflowtool')]);
    exit;
}

// Decode the JSON string
$json = json_decode($data['json'], true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['message' => get_string('json_process_decode_error','local_courseflowtool')]);
    exit;
}

// Store the JSON in the session for preview
//Is this the right way to do this? Seems like Moodle uses $SESSION as a wrapper for $_SESSION.
$SESSION->courseflow_import_data = $json;

// Check if course settings exist for this course.
$settings = $DB->get_record('local_courseflowtool_settings', ['courseid' => $courseid]);

if (!$settings) {
    // Create default settings if none exist.
    $settings = new stdClass();
    $settings->courseid = $courseid;
    $settings->import_url = ''; // Default value, change if needed.
    $settings->courseflow_style = 0; // Default to no styling.
    $settings->timecreated = time();
    $settings->timemodified = time();

    // Insert the new settings into the database.
    $DB->insert_record('local_courseflowtool_settings', $settings);
}

// Respond with redirect instruction
echo json_encode(['redirect' => 'preview_import.php?courseid=' .$courseid]);
exit;
