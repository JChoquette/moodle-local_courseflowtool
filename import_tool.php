<?php
require_once('../../config.php');
require_once('lib.php');

$courseid = local_courseflowtool_require_course_access();

// Get the course ID from the URL (if relevant).
// $courseid = required_param('courseid', PARAM_INT);

// require_login($courseid);

// // Get the course context.
// $context = context_course::instance($courseid);

// // Check if the user has the capability to view this page.
// require_capability('local/courseflowtool:view', $context);
// require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url(new moodle_url('/local/courseflowtool/import_tool.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Import CourseFlow JSON');
$PAGE->set_heading('Import CourseFlow JSON');

echo $OUTPUT->header();
?>

<textarea id="json-input" rows="10" cols="80" placeholder="Paste your JSON here..."></textarea>
<br>
<button id="import-button">Import from JSON</button>
<div id="response"></div>

<script>
document.getElementById('import-button').addEventListener('click', function() {
    let jsonData = document.getElementById('json-input').value;
    fetch('process_json.php?courseid=<?php echo $courseid; ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ json: jsonData })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('response').innerHTML = data.message;
        window.location.replace(data.redirect);
    })
    .catch(error => {
        document.getElementById('response').innerHTML = 'Error processing JSON';
    });
});
</script>

<?php
echo $OUTPUT->footer();
?>
