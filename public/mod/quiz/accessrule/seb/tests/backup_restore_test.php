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

namespace quizaccess_seb;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");
require_once(__DIR__ . '/test_helper_trait.php');

/**
 * PHPUnit tests for backup and restore functionality.
 *
 * @package   quizaccess_seb
 * @author    Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright 2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backup_restore_test extends \restore_date_testcase {
    use \quizaccess_seb_test_helper_trait;


    /** @var template $template A test template. */
    protected $template;

    /**
     * Called before every test.
     */
    public function setUp(): void {
        global $USER;

        parent::setUp();

        $this->resetAfterTest();
        $this->setAdminUser();

        $this->course = $this->getDataGenerator()->create_course();
        $this->template = $this->create_template();
        $this->user = $USER;
    }

    /**
     * A helper method to create a quiz with template usage of SEB.
     *
     * @return seb_quiz_settings
     */
    protected function create_quiz_with_template() {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $quizsettings = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $quizsettings->set('requiresafeexambrowser', settings_provider::USE_SEB_TEMPLATE);
        $quizsettings->set('templateid', $this->template->get('id'));
        $quizsettings->save();

        return $quizsettings;
    }

    /**
     * A helper method to emulate backup and restore of the quiz.
     *
     * @return \cm_info|null
     */
    protected function backup_and_restore_quiz() {
        return \core_courseformat\formatactions::cm($this->course)->duplicate($this->quiz->cmid);
    }

    /**
     * A helper method to backup test quiz.
     *
     * @return mixed A backup ID ready to be restored.
     */
    protected function backup_quiz() {
        global $CFG;

        // Get the necessary files to perform backup and restore.
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $backupid = 'test-seb-backup-restore';

        $bc = new \backup_controller(\backup::TYPE_1ACTIVITY, $this->quiz->coursemodule, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $this->user->id);
        $bc->execute_plan();

        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        return $backupid;
    }

    /**
     * A helper method to restore provided backup.
     *
     * @param string $backupid Backup ID to restore.
     */
    protected function restore_quiz($backupid) {
        $rc = new \restore_controller($backupid, $this->course->id,
            \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $this->user->id, \backup::TARGET_CURRENT_ADDING);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
    }

    /**
     * A helper method to emulate restoring to a different site.
     */
    protected function change_site() {
        set_config('siteidentifier', random_string(32) . 'not the same site');
    }

    /**
     * A helper method to validate backup and restore results.
     *
     * @param cm_info $newcm Restored course_module object.
     */
    protected function validate_backup_restore(\cm_info $newcm) {
        $this->assertEquals(2, seb_quiz_settings::count_records());
        $actual = seb_quiz_settings::get_record(['quizid' => $newcm->instance]);

        $expected = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $this->assertEquals($expected->get('templateid'), $actual->get('templateid'));
        $this->assertEquals($expected->get('requiresafeexambrowser'), $actual->get('requiresafeexambrowser'));
        $this->assertEquals($expected->get('showsebdownloadlink'), $actual->get('showsebdownloadlink'));
        $this->assertEquals($expected->get('allowuserquitseb'), $actual->get('allowuserquitseb'));
        $this->assertEquals($expected->get('quitpassword'), $actual->get('quitpassword'));
        $this->assertEquals($expected->get('allowedbrowserexamkeys'), $actual->get('allowedbrowserexamkeys'));

        // Validate specific SEB config settings.
        foreach (settings_provider::get_seb_config_elements() as $name => $notused) {
            $name = preg_replace("/^seb_/", "", $name);
            $this->assertEquals($expected->get($name), $actual->get($name));
        }
    }

    /**
     * Test backup and restore when no seb.
     */
    public function test_backup_restore_no_seb(): void {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_NO);
        $this->assertEquals(0, seb_quiz_settings::count_records());

        $this->backup_and_restore_quiz();
        $this->assertEquals(0, seb_quiz_settings::count_records());
    }

    /**
     * Test backup and restore when manually configured.
     */
    public function test_backup_restore_manual_config(): void {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $expected = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $expected->set('showsebdownloadlink', 0);
        $expected->set('quitpassword', '123');
        $expected->save();

        $this->assertEquals(1, seb_quiz_settings::count_records());

        $newcm = $this->backup_and_restore_quiz();
        $this->validate_backup_restore($newcm);
    }

    /**
     * Test backup and restore when using template.
     */
    public function test_backup_restore_template_config(): void {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $expected = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $template = $this->create_template();
        $expected->set('requiresafeexambrowser', settings_provider::USE_SEB_TEMPLATE);
        $expected->set('templateid', $template->get('id'));
        $expected->save();

        $this->assertEquals(1, seb_quiz_settings::count_records());

        $newcm = $this->backup_and_restore_quiz();
        $this->validate_backup_restore($newcm);
    }

    /**
     * Test backup and restore when using template when said template is disabled.
     *
     * @covers \quizaccess_seb\seb_quiz_settings::get_record
     * @covers \restore_quizaccess_seb_subplugin::process_quizaccess_seb_quizsettings
     */
    public function test_backup_restore_disabled_template_config(): void {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $expected = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $template = $this->create_template();
        $expected->set('requiresafeexambrowser', settings_provider::USE_SEB_TEMPLATE);
        $expected->set('templateid', $template->get('id'));
        $expected->save();

        // Disable template.
        $template->set('enabled', 0);
        $template->save();

        $this->assertEquals(1, seb_quiz_settings::count_records());

        $newcm = $this->backup_and_restore_quiz();

        $this->assertEquals(2, seb_quiz_settings::count_records());
        $actual = seb_quiz_settings::get_record(['quizid' => $newcm->instance]);

        // Test that the restored quiz no longer uses SEB.
        $expected = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $this->assertEquals(0, $actual->get('templateid'));
        $this->assertEquals(settings_provider::USE_SEB_NO, $actual->get('requiresafeexambrowser'));
        $this->assertEquals($expected->get('showsebdownloadlink'), $actual->get('showsebdownloadlink'));
        $this->assertEquals($expected->get('allowuserquitseb'), $actual->get('allowuserquitseb'));
        $this->assertEquals($expected->get('quitpassword'), $actual->get('quitpassword'));
        $this->assertEquals($expected->get('allowedbrowserexamkeys'), $actual->get('allowedbrowserexamkeys'));

        // Validate specific SEB config settings.
        foreach (settings_provider::get_seb_config_elements() as $name => $notused) {
            $name = preg_replace("/^seb_/", "", $name);
            $this->assertEquals($expected->get($name), $actual->get($name));
        }
    }

    /**
     * Test backup and restore when using uploaded file.
     */
    public function test_backup_restore_uploaded_config(): void {
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);

        $expected = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $expected->set('requiresafeexambrowser', settings_provider::USE_SEB_UPLOAD_CONFIG);
        $xml = file_get_contents(self::get_fixture_path(__NAMESPACE__, 'unencrypted.seb'));
        $this->create_module_test_file($xml, $this->quiz->cmid);
        $expected->save();

        $this->assertEquals(1, seb_quiz_settings::count_records());

        $newcm = $this->backup_and_restore_quiz();
        $this->validate_backup_restore($newcm);

        $expectedfile = settings_provider::get_module_context_sebconfig_file($this->quiz->cmid);
        $actualfile = settings_provider::get_module_context_sebconfig_file($newcm->id);

        $this->assertEquals($expectedfile->get_content(), $actualfile->get_content());
    }

    /**
     * No new template should be restored if restoring to a different site,
     * but the template with  the same name and content exists..
     */
    public function test_restore_template_to_a_different_site_when_the_same_template_exists(): void {
        $this->create_quiz_with_template();
        $backupid = $this->backup_quiz();

        $this->assertEquals(1, seb_quiz_settings::count_records());
        $this->assertEquals(1, template::count_records());

        $this->change_site();
        $this->restore_quiz($backupid);

        // Should see additional setting record, but no new template record.
        $this->assertEquals(2, seb_quiz_settings::count_records());
        $this->assertEquals(1, template::count_records());
    }

    /**
     * A new template should be restored if restoring to a different site, but existing template
     * has the same content, but different name.
     */
    public function test_restore_template_to_a_different_site_when_the_same_content_but_different_name(): void {
        $this->create_quiz_with_template();
        $backupid = $this->backup_quiz();

        $this->assertEquals(1, seb_quiz_settings::count_records());
        $this->assertEquals(1, template::count_records());

        $this->template->set('name', 'New name for template');
        $this->template->save();

        $this->change_site();
        $this->restore_quiz($backupid);

        // Should see additional setting record, and new template record.
        $this->assertEquals(2, seb_quiz_settings::count_records());
        $this->assertEquals(2, template::count_records());
    }

    /**
     * A new template should be restored if restoring to a different site, but existing template
     * has the same name, but different content.
     */
    public function test_restore_template_to_a_different_site_when_the_same_name_but_different_content(): void {
        global $CFG;

        $this->create_quiz_with_template();
        $backupid = $this->backup_quiz();

        $this->assertEquals(1, seb_quiz_settings::count_records());
        $this->assertEquals(1, template::count_records());

        $newxml = file_get_contents($CFG->dirroot . '/mod/quiz/accessrule/seb/tests/fixtures/simpleunencrypted.seb');
        $this->template->set('content', $newxml);
        $this->template->save();

        $this->change_site();
        $this->restore_quiz($backupid);

        // Should see additional setting record, and new template record.
        $this->assertEquals(2, seb_quiz_settings::count_records());
        $this->assertEquals(2, template::count_records());
    }

    /**
     * Test backup and restore seb settings with an override.
     *
     * @covers \backup_quizaccess_seb_subplugin::define_quiz_subplugin_structure
     * @covers \restore_quizaccess_seb_subplugin::process_quizaccess_seb_override
     * @covers \restore_quizaccess_seb_subplugin::after_restore_quiz
     */
    public function test_backup_restore_override(): void {
        global $DB;
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);

        // Create SEB settings for quiz.
        $seb = seb_quiz_settings::get_record(['quizid' => $this->quiz->id]);
        $seb->set('showsebdownloadlink', 0);
        $seb->set('quitpassword', '123');
        $seb->save();

        $this->assertEquals(1, seb_quiz_settings::count_records());
        $this->assertEquals(0, $DB->count_records('quiz_overrides'));
        $this->assertEquals(0, $DB->count_records('quizaccess_seb_override'));

        // Create an override.
        $overrideid = $this->save_override($this->user);

        $this->assertEquals(1, seb_quiz_settings::count_records());
        $this->assertEquals(1, $DB->count_records('quiz_overrides'));
        $this->assertEquals(1, $DB->count_records('quizaccess_seb_override'));

        // Backup and count override records.
        $this->backup_and_restore($this->course);

        $this->assertEquals(2, seb_quiz_settings::count_records());
        $this->assertEquals(2, $DB->count_records('quiz_overrides'));
        $this->assertEquals(2, $DB->count_records('quizaccess_seb_override'));

        // Check values are as expected.
        $override = $DB->get_record('quiz_overrides', ['id' => $overrideid]);
        $seboverride = $DB->get_record('quizaccess_seb_override', ['overrideid' => $overrideid]);
        $restoredoverride = $DB->get_record_sql("SELECT * FROM {quiz_overrides} WHERE id <> ?", [$overrideid]);
        $restoredseboverride = $DB->get_record_sql("SELECT * FROM {quizaccess_seb_override} WHERE overrideid <> ?", [$overrideid]);

        $this->assertEquals($override->id, $seboverride->overrideid);
        $this->assertEquals($restoredoverride->id, $restoredseboverride->overrideid);
        $this->assertNotEquals($override->id, $restoredoverride->id);

        // Compare override settings to make sure nothing is lost.
        // Exclude comparing the following values as they are expected to differ.
        $exclude = ['id', 'overrideid', 'usermodified', 'timecreated', 'timemodified'];
        $keys = array_diff(array_keys(get_object_vars($seboverride)), $exclude);
        foreach ($keys as $key) {
            $this->assertEquals($seboverride->{$key}, $restoredseboverride->{$key});
        }
    }


    /**
     * Test backup and restore course and quiz with only an SEB override.
     *
     * @covers \backup_quizaccess_seb_subplugin::define_quiz_subplugin_structure
     * @covers \restore_quizaccess_seb_subplugin::process_quizaccess_seb_override
     * @covers \restore_quizaccess_seb_subplugin::after_restore_quiz
     */
    public function test_backup_restore_override_no_seb(): void {
        global $DB;
        $this->quiz = $this->create_test_quiz($this->course, settings_provider::USE_SEB_CONFIG_MANUALLY);
        $this->user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);

        $this->assertEquals(1, seb_quiz_settings::count_records());
        $this->assertEquals(0, $DB->count_records('quiz_overrides'));
        $this->assertEquals(0, $DB->count_records('quizaccess_seb_override'));

        // Create an override.
        $overrideid = $this->save_override($this->user);

        $this->assertEquals(1, seb_quiz_settings::count_records());
        $this->assertEquals(1, $DB->count_records('quiz_overrides'));
        $this->assertEquals(1, $DB->count_records('quizaccess_seb_override'));

        // Backup and count override records.
        $this->backup_and_restore($this->course);

        $this->assertEquals(2, seb_quiz_settings::count_records());
        $this->assertEquals(2, $DB->count_records('quiz_overrides'));
        $this->assertEquals(2, $DB->count_records('quizaccess_seb_override'));

        // Check values are as expected.
        $override = $DB->get_record('quiz_overrides', ['id' => $overrideid]);
        $seboverride = $DB->get_record('quizaccess_seb_override', ['overrideid' => $overrideid]);
        $restoredoverride = $DB->get_record_sql("SELECT * FROM {quiz_overrides} WHERE id <> ?", [$overrideid]);
        $restoredseboverride = $DB->get_record_sql("SELECT * FROM {quizaccess_seb_override} WHERE overrideid <> ?", [$overrideid]);

        $this->assertEquals($override->id, $seboverride->overrideid);
        $this->assertEquals($restoredoverride->id, $restoredseboverride->overrideid);
        $this->assertNotEquals($override->id, $restoredoverride->id);

        // Compare override settings to make sure nothing is lost.
        // Exclude comparing the following values as they are expected to differ.
        $exclude = ['id', 'overrideid', 'usermodified', 'timecreated', 'timemodified'];
        $keys = array_diff(array_keys(get_object_vars($seboverride)), $exclude);
        foreach ($keys as $key) {
            $this->assertEquals($seboverride->{$key}, $restoredseboverride->{$key});
        }
    }

}
