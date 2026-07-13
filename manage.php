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
$targetcohorts = [];
if ($newsitems) {
    list($newssql, $newsparams) = $DB->get_in_or_equal(array_keys($newsitems), SQL_PARAMS_NAMED, 'newsid');
    $sql = "SELECT bnc.id, bnc.newsid, c.name
              FROM {block_iednews_cohort} bnc
              JOIN {cohort} c ON c.id = bnc.cohortid
             WHERE bnc.newsid $newssql
          ORDER BY c.name ASC";
    $records = $DB->get_records_sql($sql, $newsparams);
    foreach ($records as $record) {
        $targetcohorts[$record->newsid][] = format_string($record->name);
    }
}

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
        get_string('visibility', 'block_iednews'),
        get_string('publishfrom', 'block_iednews'),
        get_string('publishto', 'block_iednews'),
        get_string('actions'),
    ];
    foreach ($newsitems as $news) {
        $editurl = new moodle_url('/blocks/iednews/edit.php', ['id' => $news->id]);
        $deleteurl = new moodle_url('/blocks/iednews/delete.php', ['id' => $news->id, 'sesskey' => sesskey()]);
        $usersurl = new moodle_url('/blocks/iednews/users.php', ['id' => $news->id]);
        $visibility = empty($targetcohorts[$news->id])
            ? get_string('visibletoall', 'block_iednews')
            : implode(', ', $targetcohorts[$news->id]);
        $visibility .= html_writer::empty_tag('br');
        $visibility .= html_writer::link($usersurl, get_string('viewtargetusers', 'block_iednews'));
        $actions = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
        $actions .= $OUTPUT->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
        $table->data[] = [
            format_string($news->title),
            $news->published ? get_string('published', 'block_iednews') : get_string('draft', 'block_iednews'),
            $visibility,
            $news->publishfrom ? userdate($news->publishfrom) : get_string('immediately', 'block_iednews'),
            $news->publishto ? userdate($news->publishto) : get_string('never'),
            $actions,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
