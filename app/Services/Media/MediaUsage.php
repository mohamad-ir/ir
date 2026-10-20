<?php

namespace App\Services\Media;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Media;
use App\Models\Page;
use App\Models\Question;
use App\Models\ReusableBlock;
use App\Models\SeoMeta;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

/** Finds every place a media item is used, so editors know what a change or deletion affects. */
class MediaUsage
{
    /** @return array<int, array{type: string, title: string, url: ?string}> */
    public function find(Media $media): array
    {
        $id = $media->id;
        $usages = [];
        $jsonPattern = '"(media_id|image_id|photo_id|before_media_id|after_media_id|og_image_id|logo_id)":"?'.$id.'"?[,}]';
        $regexp = in_array(DB::getDriverName(), ['mysql', 'mariadb']) ? 'REGEXP' : 'LIKE';
        $needle = $regexp === 'REGEXP' ? $jsonPattern : '%:'.$id.'%';

        $add = fn ($type, $models) => collect($models)->each(function ($m) use (&$usages, $type) {
            $usages[] = ['type' => $type, 'title' => $m->title ?? $m->name ?? ('#'.$m->id), 'url' => method_exists($m, 'url') ? $m->url() : null];
        });

        $add('OCT case', ClinicalCase::withTrashed()->where('featured_media_id', $id)->orWhereRaw("images {$regexp} ?", [$needle])->orWhereRaw("comparison {$regexp} ?", [$needle])->get());
        $add('Article', Article::withTrashed()->where('featured_media_id', $id)->get());
        $add('Video', Video::withTrashed()->where('thumbnail_media_id', $id)->orWhere('video_media_id', $id)->get());
        $add('Question', Question::withTrashed()->where('media_id', $id)->get());
        $add('Topic', Topic::where('media_id', $id)->get());
        $add('Page', Page::withTrashed()->whereRaw("blocks {$regexp} ?", [$needle])->get());
        $add('Reusable block', ReusableBlock::whereRaw("blocks {$regexp} ?", [$needle])->get());
        $add('Social image', SeoMeta::where('og_image_id', $id)->get()->map(fn ($s) => $s->seoable)->filter());
        $add('Site setting', Setting::whereIn('key', ['branding.logo_id', 'branding.logo_dark_id', 'branding.favicon_id', 'branding.og_image_id', 'doctor.photo_id'])->where('value', (string) $id)->get()->map(fn ($s) => (object) ['title' => $s->key]));

        // Rich-text attachments are referenced by path.
        foreach ([[ClinicalCase::class, ['history', 'findings', 'discussion']], [Article::class, ['body']]] as [$class, $cols]) {
            $add(class_basename($class) === 'ClinicalCase' ? 'OCT case (in text)' : 'Article (in text)', $class::withTrashed()->where(function ($q) use ($cols, $media) {
                foreach ($cols as $c) {
                    $q->orWhere($c, 'like', '%'.$media->path.'%');
                }
            })->get());
        }

        return collect($usages)->unique(fn ($u) => $u['type'].$u['title'])->values()->all();
    }
}
