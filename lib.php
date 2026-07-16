<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Returns whether a news item is currently visible to a user.
 *
 * Visibility requires the item to be published, inside its publication period,
 * and either not restricted to cohorts or restricted to one of the user's cohorts.
 *
 * @param stdClass $news News record.
 * @param int|null $userid User id. Defaults to the current user.
 * @return bool
 */
function block_iednews_user_can_view_news(stdClass $news, ?int $userid = null): bool {
    global $DB, $USER;

    $now = time();
    if (empty($news->published)
            || (!empty($news->publishfrom) && $news->publishfrom > $now)
            || (!empty($news->publishto) && $news->publishto < $now)) {
        return false;
    }

    if (is_siteadmin($userid)) {
        return true;
    }

    $userid = $userid ?? $USER->id;
    if (empty($userid) || !isloggedin() || isguestuser()) {
        return false;
    }

    if (!$DB->record_exists('block_iednews_cohort', ['newsid' => $news->id])) {
        return true;
    }

    $sql = "SELECT 1
              FROM {block_iednews_cohort} bnc
              JOIN {cohort_members} cm ON cm.cohortid = bnc.cohortid
             WHERE bnc.newsid = :newsid
               AND cm.userid = :userid";

    return $DB->record_exists_sql($sql, [
        'newsid' => $news->id,
        'userid' => $userid,
    ]);
}

/**
 * Rewrites embedded file URLs and formats news content for display.
 *
 * @param stdClass $news News record.
 * @param context $context Display context.
 * @return string HTML content.
 */
function block_iednews_format_news_content(stdClass $news, context $context): string {
    $content = file_rewrite_pluginfile_urls(
        $news->content,
        'pluginfile.php',
        context_system::instance()->id,
        'block_iednews',
        'content',
        $news->id
    );

    return format_text($content, $news->contentformat, ['context' => $context]);
}

/**
 * Serves images embedded in news content and news teaser images.
 *
 * @param stdClass $course Course object.
 * @param stdClass|null $cm Course module object.
 * @param context $context File context.
 * @param string $filearea File area.
 * @param array $args File path arguments.
 * @param bool $forcedownload Whether download should be forced.
 * @param array $options Additional options.
 * @return bool
 */
function block_iednews_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel !== CONTEXT_SYSTEM || !in_array($filearea, ['content', 'image'])) {
        return false;
    }

    $itemid = (int) array_shift($args);
    $news = $DB->get_record('block_iednews', ['id' => $itemid]);
    if (!$news) {
        return false;
    }

    $isvisible = block_iednews_user_can_view_news($news);
    if (!$isvisible && !is_siteadmin()) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');

    $file = get_file_storage()->get_file(
        $context->id,
        'block_iednews',
        $filearea,
        $itemid,
        $filepath,
        $filename
    );
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
