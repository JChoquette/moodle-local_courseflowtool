/**
 * @module     local_courseflowtool/ImportTool
 * @copyright  2025 Jeremie Choquette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 */


/*eslint no-unused-vars: ["error", { "args": "none" }]*/

export const init = ({sesskey, courseid, json_process_error}) => {
    document.getElementById('import-button').addEventListener('click', function() {
        let jsonData = document.getElementById('json-input').value;
        fetch(`process_json.php?courseid=${courseid}&sesskey=${sesskey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ json: jsonData })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('response').innerHTML = data.message;
            if(data.redirect){window.location.replace(data.redirect);}
        })
        .catch(error => {
            document.getElementById('response').innerHTML = json_process_error;
        });
    });
};
