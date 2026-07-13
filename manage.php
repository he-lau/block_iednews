<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

require_login();
require_admin();
$context = context_system::instance();

$PAGE->set_url(new moodle_url('/blocks/iednews/manage.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('managenews', 'block_iednews'));
$PAGE->set_heading(get_string('managenews', 'block_iednews'));

$newsitems = $DB->get_records('block_iednews', null, 'publishfrom DESC, timecreated DESC');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managenews', 'block_iednews'));
echo $OUTPUT->single_button(new moodle_url('/blocks/iednews/edit.php'), get_string('addnews', 'block_iednews'));

if (!$newsitems) {
    echo $OUTPUT->notification(get_string('nonews', 'block_iednews'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('newstitle', 'block_iednews'),
        get_string('status'),
        get_string('publishfrom', 'block_iednews'),
        get_string('publishto', 'block_iednews'),
        get_string('actions'),
    ];
    foreach ($newsitems as $news) {
        $editurl = new moodle_url('/blocks/iednews/edit.php', ['id' => $news->id]);
        $deleteurl = new moodle_url('/blocks/iednews/delete.php', ['id' => $news->id, 'sesskey' => sesskey()]);
        $actions = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
        $actions .= $OUTPUT->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
        $table->data[] = [
            format_string($news->title),
            $news->published ? get_string('published', 'block_iednews') : get_string('draft', 'block_iednews'),
            $news->publishfrom ? userdate($news->publishfrom) : get_string('immediately', 'block_iednews'),
            $news->publishto ? userdate($news->publishto) : get_string('never'),
            $actions,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
