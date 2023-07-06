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
 * Unit tests for export/import description (info) for question category in the Moodle XML format.
 *
 * @package    qformat_xml
 * @copyright  2014 Nikita Nikitsky, Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_question\local\bank\question_edit_contexts;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Unit tests for the XML question format import and export.
 *
 * @copyright  2014 Nikita Nikitsky, Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_xml_import_export_test extends advanced_testcase {
    /**
     * Create object qformat_xml for test.
     * @param string $filename with name for testing file.
     * @param stdClass $course
     * @return qformat_xml XML question format object.
     */
    public function create_qformat($filename, $course) {
        $qformat = new qformat_xml();
        $qformat->setContexts((new question_edit_contexts(context_course::instance($course->id)))->all());
        $qformat->setCourse($course);
        $qformat->setFilename(__DIR__ . '/fixtures/' . $filename);
        $qformat->setRealfilename($filename);
        $qformat->setMatchgrades('error');
        $qformat->setCatfromfile(1);
        $qformat->setContextfromfile(1);
        $qformat->setStoponerror(1);
        $qformat->setCattofile(1);
        $qformat->setContexttofile(1);
        $qformat->set_display_progress(false);

        return $qformat;
    }

    /**
     * Check xml for compliance.
     * @param string $expectedxml with correct string.
     * @param string $xml you want to check.
     */
    public function assert_same_xml($expectedxml, $xml) {
        $this->assertEquals($this->normalise_xml($expectedxml),
                $this->normalise_xml($xml));
    }

    /**
     * Clean up some XML to remove irrelevant differences, before it is compared.
     * @param string $xml some XML.
     * @return string cleaned-up XML.
     */
    protected function normalise_xml($xml) {
        // Normalise line endings.
        $xml = str_replace("\r\n", "\n", $xml);
        $xml = preg_replace("~\n$~", "", $xml); // Strip final newline in file.

        // Replace all numbers in question id comments with 0.
        $xml = preg_replace('~(?<=<!-- question: )([0-9]+)(?=  -->)~', '0', $xml);

        // Deal with how different databases output numbers. Only match when only thing in a tag.
        $xml = preg_replace("~>.0000000<~", '>0<', $xml); // How Oracle outputs 0.0000000.
        $xml = preg_replace("~(\.(:?[0-9]*[1-9])?)0*<~", '$1<', $xml); // Other cases of trailing 0s
        $xml = preg_replace("~([0-9]).<~", '$1<', $xml); // Stray . in 1. after last step.

        return $xml;
    }

    /**
     * Check imported category.
     * @param string $name imported category name.
     * @param string $info imported category info field (description of category).
     * @param int $infoformat imported category info field format.
     */
    public function assert_category_imported($name, $info, $infoformat, $idnumber = null) {
        global $DB;
        $category = $DB->get_record('question_categories', ['name' => $name], '*', MUST_EXIST);
        $this->assertEquals($info, $category->info);
        $this->assertEquals($infoformat, $category->infoformat);
        $this->assertSame($idnumber, $category->idnumber);
    }

    /**
     * Check a question category has a given parent.
     * @param string $catname Name of the question category
     * @param string $parentname Name of the parent category
     * @throws dml_exception
     */
    public function assert_category_has_parent($catname, $parentname) {
        global $DB;
        $sql = 'SELECT qc1.*
                  FROM {question_categories} qc1
                  JOIN {question_categories} qc2 ON qc1.parent = qc2.id
                 WHERE qc1.name = ?
                   AND qc2.name = ?';
        $categories = $DB->get_records_sql($sql, [$catname, $parentname]);
        $this->assertTrue(count($categories) == 1);
    }

    /**
     * Get a question by name, including the question category ID.
     *
     * @param string $qname The question name
     * @return \stdClass The question record.
     * @throws dml_exception If the question is not found.
     */
    protected function get_question(string $qname): \stdClass {
        global $DB;
        $sql = "SELECT q.*, qbe.questioncategoryid AS category
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE q.name = :name";
        return $DB->get_record_sql($sql, ['name' => $qname], MUST_EXIST);
    }

    /**
     * Check a question exists in a category.
     * @param string $qname The name of the question
     * @param string $catname The name of the category
     * @throws dml_exception
     */
    public function assert_question_in_category($qname, $catname) {
        global $DB;

        $question = $this->get_question($qname);
        $category = $DB->get_record('question_categories', ['name' => $catname], '*', MUST_EXIST);
        $this->assertEquals($category->id, $question->category);
    }

    /**
     * Simple check for importing a category with a description.
     */
    public function test_import_category() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $qformat = $this->create_qformat('category_with_description.xml', $course);
        $imported = $qformat->importprocess();
        $this->assertTrue($imported);
        $this->assert_category_imported('Alpha',
                'This is Alpha category for test', FORMAT_MOODLE, 'alpha-idnumber');
        $this->assert_category_has_parent('Alpha', 'top');
    }

    /**
     * Check importing nested categories.
     */
    public function test_import_nested_categories() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $qformat = $this->create_qformat('nested_categories.xml', $course);
        $imported = $qformat->importprocess();
        $this->assertTrue($imported);
        $this->assert_category_imported('Delta', 'This is Delta category for test', FORMAT_PLAIN);
        $this->assert_category_imported('Epsilon', 'This is Epsilon category for test', FORMAT_MARKDOWN);
        $this->assert_category_imported('Zeta', 'This is Zeta category for test', FORMAT_MOODLE);
        $this->assert_category_has_parent('Delta', 'top');
        $this->assert_category_has_parent('Epsilon', 'Delta');
        $this->assert_category_has_parent('Zeta', 'Epsilon');
    }

    /**
     * Check importing nested categories contain the right questions.
     */
    public function test_import_nested_categories_with_questions() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $qformat = $this->create_qformat('nested_categories_with_questions.xml', $course);
        $imported = $qformat->importprocess();
        $this->assertTrue($imported);
        $this->assert_category_imported('Iota', 'This is Iota category for test', FORMAT_PLAIN);
        $this->assert_category_imported('Kappa', 'This is Kappa category for test', FORMAT_MARKDOWN);
        $this->assert_category_imported('Lambda', 'This is Lambda category for test', FORMAT_MOODLE);
        $this->assert_category_imported('Mu', 'This is Mu category for test', FORMAT_MOODLE);
        $this->assert_question_in_category('Iota Question', 'Iota');
        $this->assert_question_in_category('Kappa Question', 'Kappa');
        $this->assert_question_in_category('Lambda Question', 'Lambda');
        $this->assert_question_in_category('Mu Question', 'Mu');
        $this->assert_category_has_parent('Iota', 'top');
        $this->assert_category_has_parent('Kappa', 'Iota');
        $this->assert_category_has_parent('Lambda', 'Kappa');
        $this->assert_category_has_parent('Mu', 'Iota');
    }

    /**
     * Check import of an old file (without format), for backward compatability.
     */
    public function test_import_old_format() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $qformat = $this->create_qformat('old_format_file.xml', $course);
        $imported = $qformat->importprocess();
        $this->assertTrue($imported);
        $this->assert_category_imported('Pi', '', FORMAT_MOODLE);
        $this->assert_category_imported('Rho', '', FORMAT_MOODLE);
        $this->assert_question_in_category('Pi Question', 'Pi');
        $this->assert_question_in_category('Rho Question', 'Rho');
        $this->assert_category_has_parent('Pi', 'top');
        $this->assert_category_has_parent('Rho', 'Pi');
    }

    /**
     * Check the import of an xml file where the child category exists before the parent category.
     */
    public function test_import_categories_in_reverse_order() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $qformat = $this->create_qformat('categories_reverse_order.xml', $course);
        $imported = $qformat->importprocess();
        $this->assertTrue($imported);
        $this->assert_category_imported('Sigma', 'This is Sigma category for test', FORMAT_HTML);
        $this->assert_category_imported('Tau', 'This is Tau category for test', FORMAT_HTML);
        $this->assert_question_in_category('Sigma Question', 'Sigma');
        $this->assert_question_in_category('Tau Question', 'Tau');
        $this->assert_category_has_parent('Sigma', 'top');
        $this->assert_category_has_parent('Tau', 'Sigma');
    }

    /**
     * Simple check for exporting a category.
     */
    public function test_export_category() {
        global $SITE;

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->resetAfterTest();
        $this->setAdminUser();
        // Note while this loads $qformat with all the 'right' data from the xml file,
        // the call to setCategory, followed by exportprocess will actually only export data
        // from the database (created by the generator).
        $qformat = $this->create_qformat('export_category.xml', $SITE);

        $category = $generator->create_question_category([
                'name' => 'Alpha',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Alpha category for test',
                'infoformat' => '0',
                'idnumber' => 'alpha-idnumber',
                'stamp' => make_unique_id_code(),
                'parent' => '0',
                'sortorder' => '999']);
        $question = $generator->create_question('truefalse', null, [
                'category' => $category->id,
                'name' => 'Alpha Question',
                'questiontext' => ['format' => '1', 'text' => '<p>Testing Alpha Question</p>'],
                'generalfeedback' => ['format' => '1', 'text' => ''],
                'correctanswer' => '1',
                'feedbacktrue' => ['format' => '1', 'text' => ''],
                'feedbackfalse' => ['format' => '1', 'text' => ''],
                'penalty' => '1']);
        $qformat->setCategory($category);

        $expectedxml = file_get_contents(__DIR__ . '/fixtures/export_category.xml');
        $this->assert_same_xml($expectedxml, $qformat->exportprocess());
    }

    /**
     * Check exporting nested categories.
     */
    public function test_export_nested_categories() {
        global $SITE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qformat = $this->create_qformat('nested_categories.zml', $SITE);

        $categorydelta = $generator->create_question_category([
                'name' => 'Delta',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Delta category for test',
                'infoformat' => '2',
                'stamp' => make_unique_id_code(),
                'parent' => '0',
                'sortorder' => '999']);
        $categoryepsilon = $generator->create_question_category([
                'name' => 'Epsilon',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Epsilon category for test',
                'infoformat' => '4',
                'stamp' => make_unique_id_code(),
                'parent' => $categorydelta->id,
                'sortorder' => '999']);
        $categoryzeta = $generator->create_question_category([
                'name' => 'Zeta',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Zeta category for test',
                'infoformat' => '0',
                'stamp' => make_unique_id_code(),
                'parent' => $categoryepsilon->id,
                'sortorder' => '999']);
        $question  = $generator->create_question('truefalse', null, [
                'category' => $categoryzeta->id,
                'name' => 'Zeta Question',
                'questiontext' => [
                                'format' => '1',
                                'text' => '<p>Testing Zeta Question</p>'],
                'generalfeedback' => ['format' => '1', 'text' => ''],
                'correctanswer' => '1',
                'feedbacktrue' => ['format' => '1', 'text' => ''],
                'feedbackfalse' => ['format' => '1', 'text' => ''],
                'penalty' => '1']);
        $qformat->setCategory($categorydelta);
        $qformat->setCategory($categoryepsilon);
        $qformat->setCategory($categoryzeta);

        $expectedxml = file_get_contents(__DIR__ . '/fixtures/nested_categories.xml');
        $this->assert_same_xml($expectedxml, $qformat->exportprocess());
    }

    /**
     * Check exporting nested categories contain the right questions.
     */
    public function test_export_nested_categories_with_questions() {
        global $SITE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $qformat = $this->create_qformat('nested_categories_with_questions.xml', $SITE);

        $categoryiota = $generator->create_question_category([
                'name' => 'Iota',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Iota category for test',
                'infoformat' => '2',
                'stamp' => make_unique_id_code(),
                'parent' => '0',
                'sortorder' => '999']);
        $iotaquestion  = $generator->create_question('truefalse', null, [
                'category' => $categoryiota->id,
                'name' => 'Iota Question',
                'questiontext' => [
                        'format' => '1',
                        'text' => '<p>Testing Iota Question</p>'],
                'generalfeedback' => ['format' => '1', 'text' => ''],
                'correctanswer' => '1',
                'feedbacktrue' => ['format' => '1', 'text' => ''],
                'feedbackfalse' => ['format' => '1', 'text' => ''],
                'penalty' => '1']);
        $categorykappa = $generator->create_question_category([
                'name' => 'Kappa',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Kappa category for test',
                'infoformat' => '4',
                'stamp' => make_unique_id_code(),
                'parent' => $categoryiota->id,
                'sortorder' => '999']);
        $kappaquestion  = $generator->create_question('essay', null, [
                'category' => $categorykappa->id,
                'name' => 'Kappa Essay Question',
                'questiontext' => ['text' => 'Testing Kappa Essay Question'],
                'generalfeedback' => '',
                'responseformat' => 'editor',
                'responserequired' => 1,
                'responsefieldlines' => 10,
                'attachments' => 0,
                'attachmentsrequired' => 0,
                'graderinfo' => ['format' => '1', 'text' => ''],
                'responsetemplate' => ['format' => '1', 'text' => ''],
                'idnumber' => '']);
        $kappaquestion1  = $generator->create_question('truefalse', null, [
                'category' => $categorykappa->id,
                'name' => 'Kappa Question',
                'questiontext' => [
                        'format' => '1',
                        'text' => '<p>Testing Kappa Question</p>'],
                'generalfeedback' => ['format' => '1', 'text' => ''],
                'correctanswer' => '1',
                'feedbacktrue' => ['format' => '1', 'text' => ''],
                'feedbackfalse' => ['format' => '1', 'text' => ''],
                'penalty' => '1',
                'idnumber' => '']);
        $categorylambda = $generator->create_question_category([
                'name' => 'Lambda',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Lambda category for test',
                'infoformat' => '0',
                'stamp' => make_unique_id_code(),
                'parent' => $categorykappa->id,
                'sortorder' => '999']);
        $lambdaquestion  = $generator->create_question('truefalse', null, [
                'category' => $categorylambda->id,
                'name' => 'Lambda Question',
                'questiontext' => [
                        'format' => '1',
                        'text' => '<p>Testing Lambda Question</p>'],
                'generalfeedback' => ['format' => '1', 'text' => ''],
                'correctanswer' => '1',
                'feedbacktrue' => ['format' => '1', 'text' => ''],
                'feedbackfalse' => ['format' => '1', 'text' => ''],
                'penalty' => '1']);
        $categorymu = $generator->create_question_category([
                'name' => 'Mu',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Mu category for test',
                'infoformat' => '0',
                'stamp' => make_unique_id_code(),
                'parent' => $categoryiota->id,
                'sortorder' => '999']);
        $muquestion  = $generator->create_question('truefalse', null, [
                'category' => $categorymu->id,
                'name' => 'Mu Question',
                'questiontext' => [
                        'format' => '1',
                        'text' => '<p>Testing Mu Question</p>'],
                'generalfeedback' => ['format' => '1', 'text' => ''],
                'correctanswer' => '1',
                'feedbacktrue' => ['format' => '1', 'text' => ''],
                'feedbackfalse' => ['format' => '1', 'text' => ''],
                'penalty' => '1']);
        $qformat->setCategory($categoryiota);

        $expectedxml = file_get_contents(__DIR__ . '/fixtures/nested_categories_with_questions.xml');
        $this->assert_same_xml($expectedxml, $qformat->exportprocess());
    }

    /**
     * Simple check for exporting a category.
     */
    public function test_export_category_with_special_chars() {
        global $SITE;

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->resetAfterTest();
        $this->setAdminUser();
        // Note while this loads $qformat with all the 'right' data from the xml file,
        // the call to setCategory, followed by exportprocess will actually only export data
        // from the database (created by the generator).
        $qformat = $this->create_qformat('export_category.xml', $SITE);

        $category = $generator->create_question_category([
                'name' => 'Alpha',
                'contextid' => context_course::instance($SITE->id)->id,
                'info' => 'This is Alpha category for test',
                'infoformat' => '0',
                'idnumber' => 'The inequalities < & >',
                'stamp' => make_unique_id_code(),
                'parent' => '0',
                'sortorder' => '999']);
        $generator->create_question('truefalse', null, [
                'category' => $category->id,
                'name' => 'Alpha Question',
                'questiontext' => ['format' => '1', 'text' => '<p>Testing Alpha Question</p>'],
                'generalfeedback' => ['format' => '1', 'text' => ''],
                'idnumber' => 'T & F',
                'correctanswer' => '1',
                'feedbacktrue' => ['format' => '1', 'text' => ''],
                'feedbackfalse' => ['format' => '1', 'text' => ''],
                'penalty' => '1']);
        $qformat->setCategory($category);

        $expectedxml = file_get_contents(__DIR__ . '/fixtures/html_chars_in_idnumbers.xml');
        $this->assert_same_xml($expectedxml, $qformat->exportprocess());
    }

    /**
     * Test that bad multianswer questions are not imported.
     */
    public function test_import_broken_multianswer_questions() {
        $lines = file(__DIR__ . '/fixtures/broken_cloze_questions.xml');
        $importer = $qformat = new qformat_xml();

        // The importer echoes some errors, so we need to capture and check that.
        ob_start();
        $questions = $importer->readquestions($lines);
        $output = ob_get_contents();
        ob_end_clean();

        // Check that there were some expected errors.
        $this->assertStringContainsString('Error importing question', $output);
        $this->assertStringContainsString('Invalid embedded answers (Cloze) question', $output);
        $this->assertStringContainsString('This type of question requires at least 2 choices', $output);
        $this->assertStringContainsString('The answer must be a number, for example -1.234 or 3e8, or \'*\'.', $output);
        $this->assertStringContainsString('One of the answers should have a score of 100% so it is possible to get full marks for this question.',
                $output);
        $this->assertStringContainsString('The question text must include at least one embedded answer.', $output);

        // No question  have been imported.
        $this->assertCount(0, $questions);
    }

    /**
     * Create a custom field category for qbank_customfields, and generate 2 custom fields.
     *
     * @return array
     */
    protected function create_custom_fields(): array {
        $fieldcategory = (object)[
            'component' => 'qbank_customfields',
            'area' => 'question',
        ];

        $generator = $this->getDataGenerator();
        $catid = $generator->create_custom_field_category($fieldcategory)->get('id');
        return [
            $generator->create_custom_field(['categoryid' => $catid, 'type' => 'checkbox', 'shortname' => 'yesno']),
            $generator->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'something']),
        ];
    }

    /**
     * Import the question with customfields plugin data defined in question_with_qbank_plugin_data.xml
     *
     * @return array[] The expected fields and data that were imported with the question.
     */
    protected function import_plugin_data(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $qcategory = $generator->get_plugin_generator('core_question')->create_question_category([
            'contextid' => context_course::instance($course->id)->id,
        ]);

        [$customfield1, $customfield2] = $this->create_custom_fields();
        $expectedfields = [
            $customfield1->get('id') => $customfield1,
            $customfield2->get('id') => $customfield2,
        ];

        $qformat = $this->create_qformat('question_with_qbank_plugin_data.xml', $course);
        $expecteddata = [
            $customfield1->get('shortname') => 1,
            $customfield2->get('shortname') => 'Foobar',
        ];

        $qformat->setCategory($qcategory);
        $imported = $qformat->importprocess();
        $this->assertTrue($imported);
        return [$expectedfields, $expecteddata];
    }

    /**
     * Test that additional data for qbank plugins gets imported correctly.
     *
     * @return void
     * @covers \qformat_xml::import_qbank_plugins
     */
    public function test_import_plugin_data(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$expectedfields, $expecteddata] = $this->import_plugin_data();

        $question = $this->get_question('True/false question');
        $customfieldhandler = \qbank_customfields\customfield\question_handler::create();
        foreach ($customfieldhandler->get_instance_data($question->id) as $datacontroller) {
            $expectedfield = $expectedfields[$datacontroller->get_field()->get('id')];
            $this->assertEquals($expecteddata[$expectedfield->get('shortname')], $datacontroller->get_value());
        }
    }

    /**
     * Test that additional data for qbank plugins is ignored on import if the plugin is disabled.
     *
     * @return void
     * @covers \qformat_xml::import_qbank_plugins
     */
    public function test_import_disabled_plugin_data(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $class = \core_plugin_manager::resolve_plugininfo_class('qbank');
        $class::enable_plugin('customfields', false);

        $this->import_plugin_data();

        $question = $this->get_question('True/false question');
        $customfieldhandler = \qbank_customfields\customfield\question_handler::create();
        foreach ($customfieldhandler->get_instance_data($question->id) as $datacontroller) {
            // Custom fields should contain default empty values.
            $this->assertEmpty($datacontroller->get_value());
        }
    }

    /**
     * Test that exporting a question with additional data defined by a plugin produces the expected XML.
     *
     * @return void
     * @covers \qformat_xml::writequestion
     */
    public function test_export_plugin_data(): void {
        global $SITE, $CFG;
        require_once($CFG->dirroot . '/question/type/truefalse/questiontype.php');

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->resetAfterTest();
        $this->setAdminUser();
        // Note while this loads $qformat with all the 'right' data from the xml file,
        // the call to setCategory, followed by exportprocess will actually only export data
        // from the database (created by the generator).
        $qformat = $this->create_qformat('question_with_qbank_plugin_data.xml', $SITE);
        $qtype = new qtype_truefalse();

        $syscontext = context_system::instance();
        $category = $generator->create_question_category(['contextid' => $syscontext->id]);

        $fromform = \test_question_maker::get_question_form_data('truefalse');
        $fromform->category = $category->id . ',' . $syscontext->id;

        $question = (object)[
            'category' => $category->id,
            'qtype' => 'truefalse',
            'createdby' => 0,
        ];

        $question = $qtype->save_question($question, $fromform);
        $qformat->setQuestions([question_bank::load_question_data($question->id)]);
        $qformat->setCattofile(false);
        $qformat->setContexttofile(false);

        $this->create_custom_fields();
        $customfieldhandler = qbank_customfields\customfield\question_handler::create();
        $customfieldhandler->instance_form_save((object)[
            'id' => $question->id,
            'customfield_yesno' => 1,
            'customfield_something' => 'Foobar',
        ]);

        $expectedxml = file_get_contents(__DIR__ . '/fixtures/question_with_qbank_plugin_data.xml');
        $this->assert_same_xml($expectedxml, $qformat->exportprocess());
    }

}
