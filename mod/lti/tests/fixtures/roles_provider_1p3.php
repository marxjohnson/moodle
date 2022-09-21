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
 * Testing fixture for LTI v1.3 roles
 *
 * When used as an LTI tool, this will display a list of IMS roles sent from Moodle to the tool.
 *
 * To use this fixture, go to Site Administration > Plugins > Activity modules > External Tool > Manage tools and follow
 * "configure a tool manually". Create a new tool configuration with the URL of this script as the Tool URL, LTI 1.3 as the LTI
 * version, and the login_initiator.php script in this fixtures directory as the "Initiate login URL".
 * Then add an External Tool activity to a course, using this tool configuration.
 *
 * @package   mod_lti
 * @copyright 2022 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

use enrol_lti\local\ltiadvantage\lib\http_client;
use enrol_lti\local\ltiadvantage\lib\issuer_database;
use enrol_lti\local\ltiadvantage\lib\launch_cache_session;
use enrol_lti\local\ltiadvantage\repository\application_registration_repository;
use enrol_lti\local\ltiadvantage\repository\deployment_repository;
use Packback\Lti1p3\ImsStorage\ImsCookie;
use Packback\Lti1p3\LtiMessageLaunch;
use Packback\Lti1p3\LtiServiceConnector;
use Packback\Lti1p3\LtiException;

require_once($CFG->libdir . '/filelib.php');

require_login();
if ($CFG->debug !== DEBUG_DEVELOPER || defined('BEHAT_SITE_RUNNING')) {
    die('This page is for testing only.');
}
$idtoken = optional_param('id_token', null, PARAM_RAW);
$sesscache = new launch_cache_session();
$issdb = new issuer_database(new application_registration_repository(), new deployment_repository());
$cookie = new ImsCookie();
$serviceconnector = new LtiServiceConnector($sesscache, new http_client(new curl()));
if ($idtoken) {
    // Attempt to parse and validate the signed JWT. This will fail as we don't have a nonce, application registration, deployment,
    // or the public key to verify the signature. However, it will parse and decode JWT body, so we can see what was sent.
    $messagelaunch = LtiMessageLaunch::new($issdb, $sesscache, $cookie, $serviceconnector);
    try {
        $messagelaunch->validate(['id_token' => $idtoken, 'state' => 'test']);
    } catch (LtiException $e) {
        if (in_array($e->getMessage(), [
            LtiMessageLaunch::ERR_STATE_NOT_FOUND,
            LtiMessageLaunch::ERR_MISSING_ID_TOKEN,
            LtiMessageLaunch::ERR_INVALID_ID_TOKEN,
        ])) {
            throw $e;
        }
        // If we got this far, we don't actually care if the rest of the request validates, we've successfully
        // parsed the message body which is enough for testing.
    } finally {
        $launchdata = $messagelaunch->getLaunchData();
    }
}

?>
<html>
    <head>
        <title>Testing fixture for LTI roles</title>
    </head>
    <body>
        <p>The LTI provider received the following roles for the current user:</p>
        <ul>
            <?php
            foreach ($launchdata['https://purl.imsglobal.org/spec/lti/claim/roles'] as $role) {
                echo '<li>' . $role . '</li>' . PHP_EOL;
            }
            ?>
        </ul>
    </body>
</html>
