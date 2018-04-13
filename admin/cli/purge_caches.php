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
 * CLI script to purge caches without asking for confirmation.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');

$longoptions = [
    'help' => false,
    'all' => false,
    'muc' => false,
    'theme' => false,
    'lang' => false,
    'js' => false,
    'filter' => false,
    'other' => false
];
list($options, $unrecognized) = cli_get_params($longoptions, ['h' => 'help']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
//@codingStandardsIgnoreStart
    // The indentation of this string is "wrong" but this is to avoid a extra whitespace in console output.
    $help =
"Invalidates Moodle internal caches

Specific caches can be defined (alone or in combination) using arguments. If none are specified,
all caches will be purged.

Options:
-h, --help            Print out this help
    --all             Purge all caches (default)
    --muc             Purge all MUC caches (includes lang cache)
    --theme           Purge theme cache
    --lang            Purge language string cache
    --js              Purge JavaScript cache
    --filter          Purge text filter cache
    --other           Purge all file caches and other miscellaneous caches (may include MUC
                      if using cachestore_file).

Example:
\$sudo -u www-data /usr/bin/php admin/cli/purge_caches.php
";
//@codingStandardsIgnoreEnd

    echo $help;
    exit(0);
}


$trueoptions = array_filter($options);
if ($options['all'] && count($trueoptions) > 1) {
    cli_error(get_string('cliinvalidcombination', 'admin', '--' . implode(' --', array_keys($trueoptions))), 2);
}

$purgeall = empty($trueoptions) || $options['all'];
purge_caches($purgeall, $options);

exit(0);
