<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/iednews/lib.php');

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
        $summarychars = !empty($this->config->summarychars)
            ? (int) $this->config->summarychars
            : (int) get_config('block_iednews', 'summarychars');
        $summarychars = max(60, min(1000, $summarychars ?: 220));
        $maxheight = !empty($this->config->maxheight)
            ? (int) $this->config->maxheight
            : (int) get_config('block_iednews', 'maxheight');
        $maxheight = max(220, min(900, $maxheight ?: 420));
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
            $targetcohorts = $this->get_target_cohorts(array_keys($newsitems));
            $usercohorts = $this->get_user_cohorts($USER->id);
            foreach ($newsitems as $news) {
                $item = new stdClass();
                $item->title = format_string($news->title);
                $content = block_iednews_format_news_content($news, $this->context);
                $image = $this->get_teaser_image($news->id);
                $item->hasimage = !empty($image);
                $item->imageurl = $image->src ?? '';
                $item->imagealt = $image->alt ?? '';
                $item->summary = $this->get_summary($content, $summarychars);
                $item->url = (new moodle_url('/blocks/iednews/view.php', ['id' => $news->id]))->out(false);
                $item->showdate = $showdate;
                $item->date = userdate($news->publishfrom ?: $news->timecreated, get_string('strftimedatefullshort'));
                $item->active = ($index === 0);
                $item->index = $index;
                $item->visibilityreason = $this->get_visibility_reason(
                    $targetcohorts[$news->id] ?? [],
                    $usercohorts
                );
                $items[] = $item;
                $index++;
            }
            $this->content->text = $OUTPUT->render_from_template('block_iednews/news', [
                'carouselid' => 'block-iednews-' . $this->instance->id,
                'interval' => $slidespeed,
                'maxheight' => $maxheight,
                'items' => $items,
                'totalitems' => count($items),
                'showcontrols' => count($items) > 1,
                'pagingbar' => [
                    'previous' => true,
                    'next' => true,
                    'hidecontrolonsinglepage' => false,
                    'ignorecontrolwhileloading' => true,
                    'arialabels' => [
                        'paginationnav' => get_string('newspagination', 'block_iednews'),
                    ],
                ],
                'newscount' => get_string(
                    count($items) === 1 ? 'newscountsingle' : 'newscount',
                    'block_iednews',
                    count($items)
                ),
                'previous' => get_string('previous'),
                'next' => get_string('next'),
                'pause' => get_string('pause', 'block_iednews'),
                'resume' => get_string('resume', 'block_iednews'),
                'readmore' => get_string('readmore', 'block_iednews'),
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

    private function get_target_cohorts(array $newsids): array {
        global $DB;

        if (!$newsids) {
            return [];
        }

        list($newssql, $params) = $DB->get_in_or_equal($newsids, SQL_PARAMS_NAMED, 'newsid');
        $sql = "SELECT bnc.id, bnc.newsid, bnc.cohortid, c.name
                  FROM {block_iednews_cohort} bnc
                  JOIN {cohort} c ON c.id = bnc.cohortid
                 WHERE bnc.newsid $newssql
              ORDER BY c.name ASC";
        $records = $DB->get_records_sql($sql, $params);
        $cohorts = [];

        foreach ($records as $record) {
            $cohorts[$record->newsid][$record->cohortid] = format_string($record->name);
        }

        return $cohorts;
    }

    private function get_user_cohorts(int $userid): array {
        global $DB;

        if (is_siteadmin()) {
            return [];
        }

        return $DB->get_records_menu(
            'cohort_members',
            ['userid' => $userid],
            '',
            'cohortid, cohortid AS cohortidvalue'
        );
    }

    private function get_visibility_reason(array $targetcohorts, array $usercohorts): string {
        if (is_siteadmin()) {
            return get_string('visibilityreason_admin', 'block_iednews');
        }

        if (!$targetcohorts) {
            return get_string('visibilityreason_all', 'block_iednews');
        }

        $matchedcohorts = [];
        foreach ($targetcohorts as $cohortid => $cohortname) {
            if (isset($usercohorts[$cohortid])) {
                $matchedcohorts[] = $cohortname;
            }
        }

        if ($matchedcohorts) {
            return get_string('visibilityreason_cohorts', 'block_iednews', implode(', ', $matchedcohorts));
        }

        return get_string('visibilityreason_admin', 'block_iednews');
    }

    private function get_teaser_image(int $newsid): ?stdClass {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            context_system::instance()->id,
            'block_iednews',
            'image',
            $newsid,
            'itemid, filepath, filename',
            false
        );

        if (!$files) {
            return null;
        }

        $file = reset($files);
        $image = new stdClass();
        $image->src = moodle_url::make_pluginfile_url(
            context_system::instance()->id,
            'block_iednews',
            'image',
            $newsid,
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
        $image->alt = '';

        return $image;
    }

    private function get_summary(string $html, int $maxchars): string {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
        if (core_text::strlen($text) <= $maxchars) {
            return s($text);
        }

        return s(core_text::substr($text, 0, $maxchars - 1) . '…');
    }
}
