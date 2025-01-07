<?php
require_once(__DIR__ . '/../../config.php');

// Get the course ID from the URL. 
$courseid = required_param('id', PARAM_INT);

// Ensure the user is logged in and has course access. 
require_login($courseid);

// Get the course context. 
$context = context_course::instance($courseid);

// Check if the user has the capability to view this page.
require_capability('local/jsoncoursebuilder:view', $context);

// Set up the page. 
$PAGE->set_url(new moodle_url('/local/jsoncoursebuilder/course.php', ['id' => $courseid])); $PAGE->set_context($context); $PAGE->set_title('Run JSON Course Builder'); $PAGE->set_heading('Run JSON Course Builder');

// Output header. 
echo $OUTPUT->header(); echo '<h2>Run JSON Course Builder</h2>';

// Example: Add a button to trigger the test.php script. 
$testurl = new moodle_url('/local/jsoncoursebuilder/test.php', ['id' => $courseid]); echo html_writer::tag('p', 'Click the button below to run the JSON Course Builder script:'); echo html_writer::tag('p', html_writer::link($testurl, 'Run JSON Builder', ['class' => 'btn btn-primary']));

// Output footer. 
echo $OUTPUT->footer();


