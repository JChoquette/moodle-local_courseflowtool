<?php

defined('MOODLE_INTERNAL') || die();

//TODO: Test these to make sure they are working

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
     * Handle lesson deletion: clean up mappings in courseflowtool_map.
     *
     * @param \mod_lesson\event\lesson_deleted $event
     */
    public static function handle_lesson_deleted(\mod_lesson\event\lesson_deleted $event) {
        global $DB;

        $lessonid = $event->objectid;

        // Delete mapping for the deleted lesson.
        $DB->delete_records('local_courseflowtool_map', ['moodleid' => $lessonid, 'type' => 'lesson']);
    }

    /**
     * Handle outcome deletion: clean up mappings in courseflowtool_map.
     *
     * @param \core\event\grade_item_deleted $event
     */
    public static function handle_outcome_deleted(\core\event\grade_item_deleted $event) {
        global $DB;

        $outcomeid = $event->objectid;

        // Delete mapping for the deleted outcome.
        $DB->delete_records('local_courseflowtool_map', ['moodleid' => $outcomeid, 'type' => 'outcome']);
    }
}
