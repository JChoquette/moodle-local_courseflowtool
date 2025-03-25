<?php
/**
 * CourseFlow Import Tool for Moodle
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define event handlers for CourseFlow tool.
//We have one observer to delete everything when the course is deleted.
//We have another to delete mappings when a lesson (or rather its module) is deleted.
//Deleting an outcome does not trigger an event, sadly. This is instead handled in lib.php's local_courseflowtool_cleanup() function.
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