<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Get the course ID from the URL (if relevant).
$courseid = required_param('id', PARAM_INT);

require_login($courseid);

// Get the course context.
$context = context_course::instance($courseid);

// Check if the user has the capability to view this page.
require_capability('local/courseflowtool:view', $context);
require_capability('moodle/course:manageactivities', $context);


// Set up the page.
$PAGE->set_url(new moodle_url('/local/courseflowtool/test.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('CourseFlow Tool');
$PAGE->set_heading('CourseFlow Tool');

// Add your test logic here.
echo $OUTPUT->header();
echo '<h2>Testing CourseFlow Tool</h2>';

// Test script: Fetching JSON data (replace the URL with your own).
//$curl = new curl();
//$response = $curl->get('https://mydalite.org/en/course-flow/workflow/7915/get-public-workflow-data/'); // Example JSON data.
//$json_data = json_decode($response, true);

// if ($json_data) {
//     echo '<pre>' . print_r($json_data, true) . '</pre>';
// } else {
//     echo '<p style="color:red;">Failed to fetch or decode JSON data.</p>';
//}


$sectionname = "New Auto-Generated Topic";
$labeltext = "<h3>Welcome to this new topic!</h3><p>This content was added by the plugin.</p>";


// Create the topic
$section = local_courseflowtool_create_topic($courseid, $sectionname);

// Add a Text Area (Label) to that topic
//local_courseflowtool_add_label($courseid, $section, $labeltext);

// Add a Lesson to the topic
$lesson = local_courseflowtool_add_lesson($courseid, $section, "A pre-generated lesson", $labeltext,"Page title","Page content");

//$outcome = local_courseflowtool_add_outcome($courseid,"1.1 - an outcome","1.1");


// Redirect back to the course page
//redirect(new moodle_url('/course/view.php', ['id' => $courseid]));

echo $OUTPUT->footer();