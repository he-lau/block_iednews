<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class block_iednews extends block_base {
    public function init(): void {
        $this->title = get_string('pluginname', 'block_iednews');
    }

    public function specialization(): void {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    public function get_content() {
        global $DB, $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $limit = !empty($this->config->maxitems)
            ? (int) $this->config->maxitems
            : (int) get_config('block_iednews', 'maxitems');
        $limit = max(1, min(20, $limit ?: 5));
        $showdate = isset($this->config->showdate)
            ? !empty($this->config->showdate)
            : (bool) get_config('block_iednews', 'showdate');
        $slidespeed = !empty($this->config->slidespeed)
            ? (int) $this->config->slidespeed
            : (int) get_config('block_iednews', 'slidespeed');
        $slidespeed = max(2000, min(30000, $slidespeed ?: 5000));
        $now = time();
        $select = 'published = :published AND (publishfrom = 0 OR publishfrom <= :now1)
                   AND (publishto = 0 OR publishto >= :now2)';
        $params = ['published' => 1, 'now1' => $now, 'now2' => $now];

        if (!is_siteadmin()) {
            $select .= ' AND (
                NOT EXISTS (
                    SELECT 1
                      FROM {block_iednews_cohort} bnc_all
                     WHERE bnc_all.newsid = {block_iednews}.id
                )
                OR EXISTS (
                    SELECT 1
                      FROM {block_iednews_cohort} bnc_member
                      JOIN {cohort_members} cm ON cm.cohortid = bnc_member.cohortid
                     WHERE bnc_member.newsid = {block_iednews}.id
                       AND cm.userid = :currentuserid
                )
            )';
            $params['currentuserid'] = $USER->id;
        }

        $newsitems = $DB->get_records_select(
            'block_iednews',
            $select,
            $params,
            'publishfrom DESC, timecreated DESC',
            '*',
            0,
            $limit
        );

        if (!$newsitems) {
            $this->content->text = get_string('nonews', 'block_iednews');
        } else {
            $items = [];
            $index = 0;
            foreach ($newsitems as $news) {
                $item = new stdClass();
                $item->title = format_string($news->title);
                $content = file_rewrite_pluginfile_urls(
                    $news->content,
                    'pluginfile.php',
                    context_system::instance()->id,
                    'block_iednews',
                    'content',
                    $news->id
                );
                $item->content = format_text($content, $news->contentformat, ['context' => $this->context]);
                $item->showdate = $showdate;
                $item->date = userdate($news->publishfrom ?: $news->timecreated, get_string('strftimedatefullshort'));
                $item->active = ($index === 0);
                $item->index = $index;
                $items[] = $item;
                $index++;
            }
            $this->content->text = $OUTPUT->render_from_template('block_iednews/news', [
                'carouselid' => 'block-iednews-' . $this->instance->id,
                'interval' => $slidespeed,
                'items' => $items,
                'showcontrols' => count($items) > 1,
                'newscount' => get_string(
                    count($items) === 1 ? 'newscountsingle' : 'newscount',
                    'block_iednews',
                    count($items)
                ),
                'previous' => get_string('previous'),
                'next' => get_string('next'),
                'pause' => get_string('pause', 'block_iednews'),
                'resume' => get_string('resume', 'block_iednews'),
            ]);
        }

        if (is_siteadmin()) {
            $url = new moodle_url('/blocks/iednews/manage.php');
            $this->content->footer = html_writer::link($url, get_string('managenews', 'block_iednews'));
        }

        return $this->content;
    }

    public function has_config(): bool {
        return true;
    }

    public function instance_allow_multiple(): bool {
        return true;
    }

    public function user_can_edit(): bool {
        return is_siteadmin();
    }

    public function user_can_addto($page): bool {
        return is_siteadmin();
    }

    public function applicable_formats(): array {
        return ['all' => true];
    }
}
