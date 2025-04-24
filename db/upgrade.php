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
 * Database upgrade file
 *
 * @package    local_courseflowtool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



function xmldb_local_courseflowtool_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025042400) {
        // Define field associate_outcomes to be added to local_courseflowtool_settings.
        $table = new xmldb_table('local_courseflowtool_settings');
        $field = new xmldb_field('associate_outcomes', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // CourseFlow tool savepoint reached.
        upgrade_plugin_savepoint(true, 2025042400, 'local', 'courseflowtool');
    }

    return true;
}