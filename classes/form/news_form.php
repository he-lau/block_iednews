<?php
// This file is part of Moodle - http://moodle.org/

namespace block_iednews\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class news_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('newstitle', 'block_iednews'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('editor', 'content_editor', get_string('newscontent', 'block_iednews'), null,
            $editoroptions);
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'published', get_string('published', 'block_iednews'));
        $mform->setDefault('published', 1);

        $mform->addElement('date_time_selector', 'publishfrom', get_string('publishfrom', 'block_iednews'),
            ['optional' => true]);
        $mform->addElement('date_time_selector', 'publishto', get_string('publishto', 'block_iednews'),
            ['optional' => true]);

        $cohorts = $this->_customdata['cohorts'] ?? [];
        $mform->addElement('select', 'cohortids', get_string('targetcohorts', 'block_iednews'), $cohorts,
            ['multiple' => 'multiple', 'size' => min(10, max(3, count($cohorts)))]);
        $mform->setType('cohortids', PARAM_INT);
        $mform->addHelpButton('cohortids', 'targetcohorts', 'block_iednews');

        $this->add_action_buttons(true);
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!empty($data['publishfrom']) && !empty($data['publishto'])
                && $data['publishto'] < $data['publishfrom']) {
            $errors['publishto'] = get_string('invaliddates', 'block_iednews');
        }
        return $errors;
    }
}
