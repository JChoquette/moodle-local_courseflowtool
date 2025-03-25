<?php
defined('MOODLE_INTERNAL') || die();

//Should be run at the top of any AJAX calls or php-generated pages. Ensures the user has permissions to use the tool and is logged in.
function local_courseflowtool_require_course_access() {
    $courseid = required_param('courseid', PARAM_INT);
    require_login($courseid);

    $context = context_course::instance($courseid);
    require_capability('local/courseflowtool:view', $context);
    require_capability('moodle/course:manageactivities', $context);

    return $courseid;
}

function local_courseflowtool_extend_settings_navigation($settingsnav, $context) {
    if ($context->contextlevel == CONTEXT_COURSE) {
        // Check if the user has the required capability.
        if (has_capability('local/courseflowtool:view', $context)) {
            $url = new moodle_url('/local/courseflowtool/import_tool.php', ['courseid' => $context->instanceid]);
            $node = navigation_node::create(
                'CourseFlow Tool',
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'courseflowtool',
                new pix_icon('i/settings', '')
            );
            $settingsnav->add_node($node);
        }
    }
}

function local_courseflowtool_create_topic($courseid, $sectionname, $index, $update_data) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/externallib.php');
    require_once($CFG->dirroot . '/course/lib.php');

    $existing_section = $DB->get_record('course_sections',['section' => $index, 'course' => $courseid]);

    // Use Moodle’s course API to create the section
    if(!$existing_section){
        $section = course_create_section($courseid);
    }else{
        $section = $existing_section;
    }
    if($update_data){

        // Define the new section data
        $section_data = [
            'name' => $sectionname, // Topic name
            'summary' => 'This is an automatically generated topic.', // Optional description
            'summaryformat' => 1, // HTML format
        ];

        course_update_section($courseid,$section,$section_data);
    }
    return $section;
}


function local_courseflowtool_add_lesson($courseid, $section, $lessonname, $lessonintro, $pagetitle, $pagecontents, $outcomes, $courseflow_id) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/externallib.php');
    require_once($CFG->dirroot . '/mod/lesson/lib.php');
    require_once($CFG->dirroot . '/mod/lesson/locallib.php');
    require_once($CFG->dirroot . '/course/modlib.php'); // Required for course modules
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/mod/lesson/pagetypes/branchtable.php');

    //Check for an existing outcome previously created from this courseflow id
    $existing_map = $DB->get_record('local_courseflowtool_map',[
        'courseflow_id' => $courseflow_id, 
        'type' => 'lesson', 
        'courseid' => $courseid
    ]);

    //Try to get the corresponding lesson
    $this_lesson=false;
    if($existing_map){
        $this_lesson = $DB->get_record('lesson',[
            'course' => $courseid,
            'id' => $existing_map -> moodle_lessonid
        ]);
        //If we didn't get a corresponding outcome, our mapping is leading to nothing, delete the mapping. In theory this code should never run.
        if(!$this_lesson){
            $DB->delete_records('local_courseflowtool_map',[
                'courseid' => $courseid,
                'courseflow_id' => $courseflow_id,
                'type' => 'lesson'
            ]);
        }
    }

    // Step 1: Get the default grade item category for use later

    // Retrieve the default grade category
    $default_category = $DB->get_record('grade_categories', [
        'courseid' => $courseid,
        'parent' => null // 0 means "root" category
    ]);

    // Step 2: Get the "lesson" module ID from 'modules' table
    $module = $DB->get_record('modules', ['name' => 'lesson']);
    if (!$module) {
        debugging("Lesson module not found in 'modules' table.", DEBUG_DEVELOPER);
        return null;
    }

    //If the lesson doesn't already exist
    if(!$this_lesson){


        // Step 3: Create the lesson's intro data
        $introeditor = [];
        $introeditor['text'] = $lessonname;
        $introeditor['format'] = FORMAT_HTML;

        // Step 4: Create the lesson module data
        $lesson = new stdClass();
        $lesson->course = $courseid;
        $lesson->modulename = 'lesson';
        $lesson->section = $section->section;
        $lesson->show_description = 1;
        $lesson->visible = 1;
        $lesson->name = $lessonname;
        $lesson->available = time(); 
        $lesson->timemodified = time();
        $lesson->introeditor = $introeditor;

        //Step 5: Create the actual module
        $new_lesson = create_module($lesson);
        $lessonid = $new_lesson->id;

        // Step 5: Fetch the newly created course module
        $cm = $DB->get_record('course_modules',[
            'course'=>$courseid,
            'instance'=>$lessonid,
            'module'=>$module->id
        ]);

        // Step 6: Update the course module to show the description
        $cm->showdescription=1;
        $DB->update_record('course_modules',$cm);

        // Step 7: Add a content page to the lesson

        $page = new stdClass();
        $page->lessonid = $lessonid;
        $page->title = $pagetitle;
        $page->contents = $pagecontents;
        $page->contentsformat = FORMAT_HTML;
        $page->qtype = LESSON_PAGE_BRANCHTABLE; // This specifies it's a content page
        $page->prevpageid = 0; // First page in the lesson
        $page->nextpageid = 0; // No next page yet
        $page->timecreated = time();
        $page->timemodified = time();

        $pageid = $DB->insert_record('lesson_pages', $page);
        
        // Step 8: Add a default jump (this is required for the lesson to function properly)
        $answer = new stdClass();
        $answer->lessonid = $lessonid;
        $answer->pageid = $pageid;
        $answer->jumpto = LESSON_NEXTPAGE; // Default to next page
        $answer->timecreated = time();
        $answer->timemodified = time();

        $DB->insert_record('lesson_answers', $answer);

        //Step 11: Create a mapping between the courseflow id and the Moodle one

        $DB->insert_record('local_courseflowtool_map', [
            'courseid' => $courseid,
            'courseflow_id' => $courseflow_id,
            'moodle_lessonid' => $lessonid,
            'type' => 'lesson'
        ]);

    }else{
        //Update the existing lesson
        $lesson = $this_lesson;
        $lessonid = $lesson->id;
        $lesson->name = $lessonname;
        $lesson->intro = $lessonintro;
        $lesson->timemodified = time();
        $DB->update_record('lesson',$lesson);

        //Update the page if it exists. If it doesn't, the user probably deleted it so we don't create a new one.
        $page = $DB->get_record('lesson_pages',[
            'lessonid' => $lessonid,
            'qtype' => LESSON_PAGE_BRANCHTABLE,
            'prevpageid' => 0
        ]);
        if($page){
            $page->title = $pagetitle;
            $page->contents = $pagecontents;
            $DB->update_record('lesson_pages',$page);
        }
    }

    // Step 9: Add the outcomes

    $itemnumber_unique=1000;

    //An array of outcome ids that should remain attached at the end
    $outcome_ids_to_keep = [];
    foreach ($outcomes as $outcomeid) {

        //Get the outcome corresponding to that courseflow outcome id from the DB. Outcomes are created before lessons so this should exist unless the user has deselected the outcome.
        $outcome_map = $DB->get_record('local_courseflowtool_map',[
            'courseflow_id' => $outcomeid, 
            'type' => 'outcome', 
            'courseid' => $courseid
        ]);
        $outcome = false;
        if($outcome_map){
            $outcome = $DB->get_record('grade_outcomes',[
                'courseid' => $courseid,
                'id' => $outcome_map -> moodle_outcomeid
            ]);
        }

        //If the outcome doesn't exist, we just skip this outcome.
        if(!$outcome){
            continue;
        }
        $outcome_ids_to_keep[] = $outcome->id;

        //If the outcome is already attached to this lesson via a grade_item, we can just leave it
        $grade_item = $DB->get_record('grade_items',[
            'courseid' => $courseid,
            'itemmodule' => 'lesson',
            'iteminstance' => $lessonid,
            'outcomeid' => $outcome->id
        ]);
        if($grade_item){
            continue;
        }

        //Make sure our grade item number is unique
        $grade_items = $DB->get_records('grade_items', [
            'courseid' => $courseid,
            'iteminstance' => $lessonid,
            'itemmodule' => 'lesson'
        ]);
        // Check if any grade_items exist and find the highest itemnumber.
        if ($grade_items) {
            $max_itemnumber = max(array_column($grade_items, 'itemnumber'));
            $item_number_unique = $max_itemnumber + 1;
        }

        $grade_item = new stdClass();
        $grade_item->courseid = $courseid;
        $grade_item->iteminstance = $lessonid;
        $grade_item->itemmodule = 'lesson';
        $grade_item->itemnumber = $itemnumber_unique;
        $grade_item->itemtype = 'mod';
        $grade_item->itemname = "Lesson - " . $lessonid . " : Outcome " . $outcome->id;
        $grade_item->categoryid = $default_category->id;
        $grade_item->outcomeid = $outcome->id; 
        $grade_item->scaleid = $outcome->scaleid; //default scale
        $grade_item->grade_displaytype = GRADE_DISPLAY_TYPE_REAL; // Show the grade as a real number
        $grade_item->timecreated = time();
        $grade_item->timemodified = time();
        
        // Insert the new grade item
        $DB->insert_record('grade_items', $grade_item);
    }

    //Step 10: Remove any outcomes that don't show up in the list
    $existing_grade_items = $DB->get_records('grade_items', [
        'courseid' => $courseid,
        'itemmodule' => 'lesson',
        'iteminstance' => $lessonid
    ]);

    // Loop through all existing grade items and delete any that are not in the list of outcomes to keep
    foreach ($existing_grade_items as $grade_item) {
        if ($grade_item->outcomeid && !in_array($grade_item->outcomeid, $outcome_ids_to_keep)) {
            $DB->delete_records('grade_items', [
                'id' => $grade_item->id,
                'courseid' => $courseid
            ]);
        }
    }

    


    return $lessonid;
}


function local_courseflowtool_add_outcome($courseid, $fullname, $shortname_temp, $courseflow_id) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    //Check for an existing outcome previously created from this courseflow id
    $existing_map = $DB->get_record('local_courseflowtool_map',[
        'courseflow_id' => $courseflow_id, 
        'type' => 'outcome', 
        'courseid' => $courseid
    ]);

    //Try to get the corresponding outcome
    $this_outcome=false;
    if($existing_map){
        $this_outcome = $DB->get_record('grade_outcomes',[
            'courseid' => $courseid,
            'id' => $existing_map -> moodle_outcomeid
        ]);
        //If we didn't get a corresponding outcome, our mapping is leading to nothing, delete the mapping. In theory this code should never run because we run cleanup first.
        if(!$this_outcome){
            $DB->delete_records('local_courseflowtool_map',[
                'courseid' => $courseid,
                'courseflow_id' => $courseflow_id,
                'type' => 'outcome'
            ]);
        }
    }

    // Ensure shortname is unique
    $counter = 1;
    $unique_shortname = $shortname_temp;
    while ($DB->record_exists('grade_outcomes', ['shortname' => $unique_shortname, 'courseid' => $courseid])) {
        //If the existing outcome with the same shortname is the one that we are about to update, this isn't a problem.
        if($this_outcome){
            if($this_outcome->shortname==$unique_shortname){
                break;
            }
        }
        $unique_shortname = $shortname . '-' . $counter;
        $counter++;
    }
    $shortname = $unique_shortname;

    if(!$this_outcome){
        $outcome = new stdClass();
        $outcome->shortname = $shortname;
        $outcome->fullname = $fullname;
        $outcome->scaleid = 1; // TODO: ID of the scale to be used, change this to something you are sure exists
        $outcome->courseid = $courseid; // ID of the course

        $gradeOutcome = new grade_outcome($outcome);
        $gradeOutcome->insert();

        $this_outcome = $DB->get_record('grade_outcomes', ['shortname' => $shortname, 'courseid' => $courseid], '*', MUST_EXIST);

        //Create a mapping between the courseflow id and the Moodle one
        $DB->insert_record('local_courseflowtool_map', [
            'courseid' => $courseid,
            'courseflow_id' => $courseflow_id,
            'moodle_outcomeid' => $this_outcome->id,
            'type' => 'outcome'
        ]);
    }else{
        $this_outcome->shortname = $shortname;
        $this_outcome->fullname = $fullname;
        $DB->update_record('grade_outcomes',$this_outcome);
    }

    //TODO: Figure out why we still sometimes can't access the "grades" page of the course

    return $this_outcome;
}

function local_courseflowtool_cleanup($courseid){
    global $DB;

    // Get all mapped outcome IDs
    $mapped_outcomes = $DB->get_records('local_courseflowtool_map', [
        'type' => 'outcome',
        'courseid' => $courseid
    ]);

    foreach ($mapped_outcomes as $map) {
        $outcome_exists = $DB->record_exists('grade_outcomes', [
            'id' => $map->moodle_outcomeid,
            'courseid' => $courseid
        ]);

        // If the outcome no longer exists, delete the mapping
        if (!$outcome_exists) {
            $DB->delete_records('local_courseflowtool_map', [
                'moodle_outcomeid' => $map->moodle_outcomeid,
                'type' => 'outcome',
                'courseid' => $courseid
            ]);
        }
    }

}

