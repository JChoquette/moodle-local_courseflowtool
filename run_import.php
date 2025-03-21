//TODO: DELETE FILE NO LONGER IN USE

<?php


function local_courseflowtool_run_import($courseid,$json_data){
    require_once(__DIR__ . '/lib.php');
    global $DB, $CFG;

    // Track outcome ID mappings from JSON to Moodle
    $outcome_mapping = [];

    // Create Outcomes
    foreach ($json_data['outcomes'] as $outcome) { 
        $shortname = $outcome['shortname'];
        $fullname = $outcome['fullname'];

        // Ensure shortname is unique
        $counter = 1;
        $unique_shortname = $shortname;
        while ($DB->record_exists('grade_outcomes', ['shortname' => $unique_shortname, 'courseid' => $courseid])) {
            $unique_shortname = $shortname . '-' . $counter;
            $counter++;
        }

        $created_outcome = local_courseflowtool_add_outcome($courseid, $fullname, $unique_shortname,$outcome['id']);
        $outcome_mapping[$outcome['id']] = $created_outcome->id;
    }

    // Create Sections and Lessons
    foreach ($json_data['sections'] as $section_data) {
        $section = local_courseflowtool_create_topic($courseid, $section_data['title']);
        
        foreach ($section_data['lessons'] as $lesson_data) {
            // Map outcome IDs from JSON to actual Moodle outcome IDs
            $mapped_outcomes = array_map(fn($id) => $outcome_mapping[$id] ?? null, $lesson_data['outcomes']);
            $mapped_outcomes = array_filter($mapped_outcomes); // Remove any nulls
            local_courseflowtool_add_lesson(
                $courseid,
                $section,
                $lesson_data['lessonname'],
                $lesson_data['lessonintro'],
                $lesson_data['pagetitle'],
                $lesson_data['pagecontents'],
                $mapped_outcomes,
                $lesson_data["id"],
            );
        }
    }

    echo "Course structure successfully generated!";
}