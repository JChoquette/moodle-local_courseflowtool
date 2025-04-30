<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Processes json data entered by the user
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

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || (empty($data['json'])) && empty($data['importurl'])) {
    echo json_encode(['message' => get_string('json_process_invalid', 'local_courseflowtool')]);
    exit;
}


if (!empty($data['importurl'])) {
    // Get the import url (if provided)
    $importurl = $data["importurl"];
    $curl = new curl();
    $response = $curl->get($importurl);
    $json_response = json_decode($response, true);
    $json = $json_response['data_package'];
} elseif (!empty($data['json'])) {
    // Decode the JSON string
    $json = json_decode($data['json'], true);
} else {
    echo json_encode(['message' => get_string('json_process_decode_error', 'local_courseflowtool')]);
    exit;
}

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['message' => get_string('json_process_decode_error', 'local_courseflowtool')]);
    exit;
}

// Store the JSON in the cache for preview
$cache = cache::make('local_courseflowtool', 'courseflow_import_data');
$cache->set('courseflow_import_data', $json);


// Determine whether to apply styles
$usestyle = $data['usestyle'] ? 1 : 0;
// Determine whether or not to associate outcomes
$associateoutcomes = $data['associateoutcomes'] ? 1 : 0;

// Update the database
$record = $DB->get_record('local_courseflowtool_settings', ['courseid' => $courseid]);
$record->courseflow_style = $usestyle;
$record->associate_outcomes = $associateoutcomes;
if(!empty($data['importurl'])) {
    $record-> importurl = $data['importurl'];
}
$DB->update_record('local_courseflowtool_settings', $record);
$cache->set('courseflow_use_style', $usestyle);
$cache->set('courseflow_associate_outcomes', $associateoutcomes);

// Respond with redirect instruction
echo json_encode(['redirect' => 'preview_import.php?courseid=' .$courseid]);
exit;
