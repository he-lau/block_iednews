<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class block_iednews_edit_form extends block_edit_form {
    protected function specific_definition($mform): void {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('customtitle', 'block_iednews'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('select', 'config_maxitems', get_string('maxitems', 'block_iednews'),
            array_combine(range(1, 20), range(1, 20)));
        $mform->setDefault('config_maxitems', (int) get_config('block_iednews', 'maxitems') ?: 5);

        $mform->addElement('advcheckbox', 'config_showdate', get_string('showdate', 'block_iednews'));
        $mform->setDefault('config_showdate', (bool) get_config('block_iednews', 'showdate'));

        $mform->addElement('select', 'config_slidespeed', get_string('slidespeed', 'block_iednews'), [
            3000 => get_string('seconds', 'block_iednews', 3),
            5000 => get_string('seconds', 'block_iednews', 5),
            8000 => get_string('seconds', 'block_iednews', 8),
            10000 => get_string('seconds', 'block_iednews', 10),
        ]);
        $mform->setDefault('config_slidespeed', (int) get_config('block_iednews', 'slidespeed') ?: 5000);
    }
}
