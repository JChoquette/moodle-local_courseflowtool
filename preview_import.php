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
 * Displays a preview of the data prior to finalizing the import
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');
global $DB;

$courseid = local_courseflowtool_require_course_access();

$PAGE->set_url(new moodle_url('/local/courseflowtool/preview_import.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('preview_title', 'local_courseflowtool'));
$PAGE->set_heading(get_string('preview_title', 'local_courseflowtool'));

echo $OUTPUT->header();

// Ensure the data exists in the cache
$cache = cache::make('local_courseflowtool', 'courseflow_import_data');
$jsondata = $cache->get('courseflow_import_data') ?? null;

if (!$jsondata) {
    echo '<p>'.get_string('no_data_preview', 'local_courseflowtool').'</p>';
    echo $OUTPUT->footer();
    exit;
}

// The following code checks for existing items, adding to the json so we can display a warning if it will be updated.
$existingoutcomes = [];
$existinglessons = [];

$records = $DB->get_records('local_courseflowtool_map', ['courseid' => $courseid]);

// Create arrays for the existing outcomes and lessons
foreach ($records as $record) {
    if ($record->type === 'outcome') {
        $existingoutcomes[] = $record->courseflow_id;
    } else if ($record->type === 'lesson') {
        $existinglessons[] = $record->courseflow_id;
    }
}

// Check the lessons.
foreach ($jsondata["sections"] as &$section) {
    foreach ($section["lessons"] as &$lesson) {
        $lesson["exists"] = in_array($lesson['id'],$existinglessons);
    }
}
// Check the outcomes.
foreach ($jsondata["outcomes"] as &$outcome) {
    $outcome["exists"] = in_array($outcome['id'],$existingoutcomes);
}

$renderable = new \local_courseflowtool\output\preview_import($jsondata, sesskey(), $courseid);
echo $OUTPUT->render($renderable);
echo $OUTPUT->footer();

