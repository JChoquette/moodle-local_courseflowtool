<?php
require_once(__DIR__ . '/../../config.php');

// Get the course ID from the URL (if relevant).
$courseid = optional_param('id', 0, PARAM_INT);

if ($courseid) {
    // Ensure the user is logged in to the course.
    require_login($courseid);

    // Get the course context.
    $context = context_course::instance($courseid);
} else {
    // Default to system context if no course ID is provided.
    require_login();
    $context = context_system::instance();
}

// Check if the user has the capability to view this page.
require_capability('local/jsoncoursebuilder:view', $context);


// Set up the page.
$PAGE->set_url(new moodle_url('/local/courseflowtool/test.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('CourseFlow Tool');
$PAGE->set_heading('CourseFlow Tool');

// Add your test logic here.
echo $OUTPUT->header();
echo '<h2>Testing CourseFlow Tool</h2>';

// Test script: Fetching JSON data (replace the URL with your own).
$curl = new curl();
$response = $curl->get('https://mydalite.org/en/course-flow/workflow/7915/get-public-workflow-data/'); // Example JSON data.
$json_data = json_decode($response, true);

if ($json_data) {
    echo '<pre>' . print_r($json_data, true) . '</pre>';
} else {
    echo '<p style="color:red;">Failed to fetch or decode JSON data.</p>';
}

echo $OUTPUT->footer();