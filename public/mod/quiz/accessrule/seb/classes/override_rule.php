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

use mod_quiz\local\access_override_rule_base;
use MoodleQuickForm;
use context_module;
use context;

/**
 * Class override_rule
 *
 * @package    quizaccess_seb
 * @copyright  2025 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class override_rule extends access_override_rule_base {
    #[\Override]
    public static function add_form_fields(
        context_module $context,
        int $overrideid,
        object $quiz,
        MoodleQuickForm $mform,
    ): void {
        global $DB;
        $override = $DB->get_record('quizaccess_seb_override', ['overrideid' => $overrideid]) ?: null;
        $templateoptions = settings_provider::get_template_options($quiz->cmid);

        // Add header element.
        $mform->addElement('header', 'seb', get_string('seb', 'quizaccess_seb'));
        $mform->setExpanded('seb', $override && $override->enabled);

        // Enable Safe Exam Browser override.
        $mform->addElement('selectyesno', 'seb_enabled', get_string('enableoverride', 'quizaccess_seb'));
        $mform->setDefault(
            'seb_enabled',
            self::get_default_field('requiresafeexambrowser', 0, $override, $quiz),
        );

        // Require the use of Safe Exam Browser.
        if (settings_provider::can_override_donotrequire($context)) {
            $requireseboptions[settings_provider::USE_SEB_NO] = get_string('no');
        }

        if (
            settings_provider::can_configure_manually($context) ||
            settings_provider::is_conflicting_permissions($context)
        ) {
            $requireseboptions[settings_provider::USE_SEB_CONFIG_MANUALLY] = get_string('seb_use_manually', 'quizaccess_seb');
        }

        if (
            settings_provider::can_use_seb_template($context) ||
            settings_provider::is_conflicting_permissions($context)
        ) {
            if (!empty($templateoptions)) {
                $requireseboptions[settings_provider::USE_SEB_TEMPLATE] = get_string('seb_use_template', 'quizaccess_seb');
            }
        }

        $requireseboptions[settings_provider::USE_SEB_CLIENT_CONFIG] = get_string('seb_use_client', 'quizaccess_seb');

        $mform->addElement(
            'select',
            'seb_requiresafeexambrowser',
            get_string('seb_requiresafeexambrowser', 'quizaccess_seb'),
            $requireseboptions,
        );

        $mform->setType('seb_requiresafeexambrowser', PARAM_INT);
        $mform->setDefault(
            'seb_requiresafeexambrowser',
            self::get_default_field('requiresafeexambrowser', 0, $override, $quiz),
        );
        $mform->addHelpButton('seb_requiresafeexambrowser', 'seb_requiresafeexambrowser', 'quizaccess_seb');
        $mform->hideIf('seb_requiresafeexambrowser', 'seb_enabled', 0);

        if (settings_provider::is_conflicting_permissions($context)) {
            $mform->freeze('seb_requiresafeexambrowser');
        }

        // Safe Exam Browser config template.
        if (
            settings_provider::can_use_seb_template($context) ||
            settings_provider::is_conflicting_permissions($context)
        ) {
            $element = $mform->addElement(
                'select',
                'seb_templateid',
                get_string('seb_templateid', 'quizaccess_seb'),
                $templateoptions,
            );
        } else {
            $element = $mform->addElement('hidden', 'seb_templateid');
        }

        $mform->setType('seb_templateid', PARAM_INT);
        $mform->setDefault('seb_templateid', self::get_default_field('templateid', 0, $override, $quiz));
        $mform->addHelpButton('seb_templateid', 'seb_templateid', 'quizaccess_seb');
        $mform->hideIf('seb_templateid', 'seb_enabled', 0);

        if (settings_provider::is_conflicting_permissions($context)) {
            $mform->freeze('seb_templateid');
        }

        // Show Safe Exam browser download button.
        if (settings_provider::can_change_seb_showsebdownloadlink($context)) {
            $mform->addElement(
                'selectyesno',
                'seb_showsebdownloadlink',
                get_string('seb_showsebdownloadlink', 'quizaccess_seb'),
            );

            $mform->setType('seb_showsebdownloadlink', PARAM_BOOL);
            $mform->setDefault(
                'seb_showsebdownloadlink',
                self::get_default_field('showsebdownloadlink', 1, $override, $quiz),
            );
            $mform->addHelpButton('seb_showsebdownloadlink', 'seb_showsebdownloadlink', 'quizaccess_seb');
            $mform->hideIf('seb_showsebdownloadlink', 'seb_enabled', 0);
        }

        // Manual config elements.
        $defaults = settings_provider::get_seb_config_element_defaults();
        $types = settings_provider::get_seb_config_element_types();

        foreach (settings_provider::get_seb_config_elements() as $name => $type) {
            if (!settings_provider::can_manage_seb_config_setting($name, $context)) {
                $type = 'hidden';
            }

            $mform->addElement($type, $name, get_string($name, 'quizaccess_seb'));

            $mform->addHelpButton($name, $name, 'quizaccess_seb');
            $mform->setType($name, PARAM_BOOL);
            $mform->setDefault(
                $name,
                self::get_default_field($name, 1, $override, $quiz),
            );
            $mform->hideIf($name, 'seb_enabled', 0);

            if (isset($defaults[$name])) {
                $mform->setDefault(
                    $name,
                    self::get_default_field($name, $defaults[$name], $override, $quiz, true),
                );
            }

            if (isset($types[$name])) {
                $mform->setType($name, $types[$name]);
            }
        }

        // Allowed browser exam keys.
        if (settings_provider::can_change_seb_allowedbrowserexamkeys($context)) {
            $mform->addElement(
                'textarea',
                'seb_allowedbrowserexamkeys',
                get_string('seb_allowedbrowserexamkeys', 'quizaccess_seb'),
            );

            $mform->setType('seb_allowedbrowserexamkeys', PARAM_RAW);
            $mform->setDefault(
                'seb_allowedbrowserexamkeys',
                self::get_default_field('allowedbrowserexamkeys', '', $override, $quiz),
            );
            $mform->addHelpButton('seb_allowedbrowserexamkeys', 'seb_allowedbrowserexamkeys', 'quizaccess_seb');
            $mform->hideIf('seb_allowedbrowserexamkeys', 'seb_enabled', 0);
        }

        // Hideifs.
        foreach (settings_provider::get_quiz_hideifs() as $elname => $rules) {
            if ($mform->elementExists($elname)) {
                foreach ($rules as $hideif) {
                    $mform->hideIf(
                        $hideif->get_element(),
                        $hideif->get_dependantname(),
                        $hideif->get_condition(),
                        $hideif->get_dependantvalue(),
                    );
                }
            }
        }

        // Lock elements.
        if (settings_provider::is_conflicting_permissions($context)) {
            // Freeze common quiz settings.
            $mform->addElement('seb_enabled');
            $mform->freeze('seb_requiresafeexambrowser');
            $mform->freeze('seb_templateid');
            $mform->freeze('seb_showsebdownloadlink');
            $mform->freeze('seb_allowedbrowserexamkeys');

            $quizsettings = seb_quiz_settings::get_by_quiz_id((int) $quiz->id);

            // Remove template ID if not using template for this quiz.
            if (empty($quizsettings) || $quizsettings->get('requiresafeexambrowser') != settings_provider::USE_SEB_TEMPLATE) {
                $mform->removeElement('seb_templateid');
            }

            // Freeze all SEB specific settings.
            foreach (settings_provider::get_seb_config_elements() as $element => $type) {
                if ($mform->elementExists($element)) {
                    $mform->freeze($element);
                }
            }
        }
    }

    /**
     * Fetches the best suited default value for a field. If there is an override value set, use this.
     * If there's no override value, check if the quiz had SEB settings and use this value instead.
     * Otherwise, use the default value defined.
     *
     * @param string $field The field key to search $default and $override.
     * @param string $default The default form value.
     * @param \stdClass|null $override The override data object.
     * @param \stdClass $quiz The quiz data object.
     * @param bool $removeprefix Remove 'seb_' from the field key.
     * @return string
     */
    protected static function get_default_field(
        string $field,
        string $default,
        ?\stdClass $override,
        \stdClass $quiz,
        bool $removeprefix = false,
    ): string {
        if ($removeprefix) {
            $field = substr($field, 4);
        }
        return match (true) {
            isset($override->field) => $override->field,
            isset($quiz->$field)    => $quiz->$field,
            default                 => $default,
        };
    }

    #[\Override]
    public static function validate_form_fields(
        array $errors,
        array $data,
        array $files,
        context_module $context,
    ): array {
        $cmid = $context->instanceid;
        $quizid = get_module_from_cmid($cmid)[0]->id;

        if (!settings_provider::can_configure_seb($context)) {
            return $errors;
        }

        if (settings_provider::is_seb_settings_locked($quizid)) {
            return $errors;
        }

        if (settings_provider::is_conflicting_permissions($context)) {
            return $errors;
        }

        $settings = settings_provider::filter_plugin_settings((object) $data);

        // Validate basic settings using persistent class.
        $quizsettings = (new seb_quiz_settings())->from_record($settings);
        $quizsettings->set('cmid', $cmid);
        $quizsettings->set('quizid', $quizid);

        // Edge case for filemanager_sebconfig.
        if ($quizsettings->get('requiresafeexambrowser') == settings_provider::USE_SEB_UPLOAD_CONFIG) {
            $errorvalidatefile = settings_provider::validate_draftarea_configfile($data['filemanager_sebconfigfile']);
            if (!empty($errorvalidatefile)) {
                $errors['filemanager_sebconfigfile'] = $errorvalidatefile;
            }
        }

        // Edge case to force user to select a template.
        if ($quizsettings->get('requiresafeexambrowser') == settings_provider::USE_SEB_TEMPLATE) {
            if (empty($data['seb_templateid'])) {
                $errors['seb_templateid'] = get_string('invalidtemplate', 'quizaccess_seb');
            }
        }

        if ($quizsettings->get('requiresafeexambrowser') != settings_provider::USE_SEB_NO) {
            // Global settings may be active which require a quiz password to be set if using SEB.
            if (!empty(get_config('quizaccess_seb', 'quizpasswordrequired')) && empty($data['quizpassword'])) {
                $errors['quizpassword'] = get_string('passwordnotset', 'quizaccess_seb');
            }
        }

        return $errors;
    }

    #[\Override]
    public static function save_settings(array $override): void {
        global $DB, $USER;

        $defaults = [
            'seb_enabled' => 0,
            'seb_requiresafeexambrowser' => 0,
            'seb_templateid' => 0,
            'seb_allowedbrowserexamkeys' => '',
            'seb_showsebdownloadlink' => 1,
        ];
        $defaults += settings_provider::get_seb_config_element_defaults();

        foreach ($defaults as $key => $default) {
            if (!isset($override[$key])) {
                $override[$key] = $default;
            }
        }

        $seboverride = (object)[
            'overrideid'             => $override['overrideid'],
            'enabled'                => $override['seb_enabled'],
            'templateid'             => $override['seb_templateid'],
            'requiresafeexambrowser' => $override['seb_requiresafeexambrowser'],
            'showsebtaskbar'         => $override['seb_showsebtaskbar'],
            'showwificontrol'        => $override['seb_showwificontrol'],
            'showreloadbutton'       => $override['seb_showreloadbutton'],
            'showtime'               => $override['seb_showtime'],
            'showkeyboardlayout'     => $override['seb_showkeyboardlayout'],
            'allowuserquitseb'       => $override['seb_allowuserquitseb'],
            'quitpassword'           => $override['seb_quitpassword'],
            'linkquitseb'            => $override['seb_linkquitseb'],
            'userconfirmquit'        => $override['seb_userconfirmquit'],
            'enableaudiocontrol'     => $override['seb_enableaudiocontrol'],
            'muteonstartup'          => $override['seb_muteonstartup'],
            'allowcapturecamera'     => $override['seb_allowcapturecamera'],
            'allowcapturemicrophone' => $override['seb_allowcapturemicrophone'],
            'allowspellchecking'     => $override['seb_allowspellchecking'],
            'allowreloadinexam'      => $override['seb_allowreloadinexam'],
            'activateurlfiltering'   => $override['seb_activateurlfiltering'],
            'filterembeddedcontent'  => $override['seb_filterembeddedcontent'],
            'expressionsallowed'     => $override['seb_expressionsallowed'],
            'regexallowed'           => $override['seb_regexallowed'],
            'expressionsblocked'     => $override['seb_expressionsblocked'],
            'regexblocked'           => $override['seb_regexblocked'],
            'allowedbrowserexamkeys' => $override['seb_allowedbrowserexamkeys'],
            'showsebdownloadlink'    => $override['seb_showsebdownloadlink'],
            'usermodified'           => $USER->id,
            'timemodified'           => time(),
        ];

        if ($seboverrideid = $DB->get_field('quizaccess_seb_override', 'id', ['overrideid' => $override['overrideid']])) {
            $seboverride->id = $seboverrideid;
            $DB->update_record('quizaccess_seb_override', $seboverride);
        } else {
            $seboverride->timecreated = time();
            $DB->insert_record('quizaccess_seb_override', $seboverride);
        }

        // Delete cache.
        $quizid = $DB->get_field('quiz_overrides', 'quiz', ['id' => $override['overrideid']]);
        seb_quiz_settings::delete_cache("$quizid-{$override['overrideid']}");
    }

    #[\Override]
    public static function delete_settings(int $quizid, array $overrides): void {
        global $DB;
        $ids = array_column($overrides, 'id');
        [$insql, $inparams] = $DB->get_in_or_equal($ids);
        $DB->delete_records_select('quizaccess_seb_override', "id $insql", $inparams);

        foreach ($overrides as $override) {
            $key = "{$quizid}-{$override->id}";
            seb_quiz_settings::delete_cache($key);
        }
    }

    #[\Override]
    public static function get_settings(): array {
        return [
            'seb_enabled',
            'seb_templateid',
            'seb_requiresafeexambrowser',
            'seb_showsebtaskbar',
            'seb_showwificontrol',
            'seb_showreloadbutton',
            'seb_showtime',
            'seb_showkeyboardlayout',
            'seb_allowuserquitseb',
            'seb_quitpassword',
            'seb_linkquitseb',
            'seb_userconfirmquit',
            'seb_enableaudiocontrol',
            'seb_muteonstartup',
            'seb_allowcapturecamera',
            'seb_allowcapturemicrophone',
            'seb_allowspellchecking',
            'seb_allowreloadinexam',
            'seb_activateurlfiltering',
            'seb_filterembeddedcontent',
            'seb_expressionsallowed',
            'seb_regexallowed',
            'seb_expressionsblocked',
            'seb_regexblocked',
            'seb_allowedbrowserexamkeys',
            'seb_showsebdownloadlink',
        ];
    }

    #[\Override]
    public static function get_required_settings(): array {
        return ['seb_enabled'];
    }

    #[\Override]
    public static function get_settings_sql(string $overridetablename): array {
        $selects = [
            'seb.enabled seb_enabled',
            'seb.templateid seb_templateid',
            'seb.requiresafeexambrowser seb_requiresafeexambrowser',
            'seb.showsebtaskbar seb_showsebtaskbar',
            'seb.showwificontrol seb_showwificontrol',
            'seb.showreloadbutton seb_showreloadbutton',
            'seb.showtime seb_showtime',
            'seb.showkeyboardlayout seb_showkeyboardlayout',
            'seb.allowuserquitseb seb_allowuserquitseb',
            'seb.quitpassword seb_quitpassword',
            'seb.linkquitseb seb_linkquitseb',
            'seb.userconfirmquit seb_userconfirmquit',
            'seb.enableaudiocontrol seb_enableaudiocontrol',
            'seb.muteonstartup seb_muteonstartup',
            'seb.allowcapturecamera seb_allowcapturecamera',
            'seb.allowcapturemicrophone seb_allowcapturemicrophone',
            'seb.allowspellchecking seb_allowspellchecking',
            'seb.allowreloadinexam seb_allowreloadinexam',
            'seb.activateurlfiltering seb_activateurlfiltering',
            'seb.filterembeddedcontent seb_filterembeddedcontent',
            'seb.expressionsallowed seb_expressionsallowed',
            'seb.regexallowed seb_regexallowed',
            'seb.expressionsblocked seb_expressionsblocked',
            'seb.regexblocked seb_regexblocked',
            'seb.allowedbrowserexamkeys seb_allowedbrowserexamkeys',
            'seb.showsebdownloadlink seb_showsebdownloadlink',
        ];
        return [
            $selects,
            ["LEFT JOIN {quizaccess_seb_override} seb ON seb.overrideid = {$overridetablename}.id"],
            [],
        ];
    }

    #[\Override]
    public static function add_table_fields(object $override, array $fields, array $values, context $context): array {
        if (!empty($override->seb_enabled)) {
            $fields[] = get_string('seb_requiresafeexambrowser', 'quizaccess_seb');
            $values[] = settings_provider::get_requiresafeexambrowser_options($context)[$override->seb_requiresafeexambrowser];
        }
        return [$fields, $values];
    }

    #[\Override]
    public static function clean_form_data(array $formdata): array {
        if (isset($formdata['seb_enabled']) && $formdata['seb_enabled'] === '0') {
            $formdata['seb_enabled'] = null;
        }
        return $formdata;
    }
}
