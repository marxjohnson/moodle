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
 * Upgrade script for plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot  . '/mod/quiz/accessrule/seb/lib.php');

/**
 * Function to upgrade quizaccess_seb plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Result.
 */
function xmldb_quizaccess_seb_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Automatically generated Moodle v4.4.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2024121801) {

        // Define field allowcapturecamera to be added to quizaccess_seb_quizsettings.
        $table = new xmldb_table('quizaccess_seb_quizsettings');
        $field = new xmldb_field('allowcapturecamera', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'muteonstartup');

        // Conditionally launch add field allowcapturecamera.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field allowcapturemicrophone to be added to quizaccess_seb_quizsettings.
        $field = new xmldb_field('allowcapturemicrophone', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'allowcapturecamera');

        // Conditionally launch add field allowcapturemicrophone.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Seb savepoint reached.
        upgrade_plugin_savepoint(true, 2024121801, 'quizaccess', 'seb');
    }

    // Automatically generated Moodle v5.0.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v5.1.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2025111800) {
        // Define table quizaccess_seb_override to be created.
        $table = new xmldb_table('quizaccess_seb_override');

        // Adding fields to table quizaccess_seb_override.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('overrideid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('requiresafeexambrowser', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('showsebtaskbar', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('showwificontrol', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('showreloadbutton', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('showtime', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('showkeyboardlayout', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('allowuserquitseb', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('quitpassword', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('linkquitseb', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('userconfirmquit', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('enableaudiocontrol', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('muteonstartup', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('allowcapturecamera', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('allowcapturemicrophone', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('allowspellchecking', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('allowreloadinexam', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('activateurlfiltering', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('filterembeddedcontent', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('expressionsallowed', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('regexallowed', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('expressionsblocked', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('regexblocked', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('allowedbrowserexamkeys', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('showsebdownloadlink', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table quizaccess_seb_override.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('overrideid', XMLDB_KEY_FOREIGN, ['overrideid'], 'quiz_overrides', ['id']);
        $table->add_key('templateid', XMLDB_KEY_FOREIGN, ['templateid'], 'quizaccess_seb_template', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for quizaccess_seb_override.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2025111800, 'quizaccess', 'seb');
    }

    return true;
}
