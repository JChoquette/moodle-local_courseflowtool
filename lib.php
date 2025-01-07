<?php
defined('MOODLE_INTERNAL') || die();

function local_courseflowtool_extend_settings_navigation($settingsnav, $context) {
    if ($context->contextlevel == CONTEXT_COURSE) {
        // Check if the user has the required capability.
        if (has_capability('local/courseflowtool:view', $context)) {
            $url = new moodle_url('/local/courseflowtool/course.php', ['id' => $context->instanceid]);
            $node = navigation_node::create(
                'CourseFlow Tool',
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'courseflowtool',
                new pix_icon('i/settings', '')
            );
            echo "Trying to add a node";
            $settingsnav->add_node($node);
        }
    }
}