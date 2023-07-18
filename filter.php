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

        $text = preg_replace_callback('/{{([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)(\:f)?}}/is', [
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
        $generate_fakedata = isset($matches[4]);

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
            // Database activity not found.
            return get_string('databasenotfound', 'filter_moddata', $moddata_name);
        }

        // Get current user's groups.
        $usergroups = groups_get_user_groups($courseid, $USER->id);

        // Infer user's dataset name from user's groups.
        // TODO check the case when the user is part of several groups named "dataset_..."
        $datasetname = '';
        foreach ($usergroups[0] as $groupid) {
            $group = $DB->get_record('groups', ['id' => $groupid]);
            if (strpos($group->name, 'dataset_') === 0) {
                $datasetname = str_replace('dataset_', '', $group->name);
                break;
            }
        }
        if (!$datasetname) {
            // Dataset not found.
            return get_string('datasetnotfound', 'filter_moddata', $datasetname);
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
        if (!$dataset_fieldid || !$itemname_fieldid || !$itemfield_fieldid) {
            // Relevant fields not found.
            $a = (object)[
                    'field1'    => 'datasetname',
                    'field2'    => 'itemname',
                    'field3'    => $itemfield,
            ];

            return get_string('requiredfieldsnotfound', 'filter_moddata', $a);
        }

        // Find the relevant dataset's data records.
        if (!count($dataset_recordids)) {
            $datasetrecords = $DB->get_records_select('data_content',
                    'fieldid = ? AND ' . $DB->sql_compare_text('content') . ' = ?', [
                            $dataset_fieldid,
                            $datasetname
                    ], 'id ASC', 'recordid');
            if (!$datasetrecords) {
                // Relevant record not found.
                return get_string('datasetrecordsnotfound', 'filter_moddata', $datasetname);
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
                $a = (object)[
                        'datasetname'  => $datasetname,
                        'itemname'     => $itemname,
                        'fieldname'    => $itemfield,
                ];

                return get_string('recordnotfound', 'filter_moddata', $a);
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
            // We should have only 1 record, something has gone wrong.
            $a = (object)[
                    'datasetname'  => $datasetname,
                    'itemname'     => $itemname,
            ];

            return get_string('toomanyrecordsfound', 'filer_moddata', $a);
        }
        $recordid = array_pop($recordids);

        // Finally, let's get the field we wanted in the first place.
        $content = $DB->get_record('data_content', [
                'recordid' => $recordid,
                'fieldid'  => $itemfield_fieldid
        ]);

        if (!$content) {
            // Content record was not found.
            return $matches[0];
        }

        // Are we to generate fale data?
        if ($generate_fakedata) {
            static $fakeno = 0;
            $fakeno++;

            return self::get_fakedata($content->content, $fakeno);
        }

        return $content->content;
    }

    /**
     * @param string $data
     * @param        $fakeno
     *
     * @return string
     */
    private function get_fakedata(string $data, $fakeno) {

        // Get a number between 0 and 4, which will be constant for the whole current calendar week;
        $four = (int)date('w') % 5;
        // Get a number between 0 and 6, which will be constant for the whole current calendar week;
        $six = (int)date('w') % 7;

        if (!$four && !$six) {
            // We don't want them to both be equal to zero, so we do this to avoid returning the $data unchanged.
            $six++;
        }

        $fake = 'fake from ' . $data . ' &gt;&gt; '; // TODO remove
        $charno = 0;
        $lastchar = '';
        foreach (str_split($data) as $char) {
            if (!preg_match('/[0-9]/', $char)) {
                // Don't change non-numerical parts of the string.
                $fake .= $char;
            }
            else if ($char == '0' && $lastchar == '0') {
                // If we have two zeroes in a row, leave them as they are: cut off the last digit out of $fake,
                // then and add two zeroes.
                $fake = substr($fake, 0, (strlen($fake) - 1)) . '00';
            }
            else {
                // Do the magic.
                // TODO find a better implementation, the following is really very basic.
                $magic = ($charno % 2) ? ($fakeno + $four) % 3 + 1 : ($fakeno + $six) % 5 + 1;
                $fake .= (((int)$char + (pow(-1, $charno + $fakeno) * $magic)) + 1) % 10;
            }
            $charno++;
            $lastchar = $char;
        }

        return $fake;
    }

}

