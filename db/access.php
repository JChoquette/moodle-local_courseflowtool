<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/courseflowtool:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW, // Allow teachers to access.
        ],
    ],
];