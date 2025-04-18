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
 * Finalizes the import, calling lib.php functions to do so
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$courseid = local_courseflowtool_require_course_access();
require_sesskey();

header('Content-Type: application/json');

ob_start();

/**
 * Processes the imported JSON data and generates lessons, outcomes, and sections for the specified course.
 *
 * @param string $jsondata The JSON data representing the course structure from CourseFlow.
 * @param int $courseid The ID of the course where the items will be imported.
 * @param array $selectedlessons An array of lesson IDs to be imported.
 * @param array $selectedoutcomes An array of outcome IDs to be imported.
 * @param array $selectedsections An array of section IDs to be imported.
 * @return array An array containing status (success or error) and a message to be displayed to the user
 */
function local_courseflowtool_process_import($jsondata, $courseid, $selectedlessons, $selectedoutcomes, $selectedsections, $usestyle) {
    require_once(__DIR__ . '/lib.php');

    $outcomesmade = 0;
    $sectionsmade = 0;
    $lessonsmade = 0;

    local_courseflowtool_cleanup($courseid);

    foreach ($jsondata['outcomes'] as $outcome) {
        if (in_array($outcome["id"], $selectedoutcomes)) {

            local_courseflowtool_add_outcome(
                $courseid,
                $outcome['fullname'],
                $outcome['shortname'],
                $outcome['id']
            );
            $outcomesmade++;
        }
    }

    // Create Sections and Lessons
    foreach ($jsondata['sections'] as $sectionindex => $sectiondata) {

        /* Create the section. Unlike lessons or outcomes, this
        needs to know if the section has been selected directly,
        since we need to create a new section anyways even if the
        user has deselected it in the case where we don't have
        enough sections. We update section_index+1 because the
        top (0th) section in Moodle is usually for
        announcements/course info, it isn't one of the "topics" */
        $updatesection = in_array($sectionindex, $selectedsections);
        $section = local_courseflowtool_create_topic(
            $courseid,
            $sectiondata['title'],
            $sectionindex + 1,
            $updatesection

        );

        $sectionsmade++;

        foreach ($sectiondata['lessons'] as $lessondata) {
            if (in_array($lessondata["id"], $selectedlessons)) {

                local_courseflowtool_add_lesson(
                    $courseid,
                    $section,
                    $lessondata['lessonname'],
                    $lessondata['lessonintro'],
                    $lessondata['pagetitle'],
                    $lessondata['pagecontents'],
                    $lessondata['outcomes'],
                    $lessondata["id"],
                    $lessondata["lessontype_display"],
                    $lessondata["lessontype"],
                    $lessondata["colour"],
                    $usestyle,
                );

                $lessonsmade++;
            }
        }
    }

    // Clear course change caches so users see changes like section renaming
    course_modinfo::purge_course_cache($courseid);

    return ['status' => 'success', 'message' =>
        get_string('import_success', 'local_courseflowtool').' '.$outcomesmade.' '.
        get_string('outcomes', 'local_courseflowtool').', '.$sectionsmade.' '.
        get_string('sections', 'local_courseflowtool').', '.$lessonsmade.' '.
        get_string('lessons', 'local_courseflowtool').'.'];

}

// Ensure the data exists in the cache
$cache = cache::make('local_courseflowtool', 'courseflow_import_data');
$jsondata = $cache->get('courseflow_import_data') ?? null;
$usestyle = $cache->get('courseflow_use_style') ?? false;

// Delete the import data from the cache (optional)
// Currently commented because it's nice to be able to hit refresh on the import and be able to re-import data, but if we want to change this later we can uncomment
// $cache->delete('import_data');

if (!$jsondata) {
    ob_end_clean();
    echo json_encode(['message' => get_string('no_data', 'local_courseflowtool')]);
    exit;
}


$selectedlessons = optional_param_array('lessons', [], PARAM_INT);
$selectedoutcomes = optional_param_array('outcomes', [], PARAM_INT);
$selectedsections = optional_param_array('sections', [], PARAM_INT);

// Run the import and get the result
$result = local_courseflowtool_process_import($jsondata, $courseid, $selectedlessons, $selectedoutcomes, $selectedsections, $usestyle);


// Return JSON response
ob_end_clean();
echo json_encode($result);
exit();
