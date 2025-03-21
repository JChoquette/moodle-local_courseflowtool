<?php
require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$courseid = local_courseflowtool_require_course_access();
require_sesskey();

header('Content-Type: application/json');

ob_start();

function local_courseflowtool_process_import($json_data,$courseid,$selected_lessons,$selected_outcomes,$selected_sections){
    require_once(__DIR__ . '/lib.php');
    global $DB, $CFG;

    $outcomes_made = 0;
    $sections_made = 0;
    $lessons_made = 0;

    local_courseflowtool_cleanup($courseid);

    foreach ($json_data['outcomes'] as $index => $outcome) {
        if (in_array($outcome["id"], $selected_outcomes)) {
            
            $created_outcome = local_courseflowtool_add_outcome(
                $courseid, 
                $outcome['fullname'],
                $outcome['shortname'],
                $outcome['id']
            );
            $outcomes_made++;
        }
    }

    // Create Sections and Lessons
    foreach ($json_data['sections'] as $section_index => $section_data) {

        //Create the section. Unlike lessons or outcomes, this needs to know if the section has been selected directly, since we need to create a new section anyways even if the user has deselected it in the case where we don't have enough sections
        //We update section_index+1 because the top (0th) section in Moodle is usually for announcements/course info, it isn't one of the "topics"
        $update_section = in_array($section_index,$selected_sections);
        $section = local_courseflowtool_create_topic(
            $courseid, 
            $section_data['title'],
            $section_index+1,
            $update_section
            
        );

        $sections_made++;

        foreach ($section_data['lessons'] as $lesson_index => $lesson_data) {
            if (in_array($lesson_data["id"], $selected_lessons)) {
                
                local_courseflowtool_add_lesson(
                    $courseid,
                    $section,
                    $lesson_data['lessonname'],
                    $lesson_data['lessonintro'],
                    $lesson_data['pagetitle'],
                    $lesson_data['pagecontents'],
                    $lesson_data['outcomes'],
                    $lesson_data["id"],
                );

                $lessons_made++;
            }
        }
    }

    //TODO: uncomment this? Or move it elsewhere?
    //unset($_SESSION['courseflow_import_data']); // Clear session data
    
    \cache_helper::purge_by_event('changesincourse', $courseid);

    return ['status' => 'success', 'message' => 'Import completed successfully! Created '.$outcomes_made.' outcomes, '.$sections_made.' sections,  '.$lessons_made.' lessons.'];

   // return ['status' => 'success', 'message' => 'Import completed successfully!'];

}

//Is this use of $SESSION correct?
if (!$SESSION->courseflow_import_data) {
    ob_end_clean();
    echo json_encode(['message' => 'No data found in session.']);
    exit;
}

$json_data = $SESSION->courseflow_import_data ?? null;

//TODO: Don't access post directly like this!
$selected_lessons = optional_param('lessons', [], PARAM_INT);
$selected_outcomes = optional_param('outcomes', [], PARAM_INT);
$selected_sections = optional_param('sections', [], PARAM_INT);

// Run the import and get the result
$result = local_courseflowtool_process_import($json_data, $courseid,$selected_lessons,$selected_outcomes,$selected_sections);


// Return JSON response
ob_end_clean();
echo json_encode($result);
exit();
