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
 * Testing fixture for LTI roles
 *
 * When used as an LTI tool, this will display a list of IMS roles sent from Moodle to the tool.
 *
 * To use this fixture, go to Site Administration > Plugins > Activity modules > External Tool > Manage tools and follow
 * "configure a tool manually". Create a new tool configuration with the URL of this script as the Tool URL, and LTI1.0/1.1 as the
 * LTI version. Then add an External Tool activity to a course, using this tool configuration.
 *
 * @package   mod_lti
 * @copyright 2022 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is just plain PHP code, so dont do Moodle internal checks etc.
// phpcs:ignoreFile
?>
<html>
    <head>
        <title>Testing fixture for LTI roles</title>
    </head>
    <body>
        <p>The LTI provider received the following roles for the current user:</p>
        <ul>
            <?php
            foreach (explode(',', $_POST['roles']) as $role) {
                echo '<li>' . $role . '</li>' . PHP_EOL;
            }
            ?>
        </ul>
    </body>
</html>
