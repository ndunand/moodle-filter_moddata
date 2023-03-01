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

$string['filtername'] = 'Database Activity filter';
$string['databasenotfound'] = 'Could not find a database activity named `{$a}` in the current course.';
$string['datasetnotfound'] = 'Could not find a dataset for the current user.';
$string['requiredfieldsnotfound'] = 'Required fields not found. To display this item, the database activity should at least contain fields `{$a->field1}`, `{$a->field2}`, and `{$a->field3}`.';
$string['datasetrecordsnotfound'] = 'Could not find datasets records for dataset `{$a}´ in the specified database activity.';
$string['recordnotfound'] = 'Could not find a field `{$a->fieldname}` for item `{$a->itemname}` in the dataset `{$a->datasetname}`.';
$string['toomanyrecordsfound'] = 'Too many records found for item `{$a->itemname}` in the dataset `{$a->datasetname}';
