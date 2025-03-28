<?php
/**
 * CourseFlow Import Tool for Moodle
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

//Clear the cache before import just to be safe
$cache = cache::make('local_courseflowtool', 'courseflow_import_data');
$cache->delete('import_data'); // Ensure clean slate before new import

$courseid = local_courseflowtool_require_course_access();

$PAGE->set_url(new moodle_url('/local/courseflowtool/import_tool.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('json_import_title','local_courseflowtool'));
$PAGE->set_heading(get_string('json_import_title','local_courseflowtool'));

echo $OUTPUT->header();
?>

<textarea id="json-input" rows="10" cols="80" placeholder="<?php echo get_string('jsoninput_placeholder', 'local_courseflowtool'); ?>"></textarea>
<br>
<button id="import-button"><?php echo get_string('jsoninput_button','local_courseflowtool'); ?></button>
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
        document.getElementById('response').innerHTML = "<?php echo get_string('json_process_error','local_courseflowtool'); ?>";
    });
});
</script>

<?php
echo $OUTPUT->footer();
?>
