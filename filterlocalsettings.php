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

class moddata_filter_local_settings_form extends filter_local_settings_form {
    protected function definition_inner($mform) {

        $mform->addElement('advcheckbox', 'debug',
                           get_string('debugenabled', 'filter_moddata'),
                           '',
                           [],
                           ['nodebug', 'debug']);

        $mform->addElement('advcheckbox', 'oneemptyanswer',
                           get_string('oneemptyanswer', 'filter_moddata'));
    }
}
