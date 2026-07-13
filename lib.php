<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Serves images embedded in news content.
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

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'content') {
        return false;
    }

    $itemid = (int) array_shift($args);
    $news = $DB->get_record('block_iednews', ['id' => $itemid]);
    $now = time();
    $isvisible = $news && $news->published
        && (!$news->publishfrom || $news->publishfrom <= $now)
        && (!$news->publishto || $news->publishto >= $now);
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
