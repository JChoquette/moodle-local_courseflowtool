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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Template class for the import preview
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseflowtool\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Renderable class for the preview import screen of CourseFlow.
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_import implements renderable, templatable {
    /** @var array $jsondata Full JSON data structure. */
    private $jsondata;

    /** @var string $sesskey Moodle session key for security. */
    private $sesskey;

    /** @var int $courseid Course ID. */
    private $courseid;

    /**
     * Constructor.
     *
     * @param array $jsondata The parsed JSON import data.
     * @param string $sesskey The user's session key.
     * @param int $courseid The course ID.
     */
    public function __construct($jsondata, $sesskey, $courseid) {
        // Add section indices into data
        foreach ($jsondata["sections"] as $i => $section) {
            $jsondata["sections"][$i]["section_index"] = $i;
        }
        $this->jsondata = $jsondata;
        $this->sesskey = $sesskey;
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
            'sesskey' => $this->sesskey,
            'sections' => $this->jsondata['sections'],
            'outcomes' => $this->jsondata['outcomes'],
        ];
    }
}
