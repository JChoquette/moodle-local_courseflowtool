<?php
require_once('../../config.php');
require_once('lib.php');

$courseid = local_courseflowtool_require_course_access();

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
    fetch('process_json.php?courseid=<?php echo $courseid; ?>&sesskey=<?php echo sesskey(); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ json: jsonData })
    })
    .then(response => {
        // console.log(response.text());
        return response.json();
    })
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
