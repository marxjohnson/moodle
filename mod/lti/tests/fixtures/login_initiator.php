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
 * Fixture for simulating LTI v1.3 login
 *
 * Set the state cookie and lti_initiatelogin_status flag in the session, then redirect back to launch.php
 *
 * This should be used in conjunction with roles_provider_1p3.php
 *
 * @package   mod_lti
 * @copyright 2022 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

use Packback\Lti1p3\ImsStorage\ImsCookie;
use Packback\Lti1p3\LtiOidcLogin;

require_login();
if ($CFG->debug !== DEBUG_DEVELOPER || defined('BEHAT_SITE_RUNNING')) {
    die('This page is for testing only.');
}
$id = required_param('lti_message_hint', PARAM_INT);
$cookie = new ImsCookie();
$cookie->setCookie(LtiOidcLogin::COOKIE_PREFIX.'test', 'test', 10);
$SESSION->lti_initiatelogin_status = 1;
redirect(new moodle_url('/mod/lti/launch.php', ['id' => $id]));
