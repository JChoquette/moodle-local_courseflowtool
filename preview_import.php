<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Displays a preview of the data prior to finalizing the import
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');
$courseid = local_courseflowtool_require_course_access();

$PAGE->set_url(new moodle_url('/local/courseflowtool/preview_import.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('preview_title','local_courseflowtool'));
$PAGE->set_heading(get_string('preview_title','local_courseflowtool'));

echo $OUTPUT->header();

//Ensure the data exists in the cache
$cache = cache::make('local_courseflowtool', 'courseflow_import_data');
$json_data = $cache->get('courseflow_import_data') ?? null;

if (!$json_data) {
    echo '<p>'.get_string('no_data_preview','local_courseflowtool').'</p>';
    echo $OUTPUT->footer();
    exit;
}

//Create the form with the preview to allow the user to determine what should/shouldn't be imported
echo '<form id="import-form">';
echo '<h3>'.get_string('lessons','local_courseflowtool').'</h3>';
foreach ($json_data['sections'] as $section_index => $section) {
    //Sections are based on index, we don't care about their id we just match them index by index
    echo "<h4>".get_string('section','local_courseflowtool').": {$section['title']}</h4>";
    $checkbox_id = "section_{$section_index}";
    echo "<input type='checkbox' id='$checkbox_id' name='sections[]' value='$section_index' checked> ";
    echo "<label for='$checkbox_id'>".get_string('label_overwrite','local_courseflowtool')."</label><br>";
    foreach ($section['lessons'] as $lesson_index => $lesson) {
        //Lessons are based on id instead, we want to match up their courseflow id to their moodle id to determine whether/how to update them
        $checkbox_id = "lesson_{$lesson["id"]}";
        echo "<input type='checkbox' id='$checkbox_id' name='lessons[]' value='{$lesson["id"]}' checked> ";
        echo "<label for='$checkbox_id'>{$lesson['lessonname']}</label><br>";
    }
}
echo '<h3>'.get_string('outcomes','local_courseflowtool').'</h3>';
foreach ($json_data['outcomes'] as $outcome_index => $outcome) {
    //Outcomes are matched to their courseflow id as well
    $checkbox_id = "outcome_{$outcome["id"]}";
    echo "<input type='checkbox' id='$checkbox_id' name='outcomes[]' value='{$outcome["id"]}' checked> ";
    echo "<label for='$checkbox_id'>{$outcome['shortname']} - {$outcome['fullname']}</label><br>";
}
echo '<br><button type="button" id="confirm-import">'.get_string('confirm_import','local_courseflowtool').'</button>';
echo '</form>';

?>

<script>
document.getElementById('confirm-import').addEventListener('click', function() {
    let formData = new FormData(document.getElementById('import-form'));

    fetch('finalize_import.php?courseid=<?php echo $courseid; ?>&sesskey=<?php echo sesskey(); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // response_text = response.text();
        // console.log(response_text);
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            document.body.innerHTML = `<p>${data.message}</p>`;
        } else {
            document.body.innerHTML = `<p>Error: ${data.message}</p>`;
        }
    })
    .catch(error => {
        console.log(error);
        document.body.innerHTML = `<p>Error finalizing import.</p>`;
    });
});
</script>

<?php
echo $OUTPUT->footer();
?>
