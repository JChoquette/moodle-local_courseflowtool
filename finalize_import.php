<?php
require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$courseid = local_courseflowtool_require_course_access();

header('Content-Type: application/json');

ob_start();

function process_import($json_data,$courseid,$selected_lessons,$selected_outcomes,$selected_sections){
    require_once(__DIR__ . '/lib.php');
    global $DB, $CFG;

    file_put_contents(__DIR__ . '/debug.log', print_r($json_data, true), FILE_APPEND);

    $outcomes_made = 0;
    $sections_made = 0;
    $lessons_made = 0;

    // Track outcome ID mappings from JSON to Moodle
    $outcome_mapping = [];


    foreach ($json_data['outcomes'] as $index => $outcome) {
        if (in_array($outcome["id"], $selected_outcomes)) {
            $shortname = $outcome['shortname'];
            $fullname = $outcome['fullname'];

            // Ensure shortname is unique
            $counter = 1;
            $unique_shortname = $shortname;
            while ($DB->record_exists('grade_outcomes', ['shortname' => $unique_shortname, 'courseid' => $courseid])) {
                $unique_shortname = $shortname . '-' . $counter;
                $counter++;
            }

            $outcomes_made++;

            //Uncomment when ready for actual creation
            //$created_outcome = local_courseflowtool_add_outcome($courseid, $fullname, $unique_shortname,$outcome['id']);
            //$outcome_mapping[$outcome['id']] = $created_outcome->id;
        }
    }

    // Create Sections and Lessons
    foreach ($json_data['sections'] as $section_data) {

        //TODO: Instead of creating, first check if that index already exists and rename it
        //TODO: Only rename it if the section appears in $selected_sections
        //$section = local_courseflowtool_create_topic($courseid, $section_data['title']);

        $sections_made++;

        foreach ($section_data['lessons'] as $lesson_index => $lesson_data) {
            if (in_array($lesson_data["id"], $selected_lessons)) {
                // Map outcome IDs from JSON to actual Moodle outcome IDs
                $mapped_outcomes = array_map(fn($id) => $outcome_mapping[$id] ?? null, $lesson_data['outcomes']);
                $mapped_outcomes = array_filter($mapped_outcomes); // Remove any nulls

                //Enable when ready to create
                // local_courseflowtool_add_lesson(
                //     $courseid,
                //     $section,
                //     $lesson_data['lessonname'],
                //     $lesson_data['lessonintro'],
                //     $lesson_data['pagetitle'],
                //     $lesson_data['pagecontents'],
                //     $mapped_outcomes,
                //     $lesson_data["id"],
                // );

                $lessons_made++;
            }
        }
    }

    //TODO: uncomment this? Or move it elsewhere?
    //unset($_SESSION['courseflow_import_data']); // Clear session data
    
    return ['status' => 'success', 'message' => 'Import completed successfully! Created '.$outcomes_made.' outcomes, '.$sections_made.' sections,  '.$lessons_made.' lessons.'];

   // return ['status' => 'success', 'message' => 'Import completed successfully!'];

}


if (empty($_SESSION['courseflow_import_data'])) {
    ob_end_clean();
    echo json_encode(['message' => 'No data found in session.']);
    exit;
}

$json_data = $_SESSION['courseflow_import_data'];
error_log(print_r($json_data));

$selected_lessons = $_POST['lessons'] ?? [];
$selected_outcomes = $_POST['outcomes'] ?? [];
$selected_sections = $_POST['sections'] ?? [];


// Run the import and get the result
$result = process_import($json_data, $courseid,$selected_lessons,$selected_outcomes,$selected_sections);


// Return JSON response
ob_end_clean();
echo json_encode($result);
exit();
