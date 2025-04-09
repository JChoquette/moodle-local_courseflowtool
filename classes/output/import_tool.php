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
 * Template class for the initial import page
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseflowtool\output;

use renderable;
use templatable;
use renderer_base;

/**
 * Renderable class for the CourseFlow import tool interface.
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_tool implements renderable, templatable {
    /** @var int $courseid The ID of the course where the import is being performed. */
    private $courseid;

	/**
     * Constructor.
     *
     * @param int $courseid The course ID
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer base.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        return [
            'courseid' => $this->courseid,
            'sesskey' => sesskey(),
            'jsoninput_placeholder' => get_string('jsoninput_placeholder', 'local_courseflowtool'),
            'jsoninput_button' => get_string('jsoninput_button', 'local_courseflowtool'),
            'json_process_error' => get_string('json_process_error', 'local_courseflowtool')
        ];
    }
}
