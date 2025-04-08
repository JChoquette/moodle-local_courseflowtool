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
 * Internal functions for import
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

/**
 * Should be run at the top of any AJAX calls or PHP-generated pages.
 * 
 * Ensures the user is logged in and has permission to access the specified course.
 * If the user lacks the required permissions, appropriate error handling is performed.
 *
 * @return int The ID of the course the user is accessing.
 * @throws moodle_exception If the user does not have the required access or is not logged in.
 */
function local_courseflowtool_require_course_access() {
    $courseid = required_param('courseid', PARAM_INT);
    require_login($courseid);

    $context = context_course::instance($courseid);
    require_capability('local/courseflowtool:view', $context);
    require_capability('moodle/course:manageactivities', $context);

    return $courseid;
}

/**
 * Extends the settings navigation for the course.
 * 
 * Adds additional settings options related to the CourseFlow import tool 
 * to the course navigation block, if the user has the appropriate permissions.
 *
 * @param navigation_node $settingsnav The settings navigation node to extend.
 * @param context $context The context of the course where the navigation is being extended.
 */
function local_courseflowtool_extend_settings_navigation($settingsnav, $context) {
    if ($context->contextlevel == CONTEXT_COURSE) {
        // Check if the user has the required capability.
        if (has_capability('local/courseflowtool:view', $context)) {
            $url = new moodle_url('/local/courseflowtool/import_tool.php', ['courseid' => $context->instanceid]);
            $node = navigation_node::create(
                get_string('pluginname','local_courseflowtool'),
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

/**
 * Creates a new topic (section) in the specified course.
 *
 * If a section with the given name already exists at the specified index, 
 * it updates the section's name and visibility status. Otherwise, it 
 * creates a new section with the provided details.
 *
 * @param int $courseid The ID of the course where the section will be created.
 * @param string $sectionname The name of the section to create or update.
 * @param int $index The section number (starting from 1) where the section will be placed. We don't overwrite the first topic, since it is usually reserved for announcements, etc.
 * @param bool $update_data Whether or not to actually update or alter the data in this.
 * 
 * @return stdClass The created or updated section object.
 */
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
            'summaryformat' => 1, // HTML format
        ];

        course_update_section($courseid,$section,$section_data);
    }
    return $section;
}


/**
 * Adds a new lesson to the specified course section.
 *
 * This function creates a lesson with the provided name, introduction, and content page.
 * It also associates the lesson with the specified outcomes and stores the CourseFlow ID 
 * for mapping purposes.
 *
 * @param int $courseid The ID of the course where the lesson will be added.
 * @param stdClass $section The section object where the lesson will be placed.
 * @param string $lessonname The name of the lesson.
 * @param string $lessonintro The introduction text for the lesson.
 * @param string $pagetitle The title of the initial content page.
 * @param string $pagecontents The content of the initial page.
 * @param array $outcomes An array of outcome IDs to associate with the lesson.
 * @param int $courseflow_id The CourseFlow ID used for mapping and tracking.
 *
 * @return int The ID of the created lesson.
 */
function local_courseflowtool_add_lesson($courseid, $section, $lessonname, $lessonintro, $pagetitle, $pagecontents, $outcomes, $courseflow_id) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/lesson/locallib.php');
    require_once($CFG->dirroot . '/mod/lesson/lib.php');
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
    $default_category = $DB->get_record('grade_categories', 
        ['courseid' => $courseid,
        'parent' => null],
        '*',
        MUST_EXIST);

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
        $introeditor['text'] = $lessonintro;
        $introeditor['format'] = FORMAT_HTML;

        // Step 4: Create the lesson module data
        $lesson = new stdClass();
        $lesson->course = $courseid;
        $lesson->modulename = 'lesson';
        $lesson->section = $section->section;
        $lesson->visible = 1;
        $lesson->name = $lessonname;
        $lesson->available = time(); 
        $lesson->timemodified = time();
        $lesson->introeditor = $introeditor;
        $lesson->showdescription = 1;

        //Step 5: Create the actual module, then fetch the newly created course module
        $new_lesson = create_module($lesson);
        $lessonid = $new_lesson->id;

        $cm = $DB->get_record('course_modules',[
            'course'=>$courseid,
            'instance'=>$lessonid,
            'module'=>$module->id
        ]);
        $context = context_module::instance($cm->id);

        // Step 6: Add a content page to the lesson

        // Get the context of the course module

        $contents_editor = [];
        $contents_editor['text'] = $pagecontents;
        $contents_editor['format'] = FORMAT_HTML;

        $page = new stdClass();
        $page->lessonid = $lessonid;
        $page->title = $pagetitle;
        $page->contents_editor = $contents_editor;
        $page->qtype = LESSON_PAGE_BRANCHTABLE; // This specifies it's a content page

        //This automatically creates the "answers"
        $page = lesson_page::create($page,new lesson($new_lesson),$context,$CFG->maxbytes);

        

        //Step 7: Create a mapping between the courseflow id and the Moodle one

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

        $cm = get_coursemodule_from_id('lesson',$DB->get_record('course_modules',[
            'course'=>$courseid,
            'instance'=>$lessonid,
            'module'=>$module->id
        ])->id);
        $context = context_module::instance($cm->id);

        $lesson->coursemodule = $cm->id;
        $lesson->instance = $lessonid;
        $lesson->mediafile = null;

        lesson_update_instance($lesson,null);
        \core\event\course_module_updated::create_from_cm($cm,$context)->trigger();

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

    // Step 8: Add the outcomes

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

        $new_grade_item = new grade_item($grade_item);
        $grade_item_id = $new_grade_item->insert();
        
    }

    //Step 9: Remove any outcomes that don't show up in the list
    $existing_grade_items = $DB->get_records('grade_items', [
        'courseid' => $courseid,
        'itemmodule' => 'lesson',
        'iteminstance' => $lessonid
    ]);

    // Loop through all existing grade items and delete any that are not in the list of outcomes to keep
    foreach ($existing_grade_items as $grade_item) {
        if ($grade_item->outcomeid && !in_array($grade_item->outcomeid, $outcome_ids_to_keep)) {
            $this_grade_item = new grade_item($grade_item);
            $this_grade_item->delete();
        }
    }

    


    return $lessonid;
}

/**
 * Adds a new outcome to the specified course.
 *
 * This function creates an outcome with the given full name and a temporary short name.
 * It also stores the CourseFlow ID for mapping purposes.
 *
 * @param int $courseid The ID of the course where the outcome will be added.
 * @param string $fullname The full name of the outcome.
 * @param string $shortname_temp A temporary short name for the outcome.
 * @param int $courseflow_id The CourseFlow ID used for mapping and tracking.
 *
 * @return stdClass The created outcome object.
 */
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

    return $this_outcome;
}

/**
 * Cleans up old mappings and data associated with a course.
 *
 * This function removes obsolete data from the local_courseflowtool_map and
 * local_courseflowtool_settings tables for the specified course.
 *
 * @param int $courseid The ID of the course to clean up.
 */
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

    // Get the "lesson" module ID from 'modules' table
    $module = $DB->get_record('modules', ['name' => 'lesson']);
    if (!$module) {
        debugging("Lesson module not found in 'modules' table.", DEBUG_DEVELOPER);
        return null;
    }

    // Get all mapped lesson IDs
    $mapped_lessons = $DB->get_records('local_courseflowtool_map', [
        'type' => 'lesson',
        'courseid' => $courseid
    ]);

    foreach ($mapped_lessons as $map) {
        $lesson_module_exists = $DB->record_exists('course_modules', [
            'instance' => $map->moodle_lessonid,
            'course' => $courseid,
            'module' => $module->id,
            'deletioninprogress' => 0
        ]);

        // If the outcome no longer exists, delete the mapping
        if (!$lesson_module_exists) {
            $DB->delete_records('local_courseflowtool_map', [
                'moodle_lessonid' => $map->moodle_lessonid,
                'type' => 'lesson',
                'courseid' => $courseid
            ]);
        }
    }


}

