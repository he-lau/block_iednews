<?php
// This file is part of Moodle - http://moodle.org/

namespace block_iednews\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_iednews', [
            'title' => 'privacy:metadata:block_iednews:title',
            'content' => 'privacy:metadata:block_iednews:content',
            'usermodified' => 'privacy:metadata:block_iednews:usermodified',
            'timecreated' => 'privacy:metadata:block_iednews:timecreated',
            'timemodified' => 'privacy:metadata:block_iednews:timemodified',
        ], 'privacy:metadata:block_iednews');
        $collection->add_database_table('block_iednews_cohort', [
            'newsid' => 'privacy:metadata:block_iednews_cohort:newsid',
            'cohortid' => 'privacy:metadata:block_iednews_cohort:cohortid',
        ], 'privacy:metadata:block_iednews_cohort');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND EXISTS (
                       SELECT 1
                         FROM {block_iednews} news
                        WHERE news.usermodified = :userid
                   )";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }

        $records = $DB->get_records('block_iednews', ['usermodified' => $contextlist->get_user()->id]);
        $newsitems = [];
        foreach ($records as $record) {
            $newsitems[] = (object) [
                'title' => format_string($record->title),
                'content' => format_text($record->content, $record->contentformat, ['context' => $context]),
                'published' => transform::yesno($record->published),
                'publishfrom' => transform::datetime($record->publishfrom),
                'publishto' => transform::datetime($record->publishto),
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified),
            ];
            writer::with_context($context)->export_area_files(
                [],
                'block_iednews',
                'content',
                $record->id
            );
        }

        writer::with_context($context)->export_data([], (object) ['news' => $newsitems]);
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        get_file_storage()->delete_area_files($context->id, 'block_iednews', 'content');
        $DB->delete_records('block_iednews_cohort');
        $DB->delete_records('block_iednews');
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }

        $records = $DB->get_records('block_iednews', ['usermodified' => $contextlist->get_user()->id], '', 'id');
        foreach ($records as $record) {
            get_file_storage()->delete_area_files($context->id, 'block_iednews', 'content', $record->id);
            $DB->delete_records('block_iednews_cohort', ['newsid' => $record->id]);
        }
        $DB->delete_records('block_iednews', ['usermodified' => $contextlist->get_user()->id]);
    }
}
