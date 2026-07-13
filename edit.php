<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');

use block_iednews\form\news_form;

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();

require_login();
require_admin();

$url = new moodle_url('/blocks/iednews/edit.php', $id ? ['id' => $id] : []);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('editnews', 'block_iednews') : get_string('addnews', 'block_iednews'));
$PAGE->set_heading(get_string('managenews', 'block_iednews'));

$editoroptions = [
    'context' => $context,
    'maxfiles' => -1,
    'maxbytes' => get_max_upload_file_size(),
    'subdirs' => 0,
    'accepted_types' => ['image'],
];

if ($id) {
    $news = $DB->get_record('block_iednews', ['id' => $id], '*', MUST_EXIST);
    $news = file_prepare_standard_editor(
        $news,
        'content',
        $editoroptions,
        $context,
        'block_iednews',
        'content',
        $news->id
    );
} else {
    $news = (object) [
        'id' => 0,
        'published' => 1,
        'publishfrom' => time(),
        'publishto' => 0,
        'content_editor' => ['text' => '', 'format' => FORMAT_HTML],
    ];
}

$form = new news_form($url, ['editoroptions' => $editoroptions]);
$form->set_data($news);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/iednews/manage.php'));
} else if ($data = $form->get_data()) {
    $record = new stdClass();
    $record->title = $data->title;
    $record->published = !empty($data->published);
    $record->publishfrom = (int) $data->publishfrom;
    $record->publishto = (int) $data->publishto;
    $record->timemodified = time();
    $record->usermodified = $USER->id;

    if ($id) {
        $record->id = $id;
        $DB->update_record('block_iednews', $record);
    } else {
        $record->timecreated = $record->timemodified;
        $record->content = '';
        $record->contentformat = FORMAT_HTML;
        $record->id = $DB->insert_record('block_iednews', $record);
    }
    $itemid = $record->id;
    $data = file_postupdate_standard_editor(
        $data,
        'content',
        $editoroptions,
        $context,
        'block_iednews',
        'content',
        $itemid
    );
    $DB->update_record('block_iednews', (object) [
        'id' => $itemid,
        'content' => $data->content,
        'contentformat' => $data->contentformat,
    ]);
    redirect(new moodle_url('/blocks/iednews/manage.php'), get_string('changessaved'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editnews', 'block_iednews') : get_string('addnews', 'block_iednews'));
$form->display();
echo $OUTPUT->footer();
