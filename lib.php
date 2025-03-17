<?php
defined('MOODLE_INTERNAL') || die();

function local_courseflowtool_extend_settings_navigation($settingsnav, $context) {
    if ($context->contextlevel == CONTEXT_COURSE) {
        // Check if the user has the required capability.
        if (has_capability('local/courseflowtool:view', $context)) {
            $url = new moodle_url('/local/courseflowtool/course.php', ['id' => $context->instanceid]);
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

function local_courseflowtool_create_topic($courseid, $sectionname) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/externallib.php');
    require_once($CFG->dirroot . '/course/lib.php');

    // Define the new section data
    $section_data = [
        'name' => $sectionname, // Topic name
        'summary' => 'This is an automatically generated topic.', // Optional description
        'summaryformat' => 1, // HTML format
    ];

    // Use Moodle’s course API to create the section
    $section = course_create_section($courseid);
    course_update_section($courseid,$section,$section_data);
    return $section;
}

function local_courseflowtool_add_label($courseid, $section, $labeltext) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/externallib.php');
    require_once($CFG->dirroot . '/mod/label/lib.php');
    require_once($CFG->dirroot . '/course/modlib.php'); // Required for course modules
    require_once($CFG->dirroot . '/course/lib.php');

    // Define the label data
    $label = new stdClass();
    $label->course = $courseid;
    $label->section = $section; // The section where the label should be added
    $label->name = 'New Label';
    $label->intro = $labeltext; // The content of the label
    $label->introformat = FORMAT_HTML;
    $label->timemodified = time();

    // Insert the label instance into the database
    $labelid = label_add_instance($label);

    // Now create a course module entry for the label
    $module = $DB->get_record('modules', ['name' => 'label']);
    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $module->id;
    $cm->instance = $labelid;
    $cm->section = $section->section;
    $cm->visible = 1; // Make it visible
    $cm->added = time();

    // Insert the course module and update section
    $cmid = $DB->insert_record('course_modules', $cm);


    course_add_cm_to_section($courseid, $cmid, $section->section);

    return $labelid;
}

function local_courseflowtool_add_lesson($courseid, $section, $lessonname, $lessonintro, $pagetitle, $pagecontents, $outcomes) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/externallib.php');
    require_once($CFG->dirroot . '/mod/lesson/lib.php');
    require_once($CFG->dirroot . '/mod/lesson/locallib.php');
    require_once($CFG->dirroot . '/course/modlib.php'); // Required for course modules
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/mod/lesson/pagetypes/branchtable.php');

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

    // Step 3: Create a course module entry (but without an instance ID yet)
    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $module->id;
    $cm->section = $section->section;
    $cm->visible = 1; // Make it visible
    $cm->added = time();

    $cmid = $DB->insert_record('course_modules', $cm);

    // Step 4: Create the lesson instance
    $lesson = new stdClass();
    $lesson->course = $courseid;
    $lesson->name = $lessonname;
    $lesson->intro = $lessonintro;
    $lesson->introformat = FORMAT_HTML;
    $lesson->available = time(); 
    $lesson->timemodified = time();
    $lesson->coursemodule = $cmid; // Set the course module ID before inserting

    $lessonid = lesson_add_instance($lesson, []);

    // Step 5: Update the course module with the correct instance ID
    $DB->set_field('course_modules', 'instance', $lessonid, ['id' => $cmid]);

    // Step 6: Add the course module to the correct section
    course_add_cm_to_section($courseid, $cmid, $section->section);

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

    // Step 9: Add the outcomes

    $itemnumber=1000;

    if (!empty($outcomes)) {
        foreach ($outcomes as $outcomeid) {
            $outcome = $DB->get_record('grade_outcomes', ['id' => $outcomeid], '*', MUST_EXIST);
            $grade_item = new stdClass();
            $grade_item->courseid = $courseid;
            $grade_item->iteminstance = $lessonid;
            $grade_item->itemmodule = 'lesson';
            $grade_item->itemnumber = $itemnumber;
            $grade_item->itemtype = 'mod';
            $grade_item->itemname = $lessonname . " - Outcome " . $outcomeid;
            $grade_item->categoryid = $default_category->id;
            $grade_item->outcomeid = $outcomeid; // Default for outcomes
            $grade_item->scaleid = $outcome->scaleid;
            $grade_item->grade_displaytype = GRADE_DISPLAY_TYPE_REAL; // Show the grade as a real number
            $grade_item->timecreated = time();
            $grade_item->timemodified = time();
            
            // Insert the new grade item
            $DB->insert_record('grade_items', $grade_item);
            $itemnumber = $itemnumber+1;
        }
    }


    return $lessonid;
}


function local_courseflowtool_add_outcome($courseid, $fullname, $shortname) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $outcome = new stdClass();
    $outcome->shortname = $shortname;
    $outcome->fullname = $fullname;
    $outcome->scaleid = 1; // ID of the scale to be used, change this to something you are sure exists
    $outcome->courseid = $courseid; // ID of the course

    $gradeOutcome = new grade_outcome($outcome);
    $gradeOutcome->insert();

    return $DB->get_record('grade_outcomes', ['shortname' => $shortname, 'courseid' => $courseid], '*', MUST_EXIST);
}