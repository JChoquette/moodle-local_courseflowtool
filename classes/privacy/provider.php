<?php
/**
 * CourseFlow Import Tool for Moodle
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseflowtool\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements
        // This plugin does store personal user data.
        \core_privacy\local\metadata\provider {

    public static function get_metadata(collection $collection): collection {

        // Information about user settings stored in local_courseflowtool_settings.
        $collection->add_database_table(
            'local_courseflowtool_settings',
            [
                'courseid' => 'privacy:metadata:courseid',
                'importurl' => 'privacy:metadata:importurl',
                'courseflow_style' => 'privacy:metadata:courseflow_style'
            ],
            'privacy:metadata:local_courseflowtool_settings'
        );

        // Lessons and outcomes linked to user entries.
        $collection->add_database_table(
            'local_courseflowtool_map',
            [
                'courseid' => 'privacy:metadata:courseid',
                'courseflow_id' => 'privacy:metadata:courseflowid',
                'moodle_lessonid' => 'privacy:metadata:moodlelessonid',
                'moodle_outcomeid' => 'privacy:metadata:moodleoutcomeid',
                'type' => 'privacy:metadata:type'
            ],
            'privacy:metadata:local_courseflowtool_map'
        );

        return $collection;
    }
}