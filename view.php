<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/iednews/lib.php');

$id = required_param('id', PARAM_INT);
$context = context_system::instance();

require_login();

$news = $DB->get_record('block_iednews', ['id' => $id], '*', IGNORE_MISSING);
if (!$news || !block_iednews_user_can_view_news($news)) {
    throw new moodle_exception('newsnotavailable', 'block_iednews');
}

$url = new moodle_url('/blocks/iednews/view.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($news->title));
$PAGE->set_heading(get_string('newsarticle', 'block_iednews'));
$PAGE->requires->css('/blocks/iednews/styles.css');

$content = block_iednews_format_news_content($news, $context);

echo $OUTPUT->header();
echo html_writer::start_tag('article', ['class' => 'block-iednews-full']);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/my/'),
        get_string('backtodashboard', 'block_iednews'),
        ['class' => 'btn btn-secondary btn-sm']
    ),
    'block-iednews-full-header'
);
echo $OUTPUT->heading(format_string($news->title), 2);
echo html_writer::div(
    userdate($news->publishfrom ?: $news->timecreated, get_string('strftimedatefullshort')),
    'text-muted small mb-3'
);
echo html_writer::div($content, 'block-iednews-full-content');
echo html_writer::end_tag('article');
echo $OUTPUT->footer();
