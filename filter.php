<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    filter_questiondata
 * @copyright  2022 Université de Lausanne http://www.unil.ch
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_questiondata extends moodle_text_filter {

    function filter($text, array $options = array()) {

        $text = preg_replace_callback('/{{questiondata\:([a-zA-Z0-9]+)\:([a-zA-Z0-9]+)}}/is', [
                        $this,
                        'embed'
                ], $text);

        return $text;
    }

    function embed($matches) {
        global $DB, $PAGE, $USER;

        $questionname = $matches[1];
        $questionfield = $matches[2];

        // Get the current course.
        $coursectx = $PAGE->context->get_course_context(true);
        $courseid = $coursectx->instanceid;

        // First, find the relevant database activity.
        $database = $DB->get_record('data', [
                'name'   => 'questiondata',
                'course' => $courseid
        ]);

        if (!$database) {
            // Database not found, leave text untouched.
            return $matches[0];
        }

        // Get current user's groups.
        $usergroups = groups_get_user_groups($courseid, $USER->id);

        // Infer user's dataset name from user's groups.
        $datasetname = '';
        foreach ($usergroups[0] as $groupid) {
            $group = $DB->get_record('groups', ['id' => $groupid]);
            if (strpos($group->name, 'questiondata_') === 0) {
                $datasetname = str_replace('questiondata_', '', $group->name);
                break;
            }
        }
        if (!$datasetname) {
            // Dataset not found, leave text untouched.
            return $matches[0];
        }

        // Find data fields.
        $fields = $DB->get_records('data_fields', ['dataid' => $database->id]);
        $dataset_fieldid = 0;
        $questionname_fieldid = 0;
        $questionfield_fieldid = 0;
        foreach ($fields as $field) {
            if ($field->name === 'datasetname') {
                $dataset_fieldid = $field->id;
            }
            if ($field->name === 'questionname') {
                $questionname_fieldid = $field->id;
            }
            if ($field->name === $questionfield) {
                $questionfield_fieldid = $field->id;
            }
        }
        if (!$dataset_fieldid && !$questionname_fieldid && !$questionfield_fieldid) {
            // Relevant fields not found, leave text untouched.
            return $matches[0];
        }

        // Find the relevant dataset's data records.
        $datasetrecords =
                $DB->get_records_select('data_content', 'fieldid = ? AND ' . $DB->sql_compare_text('content') . ' = ?',
                        [
                                $dataset_fieldid,
                                $datasetname
                        ]);
        if (!$datasetrecords) {
            // Relevant record not found, leave text untouched.
            return $matches[0];
        }
        $dataset_recordids = [];
        foreach ($datasetrecords as $datasetrecord) {
            $dataset_recordids[] = $datasetrecord->recordid;
        }

        // Get the relevant question's records.
        $questionrecords =
                $DB->get_records_select('data_content', 'fieldid = ? AND ' . $DB->sql_compare_text('content') . ' = ?',
                        [
                                $questionname_fieldid,
                                $questionname
                        ]);
        if (!$questionrecords) {
            // Relevant record not found, leave text untouched.
            return $matches[0];
        }
        $question_recordids = [];
        foreach ($questionrecords as $questionrecord) {
            $question_recordids[] = $questionrecord->recordid;
        }

        // Now, we can identify the actual record.
        $recordid = array_intersect($dataset_recordids, $question_recordids)[0];

        // Finally, let's get the field we wanted in the first place.
        $content = $DB->get_record('data_content', ['recordid' => $recordid, 'fieldid' => $questionfield_fieldid]);
        return $content->content;

        // TODO remove.
        return print_r(['recordid' => $recordid, 'fieldid' => $questionfield_fieldid], true);

    }

}

