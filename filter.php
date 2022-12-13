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
 * @package    filter_moddata
 * @copyright  2022 Université de Lausanne http://www.unil.ch
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_moddata extends moodle_text_filter {

    function filter($text, array $options = array()) {

        $text = preg_replace_callback('/{{([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)}}/is', [
                $this,
                'embed_data'
        ], $text);

        return $text;
    }

    /**
     * @param $matches
     *
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    function embed_data($matches) {
        global $DB, $PAGE, $USER;

        $moddata_name = $matches[1];
        $itemname = $matches[2];
        $itemfield = $matches[3];

        // Use static acceleration for $dataset_recordids, as it is constant for the page,
        // because it's constant for the current user.
        static $dataset_recordids = [];

        // Use static acceleration for $item_recordids, but only if we're using the same item as last call.
        // We check this by keeping track of the last item's name in $lastitemname.
        static $lastitemname = '';
        static $item_recordids = [];

        // Get the current course.
        $coursectx = $PAGE->context->get_course_context(true);
        $courseid = $coursectx->instanceid;

        // First, find the relevant database activity.
        $database = $DB->get_record('data', [
                'name'   => $moddata_name,
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
            if (strpos($group->name, 'dataset_') === 0) {
                $datasetname = str_replace('dataset_', '', $group->name);
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
        $itemname_fieldid = 0;
        $itemfield_fieldid = 0;
        foreach ($fields as $field) {
            if ($field->name === 'datasetname') {
                $dataset_fieldid = $field->id;
            }
            if ($field->name === 'itemname') {
                $itemname_fieldid = $field->id;
            }
            if ($field->name === $itemfield) {
                $itemfield_fieldid = $field->id;
            }
        }
        if (!$dataset_fieldid && !$itemname_fieldid && !$itemfield_fieldid) {
            // Relevant fields not found, leave text untouched.
            return $matches[0];
        }

        // Find the relevant dataset's data records.
        if (!count($dataset_recordids)) {
            $datasetrecords = $DB->get_records_select('data_content',
                    'fieldid = ? AND ' . $DB->sql_compare_text('content') . ' = ?', [
                            $dataset_fieldid,
                            $datasetname
                    ], 'id ASC', 'recordid');
            if (!$datasetrecords) {
                // Relevant record not found, leave text untouched.
                return $matches[0] . __LINE__;
            }
            $dataset_recordids = [];
            foreach ($datasetrecords as $datasetrecord) {
                $dataset_recordids[] = $datasetrecord->recordid;
            }
        }

        // Get the relevant item's records.
        if ($lastitemname !== $itemname) {
            // Need to recompute, can't use static acceleration.
            $itemrecords = $DB->get_records_select('data_content',
                    'fieldid = ? AND ' . $DB->sql_compare_text('content') . ' = ?', [
                            $itemname_fieldid,
                            $itemname
                    ], 'id ASC', 'recordid');
            if (!$itemrecords) {
                // Relevant record not found, leave text untouched.
                return $matches[0] . __LINE__;
            }
            $item_recordids = [];
            foreach ($itemrecords as $itemrecord) {
                $item_recordids[] = $itemrecord->recordid;
            }
            // Mark $lastitemname to benefit from static acceleration next time.
            $lastitemname = $itemname;
        }

        // Now, we can identify the actual record.
        $recordids = array_intersect($dataset_recordids, $item_recordids);
        if (count($recordids) > 1) {
            // We should have only 1 record, something has gone wrong. Leave the text untouched.
            return $matches[0];
        }
        $recordid = array_pop($recordids);

        // Finally, let's get the field we wanted in the first place.
        $content = $DB->get_record('data_content', [
                'recordid' => $recordid,
                'fieldid'  => $itemfield_fieldid
        ]);

        return $content->content;
    }

}

