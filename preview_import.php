<?php

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');
$courseid = local_courseflowtool_require_course_access();

$PAGE->set_url(new moodle_url('/local/courseflowtool/preview_import.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Preview CourseFlow Import');
$PAGE->set_heading('Preview CourseFlow Import');

echo $OUTPUT->header();

if (!$SESSION->courseflow_import_data) {
    echo "<p>No data to preview. Please go back and upload JSON first.</p>";
    echo $OUTPUT->footer();
    exit;
}

$json_data = $SESSION->courseflow_import_data;


//Create the form with the preview to allow the user to determine what should/shouldn't be imported
echo '<form id="import-form">';
echo '<h3>Lessons</h3>';
foreach ($json_data['sections'] as $section_index => $section) {
    //Sections are based on index, we don't care about their id we just match them index by index
    echo "<h4>Section: {$section['title']}</h4>";
    $checkbox_id = "section_{$section_index}";
    echo "<input type='checkbox' id='$checkbox_id' name='sections[]' value='$section_index' checked> ";
    echo "<label for='$checkbox_id'>Overwrite existing topic label (if applicable)</label><br>";
    foreach ($section['lessons'] as $lesson_index => $lesson) {
        //Lessons are based on id instead, we want to match up their courseflow id to their moodle id to determine whether/how to update them
        $checkbox_id = "lesson_{$lesson["id"]}";
        echo "<input type='checkbox' id='$checkbox_id' name='lessons[]' value='{$lesson["id"]}' checked> ";
        echo "<label for='$checkbox_id'>{$lesson['lessonname']}</label><br>";
    }
}
echo '<h3>Outcomes</h3>';
foreach ($json_data['outcomes'] as $outcome_index => $outcome) {
    //Outcomes are matched to their courseflow id as well
    $checkbox_id = "outcome_{$outcome["id"]}";
    echo "<input type='checkbox' id='$checkbox_id' name='outcomes[]' value='{$outcome["id"]}' checked> ";
    echo "<label for='$checkbox_id'>{$outcome['shortname']} - {$outcome['fullname']}</label><br>";
}
echo '<br><button type="button" id="confirm-import">Confirm and Import</button>';
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
