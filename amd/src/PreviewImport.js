/**
 * @module     local_courseflowtool/PreviewImport
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
export const init = ({sesskey, courseid, error_finalize,error_generic}) => {
    document.getElementById('confirm-import').addEventListener('click', function() {
        let formData = new FormData(document.getElementById('import-form'));

        fetch(`finalize_import.php?courseid=${courseid}&sesskey=${sesskey}`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // eslint-disable-next-line no-console
            // console.log(response.text());
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                document.getElementById("import-form").innerHTML = `<p>${data.message}</p>`;
            } else {
                document.getElementById("import-form").innerHTML = `<p>${error_generic}: ${data.message}</p>`;
            }
        })
        .catch(error => {
            document.getElementById("import-form").innerHTML = `<p>${error_finalize}.</p>`;
        });
    });

    //Toggles the checkboxes on (if not all on) or off (if all off)
    const toggleCheckboxes = (selector, button, labelCheck, labelUncheck) => {
        const checkboxes = document.querySelectorAll(selector);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach((cb) => {
            cb.checked = !allChecked;
        });
    };

    const toggleLessonsBtn = document.getElementById('toggle-lessons');
    const toggleOutcomesBtn = document.getElementById('toggle-outcomes');

    toggleLessonsBtn.addEventListener('click', () => {
        toggleCheckboxes(
            '.lesson-checkbox',
            toggleLessonsBtn,
            'Select All Lessons',
            'Deselect All Lessons'
        );
    });

    toggleOutcomesBtn.addEventListener('click', () => {
        toggleCheckboxes(
            '.outcome-checkbox',
            toggleOutcomesBtn,
            'Select All Outcomes',
            'Deselect All Outcomes'
        );
    });
};

