<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'block_iednews/maxitems',
        get_string('maxitems', 'block_iednews'),
        get_string('maxitems_desc', 'block_iednews'),
        5,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_iednews/showdate',
        get_string('showdate', 'block_iednews'),
        get_string('showdate_desc', 'block_iednews'),
        1
    ));
    $settings->add(new admin_setting_configselect(
        'block_iednews/slidespeed',
        get_string('slidespeed', 'block_iednews'),
        get_string('slidespeed_desc', 'block_iednews'),
        5000,
        [
            3000 => get_string('seconds', 'block_iednews', 3),
            5000 => get_string('seconds', 'block_iednews', 5),
            8000 => get_string('seconds', 'block_iednews', 8),
            10000 => get_string('seconds', 'block_iednews', 10),
        ]
    ));
    $settings->add(new admin_setting_description(
        'block_iednews/managelink',
        get_string('managenews', 'block_iednews'),
        html_writer::link(new moodle_url('/blocks/iednews/manage.php'), get_string('openmanagement', 'block_iednews'))
    ));
}
