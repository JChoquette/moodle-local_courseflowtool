<?php

defined('MOODLE_INTERNAL') || die();

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
