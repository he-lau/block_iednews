<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 100;
$context = context_system::instance();

require_login();
require_admin();

$news = $DB->get_record('block_iednews', ['id' => $id], '*', MUST_EXIST);
$url = new moodle_url('/blocks/iednews/users.php', ['id' => $id]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('targetusersfor', 'block_iednews', format_string($news->title)));
$PAGE->set_heading(get_string('managenews', 'block_iednews'));

$cohortids = $DB->get_fieldset_select(
    'block_iednews_cohort',
    'cohortid',
    'newsid = ?',
    [$id]
);

$params = [];
if ($cohortids) {
    list($cohortsql, $params) = $DB->get_in_or_equal($cohortids, SQL_PARAMS_NAMED, 'cohortid');
    $from = "FROM {user} u
             JOIN {cohort_members} cm ON cm.userid = u.id
            WHERE cm.cohortid $cohortsql
              AND u.deleted = 0";
} else {
    $from = "FROM {user} u
            WHERE u.deleted = 0";
}

$countsql = "SELECT COUNT(DISTINCT u.id) $from";
$total = $DB->count_records_sql($countsql, $params);

$sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                        u.middlename, u.alternatename, u.email
                   $from
               ORDER BY u.lastname ASC, u.firstname ASC, u.id ASC";
$users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('targetusersfor', 'block_iednews', format_string($news->title)));

if ($cohortids) {
    echo $OUTPUT->notification(get_string('restrictedtocohorts', 'block_iednews'), 'info');
} else {
    echo $OUTPUT->notification(get_string('visibletoall', 'block_iednews'), 'info');
}

echo html_writer::tag('p', get_string('targetusercount', 'block_iednews', $total));

if ($users) {
    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('email'),
    ];

    foreach ($users as $user) {
        $table->data[] = [
            fullname($user),
            s($user->email),
        ];
    }

    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($total, $page, $perpage, $url);
} else {
    echo $OUTPUT->notification(get_string('notargetusers', 'block_iednews'), 'warning');
}

echo $OUTPUT->single_button(new moodle_url('/blocks/iednews/manage.php'), get_string('back'));
echo $OUTPUT->footer();
