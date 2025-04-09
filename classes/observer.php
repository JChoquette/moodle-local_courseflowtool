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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Handles database cleaning when courses or lessons are deleted
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_courseflowtool_observer
 *
 * Handles event observers for the CourseFlow tool.
 *
 * This class defines methods that respond to course deletion
 * and course module deletion events to remove associated items from
 * the courseflow tool tables
 * 
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class local_courseflowtool_observer {

    /**
     * Handle course deletion: clean up course settings and mappings.
     *
     * @param \core\event\course_deleted $event
     */
    public static function handle_course_deleted(\core\event\course_deleted $event) {
        global $DB;

        $courseid = $event->objectid;

        // Delete related course settings.
        $DB->delete_records('local_courseflowtool_settings', ['courseid' => $courseid]);

        // Delete related lesson and outcome mappings.
        $DB->delete_records('local_courseflowtool_map', ['courseid' => $courseid]);
    }

    /**
     * Handle lesson deletion: clean up mappings in local_courseflowtool_map.
     *
     * @param \mod_lesson\event\lesson_deleted $event
     */
    public static function handle_lesson_deleted(\core\event\course_module_deleted $event) {
        global $DB;

        //Get the "lesson" module ID from 'modules' table
        $lesson_module = $DB->get_record('modules', ['name' => 'lesson']);
        if (!$lesson_module) {
            return null;
        }

        //Get the course module deletion snapshot
        $cm_snapshot = $event->get_record_snapshot('course_modules', $event->objectid);

        if($cm_snapshot && $cm_snapshot->module == $lesson_module->id){
            $lessonid = $cm_snapshot->instance;
            // Delete mapping for the deleted lesson.
            $DB->delete_records('local_courseflowtool_map', ['moodle_lessonid' => $lessonid, 'type' => 'lesson', 'courseid' => $event->courseid]);
        }


    }

}
