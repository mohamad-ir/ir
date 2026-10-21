<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Gallery;
use App\Models\Lesson;
use App\Models\MembershipPlan;
use App\Models\Webinar;
use App\Models\Category;
use App\Models\ClinicalCase;
use App\Models\ExamTopic;
use App\Models\Media;
use App\Models\Modality;
use App\Models\ProfileEntry;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Models\Video;
use App\Services\Demo\DemoContent;
use App\Services\Media\MediaService;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Optional sample content so a fresh installation looks complete.
 * Everything created here is recorded and can be removed in one click
 * from System › Sample content.
 */
class DemoContentSeeder extends Seeder
{
    protected array $media = [];

    protected array $registry = [];

    public function run(): void
    {
        $author = User::role('super_admin')->first();
        $assets = database_path('seeders/assets');

        foreach (glob($assets.'/*.{jpg,mp4}', GLOB_BRACE) as $file) {
            $name = basename($file);
            $existing = Media::where('original_name', $name)->first();
            $this->media[$name] = $existing ?? $this->track(app(MediaService::class)->store($file, [
                'title' => str($name)->beforeLast('.')->replace(['oct-', 'fundus-', '-'], ['OCT ', 'Fundus ', ' '])->title()->toString(),
                'alt' => $this->altFor($name),
                'credit' => 'Synthetic illustration',
                'is_deidentified' => true,
            ]));
        }

        if (! Settings::get('doctor.photo_id') && isset($this->media['portrait-placeholder.jpg'])) {
            Settings::set('doctor.photo_id', $this->media['portrait-placeholder.jpg']->id);
        }

        $cases = require __DIR__.'/data/cases.php';
        foreach ($cases as $i => $c) {
            if (ClinicalCase::where('slug', $c['slug'])->exists()) {
                continue;
            }
            $case = ClinicalCase::withoutRevisions(fn () => ClinicalCase::create([
                ...collect($c)->only(['title', 'slug', 'summary', 'patient_age', 'patient_sex', 'eye', 'presenting_complaint', 'history', 'findings', 'quiz_prompt', 'quiz_options', 'diagnosis', 'diagnosis_explanation', 'differentials', 'discussion', 'management', 'teaching_points', 'references', 'difficulty'])->all(),
                'images' => collect($c['images'])->map(fn ($img) => ['media_id' => $this->media[$img['file']]->id, 'label' => $img['label'], 'caption' => $img['caption']])->all(),
                'comparison' => isset($c['comparison']) ? [
                    'before_media_id' => $this->media[$c['comparison']['before']]->id,
                    'after_media_id' => $this->media[$c['comparison']['after']]->id,
                    'before_label' => $c['comparison']['before_label'], 'after_label' => $c['comparison']['after_label'], 'caption' => $c['comparison']['caption'],
                ] : null,
                'featured_media_id' => $this->media[$c['images'][0]['file']]->id,
                'is_case_of_week' => ! empty($c['cotw']) || $i < 4,
                'case_of_week_date' => ! empty($c['cotw']) ? now()->startOfWeek() : ($i < 4 ? now()->startOfWeek()->subWeeks($i) : null),
                'is_featured' => $i < 3,
                'status' => ContentStatus::Published,
                'published_at' => now()->subDays($i * 6 + 1),
                'author_id' => $author?->id,
            ]));
            $case->topics()->sync($this->topicIds($c['topics']));
            $case->modalities()->sync(Modality::whereIn('slug', $c['modalities'])->pluck('id'));
            $case->forceFill(['views_count' => random_int(40, 900)])->saveQuietly();
            $case->touch();
            $this->track($case);
        }

        foreach (require __DIR__.'/data/articles.php' as $i => $a) {
            if (Article::where('slug', $a['slug'])->exists()) {
                continue;
            }
            $article = Article::withoutRevisions(fn () => Article::create([
                'title' => $a['title'], 'slug' => $a['slug'], 'excerpt' => $a['excerpt'],
                'body' => $this->expandShortcodes($a['body']), 'references' => $a['references'],
                'category_id' => Category::where('type', 'article')->where('slug', $a['category'])->value('id'),
                'featured_media_id' => $this->media[['oct-normal.jpg', 'oct-drusen.jpg', 'fundus-vasculitis.jpg', 'oct-namd.jpg', 'oct-dme.jpg'][$i % 5]]->id,
                'is_featured' => $a['featured'], 'status' => ContentStatus::Published,
                'published_at' => now()->subDays($i * 9 + 2), 'author_id' => $author?->id,
            ]));
            $article->topics()->sync($this->topicIds($a['topics']));
            $article->touch();
            $this->track($article);
        }

        foreach (require __DIR__.'/data/videos.php' as $i => $v) {
            if (Video::where('slug', $v['slug'])->exists()) {
                continue;
            }
            $video = Video::withoutRevisions(fn () => Video::create([
                'title' => $v['title'], 'slug' => $v['slug'], 'summary' => $v['summary'], 'description' => $v['description'],
                'provider' => 'file', 'video_media_id' => $this->media['demo-oct-fluid.mp4']->id, 'duration_seconds' => 20,
                'thumbnail_media_id' => $this->media[$v['thumb']]->id, 'chapters' => $v['chapters'], 'transcript' => $v['transcript'],
                'category_id' => Category::where('type', 'video')->where('slug', $v['category'])->value('id'),
                'level' => $v['level'], 'is_featured' => $v['featured'], 'access_level' => $v['access'] ?? 'public',
                'status' => ContentStatus::Published, 'published_at' => now()->subDays($i * 7 + 3), 'author_id' => $author?->id,
            ]));
            $video->topics()->sync($this->topicIds($v['topics']));
            $video->touch();
            $this->track($video);
        }

        $exam = require __DIR__.'/data/exam.php';
        foreach ($exam['topics'] as $i => $t) {
            $topic = ExamTopic::where('slug', $t['slug'])->first() ?? $this->track(ExamTopic::withoutRevisions(fn () => ExamTopic::create([
                ...$t, 'body' => $this->expandShortcodes($t['body']), 'sort_order' => $i, 'status' => ContentStatus::Published, 'published_at' => now()->subDays(30),
            ])));
        }
        foreach ($exam['questions'] as $i => $q) {
            if (Question::where('title', $q['title'])->exists()) {
                continue;
            }
            $this->track(Question::withoutRevisions(fn () => Question::create([
                'exam_topic_id' => ExamTopic::where('slug', $q['topic'])->value('id'),
                'type' => $q['type'], 'exam' => $q['exam'], 'title' => $q['title'], 'stem' => $q['stem'],
                'media_id' => isset($q['image']) ? $this->media[$q['image']]->id : null,
                'options' => $q['options'] ?? null, 'explanation' => $q['explanation'] ?? null, 'model_answer' => $q['model_answer'] ?? null,
                'key_points' => $q['key_points'] ?? null, 'references' => $q['references'] ?? null, 'difficulty' => $q['difficulty'],
                'status' => ContentStatus::Published, 'published_at' => now()->subDays(20 - $i), 'author_id' => $author?->id,
            ])));
        }

        if (ProfileEntry::count() === 0) {
            foreach (require __DIR__.'/data/profile.php' as $i => $entry) {
                $this->track(ProfileEntry::create([...$entry, 'sort_order' => $i]));
            }
        }

        $this->learning($author);
        $this->galleries($author);

        DemoContent::remember($this->registry);
    }

    protected function galleries(?User $author): void
    {
        foreach (require __DIR__.'/data/galleries.php' as $i => $g) {
            if (Gallery::withTrashed()->where('slug', $g['slug'])->exists()) {
                continue;
            }
            $gallery = Gallery::withoutRevisions(fn () => Gallery::create([
                'title' => $g['title'], 'slug' => $g['slug'], 'summary' => $g['summary'], 'layout' => $g['layout'], 'dark_viewer' => $g['dark'],
                'is_featured' => $g['featured'], 'sort_order' => $i, 'status' => ContentStatus::Published, 'published_at' => now()->subDays(3 + $i), 'author_id' => $author?->id,
            ]));
            foreach ($g['images'] as $n => [$file, $caption]) {
                if (isset($this->media[$file])) {
                    $gallery->items()->create(['media_id' => $this->media[$file]->id, 'caption' => $caption, 'sort_order' => $n + 1]);
                }
            }
            $gallery->topics()->sync($this->topicIds($g['topics']));
            $gallery->touch();
            $this->track($gallery);
        }
    }

    protected function learning(?User $author): void
    {
        $data = require __DIR__.'/data/learning.php';
        $currency = Settings::get('payments.currency', 'EUR');

        foreach ($data['plans'] as $p) {
            if (! MembershipPlan::where('slug', $p['slug'])->exists()) {
                $this->track(MembershipPlan::create([...$p, 'currency' => $currency, 'is_active' => true, 'features' => collect($p['features'])->map(fn ($f) => ['text' => $f])->all()]));
            }
        }

        foreach ($data['courses'] as $i => $c) {
            if (Course::withTrashed()->where('slug', $c['slug'])->exists()) {
                continue;
            }
            $course = Course::withoutRevisions(fn () => Course::create([
                ...collect($c)->only(['title', 'slug', 'subtitle', 'summary', 'description', 'level', 'price', 'prices', 'included_in_membership', 'is_featured', 'faqs'])->all(),
                'outcomes' => collect($c['outcomes'])->map(fn ($o) => ['text' => $o])->all(),
                'currency' => $currency,
                'featured_media_id' => $this->media[$c['image']]->id ?? null,
                'status' => ContentStatus::Published, 'published_at' => now()->subDays(4 + $i * 5), 'author_id' => $author?->id,
            ]));
            $course->topics()->sync($this->topicIds($c['topics']));
            $position = 0;
            foreach ($c['sections'] as $s => $section) {
                $sectionModel = CourseSection::create(['course_id' => $course->id, 'title' => $section['title'], 'sort_order' => $s]);
                foreach ($section['lessons'] as $l => $lesson) {
                    Lesson::create([
                        'course_id' => $course->id, 'course_section_id' => $sectionModel->id, 'title' => $lesson['title'],
                        'summary' => $lesson['summary'], 'duration_minutes' => $lesson['minutes'], 'is_preview' => $lesson['preview'] ?? false, 'quiz' => $lesson['quiz'] ?? null,
                        'body' => '<p>'.e($lesson['summary']).'</p><p><em>Sample lesson — replace the video and notes in the dashboard under Learning › Courses.</em></p>',
                        'video_provider' => 'file', 'video_media_id' => $this->media['demo-oct-fluid.mp4']->id ?? null, 'sort_order' => ++$position,
                    ]);
                }
            }
            $course->touch();
            $this->track($course);
        }

        foreach ($data['webinars'] as $w) {
            if (Webinar::withTrashed()->where('slug', $w['slug'])->exists()) {
                continue;
            }
            $webinar = Webinar::withoutRevisions(fn () => Webinar::create([
                ...collect($w)->only(['title', 'slug', 'summary', 'description', 'price', 'prices', 'free_for_members', 'capacity'])->all(),
                'currency' => $currency,
                'starts_at' => now()->addDays($w['days'])->setTime($w['hour'], 0),
                'duration_minutes' => $w['minutes'],
                'platform' => 'zoom',
                'join_url' => 'https://zoom.us/j/0000000000',
                'join_details' => 'Sample meeting — replace with your Zoom link and passcode.',
                'featured_media_id' => $this->media[$w['image']]->id ?? null,
                'status' => ContentStatus::Published, 'published_at' => now()->subDay(), 'author_id' => $author?->id,
            ]));
            $webinar->topics()->sync($this->topicIds($w['topics']));
            $this->track($webinar);
        }
    }

    protected function track($model)
    {
        $this->registry[] = $model->getMorphClass().':'.$model->getKey();

        return $model;
    }

    protected function topicIds(array $keys): array
    {
        return collect($keys)->map(fn ($k) => Topic::where('slug', $k)->orWhereRaw('LOWER(abbreviation) = ?', [strtolower($k)])->value('id'))->filter()->unique()->values()->all();
    }

    /** Converts [[callout:tone:text]] into the rich-editor custom block markup. */
    public function expandShortcodes(string $html): string
    {
        return preg_replace_callback('/\[\[callout:(\w+):(.+?)\]\]/s', fn ($m) => '<div data-type="customBlock" data-id="callout" data-config="'.e(json_encode(['tone' => $m[1], 'title' => '', 'text' => $m[2]])).'"></div>', $html);
    }

    protected function altFor(string $name): string
    {
        return match (true) {
            str_starts_with($name, 'oct-') => 'Synthetic OCT B-scan illustrating '.str($name)->after('oct-')->beforeLast('.')->replace('-', ' '),
            str_starts_with($name, 'fundus-') => 'Synthetic colour fundus illustration: '.str($name)->after('fundus-')->beforeLast('.')->replace('-', ' '),
            str_ends_with($name, '.mp4') => 'Demonstration video',
            default => 'Portrait placeholder',
        };
    }
}
