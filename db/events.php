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
 * Event handlers for when courses and lessons are deleted
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define event handlers for CourseFlow tool.
// We have one observer to delete everything when the course is deleted.
// We have another to delete mappings when a lesson (or rather its module) is deleted.
// Deleting an outcome does not trigger an event, sadly. This is instead handled in lib.php's local_courseflowtool_cleanup() function.
$observers = [
    [
        'eventname'   => '\core\event\course_deleted',
        'callback'    => 'local_courseflowtool_observer::handle_course_deleted',
        'includefile' => '/local/courseflowtool/classes/observer.php',
    ],
    [
        'eventname'   => '\core\event\course_module_deleted',
        'callback'    => 'local_courseflowtool_observer::handle_lesson_deleted',
        'includefile' => '/local/courseflowtool/classes/observer.php',
    ],
];
