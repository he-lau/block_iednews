<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$context = context_system::instance();

require_login();
require_admin();
require_sesskey();

$news = $DB->get_record('block_iednews', ['id' => $id], '*', MUST_EXIST);
$url = new moodle_url('/blocks/iednews/delete.php', ['id' => $id, 'sesskey' => sesskey()]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('deletenews', 'block_iednews'));
$PAGE->set_heading(get_string('managenews', 'block_iednews'));

if ($confirm) {
    get_file_storage()->delete_area_files($context->id, 'block_iednews', 'content', $id);
    $DB->delete_records('block_iednews_cohort', ['newsid' => $id]);
    $DB->delete_records('block_iednews', ['id' => $id]);
    redirect(new moodle_url('/blocks/iednews/manage.php'), get_string('newsdeleted', 'block_iednews'));
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('confirmdelete', 'block_iednews', format_string($news->title)),
    new moodle_url($url, ['confirm' => 1]),
    new moodle_url('/blocks/iednews/manage.php')
);
echo $OUTPUT->footer();
