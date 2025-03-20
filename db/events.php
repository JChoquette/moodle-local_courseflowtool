<?php

// Define event handlers for CourseFlow tool.
$observers = [
    [
        'eventname'   => '\core\event\course_deleted',
        'callback'    => 'local_courseflowtool_observer::handle_course_deleted',
        'includefile' => '/local/courseflowtool/classes/observer.php',
        'priority'    => 1000,
        'internal'    => false,
    ],
    [
        'eventname'   => '\mod_lesson\event\lesson_deleted',
        'callback'    => 'local_courseflowtool_observer::handle_lesson_deleted',
        'includefile' => '/local/courseflowtool/classes/observer.php',
        'priority'    => 1000,
        'internal'    => false,
    ],
    [
        'eventname'   => '\core\event\grade_item_deleted',
        'callback'    => 'local_courseflowtool_observer::handle_outcome_deleted',
        'includefile' => '/local/courseflowtool/classes/observer.php',
        'priority'    => 1000,
        'internal'    => false,
    ],
];