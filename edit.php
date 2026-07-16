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
    'accepted_types' => ['.png', '.jpg', '.jpeg'],
];
$imageoptions = [
    'context' => $context,
    'maxfiles' => 1,
    'maxbytes' => get_max_upload_file_size(),
    'subdirs' => 0,
    'accepted_types' => ['.png', '.jpg', '.jpeg'],
];
$cohorts = $DB->get_records_menu('cohort', null, 'name ASC', 'id, name');

if ($id) {
    $news = $DB->get_record('block_iednews', ['id' => $id], '*', MUST_EXIST);
    $draftitemid = file_get_submitted_draft_itemid('image_filemanager');
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'block_iednews',
        'image',
        $news->id,
        $imageoptions
    );
    $news->image_filemanager = $draftitemid;
    $news = file_prepare_standard_editor(
        $news,
        'content',
        $editoroptions,
        $context,
        'block_iednews',
        'content',
        $news->id
    );
    $news->cohortids = $DB->get_fieldset_select(
        'block_iednews_cohort',
        'cohortid',
        'newsid = ?',
        [$news->id]
    );
} else {
    $news = (object) [
        'id' => 0,
        'published' => 1,
        'publishfrom' => time(),
        'publishto' => 0,
        'cohortids' => [],
        'image_filemanager' => file_get_unused_draft_itemid(),
        'content_editor' => ['text' => '', 'format' => FORMAT_HTML],
    ];
}

$form = new news_form($url, [
    'editoroptions' => $editoroptions,
    'imageoptions' => $imageoptions,
    'cohorts' => $cohorts,
]);
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
        $record->image = 0;
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
        'image' => $itemid,
        'content' => $data->content,
        'contentformat' => $data->contentformat,
    ]);
    file_save_draft_area_files(
        $data->image_filemanager,
        $context->id,
        'block_iednews',
        'image',
        $itemid,
        $imageoptions
    );

    $DB->delete_records('block_iednews_cohort', ['newsid' => $itemid]);
    if (!empty($data->cohortids) && is_array($data->cohortids)) {
        $selectedcohorts = array_unique(array_map('intval', $data->cohortids));
        foreach ($selectedcohorts as $cohortid) {
            if ($cohortid <= 0 || !isset($cohorts[$cohortid])) {
                continue;
            }
            $DB->insert_record('block_iednews_cohort', (object) [
                'newsid' => $itemid,
                'cohortid' => $cohortid,
            ]);
        }
    }

    redirect(new moodle_url('/blocks/iednews/manage.php'), get_string('changessaved'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editnews', 'block_iednews') : get_string('addnews', 'block_iednews'));
$form->display();
echo $OUTPUT->footer();
