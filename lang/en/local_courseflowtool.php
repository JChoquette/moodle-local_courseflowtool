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
 * English language strings
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'CourseFlow Tool';
$string['courseflowtool:view'] = 'View CourseFlow tool plugin';
$string['courseflowtool_test'] = 'Test CourseFlow tool plugin';

// import_tool.php
$string['json_import_title'] = 'Import CourseFlow JSON';
$string['jsoninput_placeholder'] = 'Paste your JSON here...';
$string['jsoninput_button'] = 'Import from JSON';
$string['json_process_error'] = 'Error processing JSON';
$string['usecourseflowstyle'] = 'Use CourseFlow styling for imported lessons';
$string['associateoutcomes'] = 'Associate outcomes with lessons (may create many grade items)';
$string['url_input_label'] = 'Import from URL';
$string['urlinput_button'] = 'Import from URL';

// process_json.php
$string['json_process_invalid'] = 'Invalid JSON data.';
$string['json_process_decode_error'] = 'Error decoding JSON.';

// preview_import.php
$string['preview_title'] = 'Preview CourseFlow Import';
$string['section'] = "Section";
$string['lesson'] = "Lesson";
$string['outcome'] = "Outcome";
$string['sections'] = "Sections";
$string['lessons'] = "Lessons";
$string['outcomes'] = "Outcomes";
$string['label_overwrite'] = 'Overwrite existing topic label (if applicable)';
$string['confirm_import'] = 'Confirm and Import';
$string['no_data_preview'] = 'No data to preview. Please go back and upload JSON first.';
$string['returntocourse'] = 'Return to course';

// finalize_import.php

$string['no_data'] = 'No data found in session.';
$string['error'] = 'Error';
$string['error_finalize'] = 'Error finalizing import';
$string['import_success'] = 'Import completed successfully! Created';

// privacy/provider.php

$string['privacy:metadata'] = 'No data tied to a specific user is stored. Settings are tied to the Moodle course and its CourseFlow origin course, but not to an individual user. Data input by the user is stored only temporarily during the construction of Moodle objects. It is also not tied to a specific user in any way.';

// db/caches.php
$string['cachedef_courseflow_import_data'] = 'CourseFlow Tool import data';
