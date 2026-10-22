<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Course;
use App\Models\Video;
use App\Models\Webinar;
use App\Support\Settings;

/** schema.org objects for content types. */
class SchemaFactory
{
    public static function author($user): array
    {
        if (! $user) {
            return ['@id' => url('/').'#doctor'];
        }

        return array_filter(['@type' => 'Person', 'name' => $user->displayName(), 'jobTitle' => $user->specialty]);
    }

    protected static function publisher(): array
    {
        return ['@type' => 'Organization', 'name' => Settings::get('site.name'), 'url' => url('/')];
    }

    public static function article(Article $a): array
    {
        return array_filter([
            '@type' => 'Article',
            'headline' => $a->title,
            'description' => $a->seoDescription(),
            'image' => $a->featuredMedia?->url('lg'),
            'datePublished' => $a->published_at?->toIso8601String(),
            'dateModified' => $a->updated_at?->toIso8601String(),
            'author' => self::author($a->author),
            'publisher' => self::publisher(),
            'mainEntityOfPage' => $a->url(),
            'articleSection' => $a->category?->name,
            'keywords' => $a->topics->pluck('name')->implode(', ') ?: null,
            'audience' => ['@type' => 'MedicalAudience', 'audienceType' => 'Clinician'],
            'isAccessibleForFree' => ($a->access_level?->value ?? 'public') === 'public',
            'citation' => collect($a->references)->pluck('citation')->filter()->values()->all() ?: null,
            'commentCount' => $a->comments_count ?: null,
        ]);
    }

    public static function case(ClinicalCase $c, bool $includeAnswer = true): array
    {
        return array_filter([
            '@type' => 'MedicalWebPage',
            'name' => $c->title,
            'headline' => $c->title,
            'description' => $c->seoDescription(),
            'url' => $c->url(),
            'image' => $c->coverImage()?->url('lg'),
            'datePublished' => $c->published_at?->toIso8601String(),
            'dateModified' => $c->updated_at?->toIso8601String(),
            'author' => self::author($c->author),
            'publisher' => self::publisher(),
            'audience' => ['@type' => 'MedicalAudience', 'audienceType' => 'Clinician'],
            'medicalAudience' => 'Clinician',
            'specialty' => 'Ophthalmologic',
            'about' => $includeAnswer && $c->diagnosis ? ['@type' => 'MedicalCondition', 'name' => $c->diagnosis] : null,
            'keywords' => $c->topics->pluck('name')->merge($c->modalities->pluck('name'))->implode(', ') ?: null,
            'isAccessibleForFree' => ($c->access_level?->value ?? 'public') === 'public',
            'citation' => collect($c->references)->pluck('citation')->filter()->values()->all() ?: null,
            'commentCount' => $c->comments_count ?: null,
        ]);
    }

    public static function video(Video $v): array
    {
        return array_filter([
            '@type' => 'VideoObject',
            'name' => $v->title,
            'description' => $v->seoDescription() ?: $v->title,
            'thumbnailUrl' => $v->thumbnail() ? [$v->thumbnail()] : null,
            'uploadDate' => $v->published_at?->toIso8601String(),
            'duration' => $v->isoDuration(),
            'embedUrl' => ! $v->isPublicAccess() || $v->isFilePlayer() ? null : preg_replace('/[?&]autoplay=1/', '', (string) $v->embedUrl()),
            'contentUrl' => $v->isPublicAccess() && $v->isFilePlayer() ? $v->embedUrl() : null,
            'isAccessibleForFree' => $v->isPublicAccess(),
            'publisher' => self::publisher(),
            'commentCount' => $v->comments_count ?: null,
            'hasPart' => collect($v->chapters)->filter(fn ($ch) => isset($ch['time'], $ch['title']))->values()->map(fn ($ch, $i) => [
                '@type' => 'Clip',
                'name' => $ch['title'],
                'startOffset' => self::seconds($ch['time']),
                'url' => $v->url().'?t='.self::seconds($ch['time']),
            ])->all() ?: null,
        ]);
    }

    public static function course(Course $c): array
    {
        $offers = collect($c->allPrices())->map(fn ($amount, $currency) => [
            '@type' => 'Offer',
            'category' => $c->isFree() ? 'Free' : 'Paid',
            'price' => number_format($amount / 100, 2, '.', ''),
            'priceCurrency' => $currency,
            'availability' => 'https://schema.org/InStock',
            'url' => $c->url(),
        ])->values()->all();

        return array_filter([
            '@type' => 'Course',
            'name' => $c->title,
            'description' => $c->seoDescription() ?: $c->title,
            'url' => $c->url(),
            'image' => $c->featuredMedia?->url('lg'),
            'provider' => self::publisher(),
            'author' => self::author($c->author),
            'educationalLevel' => $c->level?->getLabel(),
            'inLanguage' => 'en',
            'audience' => ['@type' => 'MedicalAudience', 'audienceType' => 'Clinician'],
            'teaches' => collect($c->outcomes)->pluck('text')->filter()->values()->all() ?: null,
            'offers' => $offers,
            'hasCourseInstance' => [array_filter([
                '@type' => 'CourseInstance',
                'courseMode' => 'Online',
                'courseWorkload' => $c->totalMinutes() ? 'PT'.intdiv($c->totalMinutes(), 60).'H'.($c->totalMinutes() % 60).'M' : null,
            ])],
        ]);
    }

    public static function event(Webinar $w): array
    {
        return array_filter([
            '@type' => 'Event',
            'name' => $w->title,
            'description' => $w->seoDescription() ?: $w->title,
            'url' => $w->url(),
            'image' => $w->featuredMedia?->url('lg'),
            'startDate' => $w->starts_at?->toIso8601String(),
            'endDate' => $w->endsAt()->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
            'location' => ['@type' => 'VirtualLocation', 'url' => $w->url()],
            'organizer' => self::publisher(),
            'performer' => $w->author ? self::author($w->author) : null,
            'offers' => collect($w->allPrices())->map(fn ($amount, $currency) => array_filter([
                '@type' => 'Offer',
                'price' => number_format($amount / 100, 2, '.', ''),
                'priceCurrency' => $currency,
                'availability' => $w->acceptsRegistrations() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'url' => $w->url(),
                'validFrom' => $w->published_at?->toIso8601String(),
            ]))->values()->all(),
        ]);
    }

    /** @param \Illuminate\Support\Collection<int, \App\Models\GalleryItem> $items only images that may be shown publicly */
    public static function gallery(\App\Models\Gallery $g, $items): array
    {
        return array_filter([
            '@type' => 'ImageGallery',
            'name' => $g->title,
            'description' => $g->seoDescription() ?: $g->title,
            'url' => $g->url(),
            'datePublished' => $g->published_at?->toIso8601String(),
            'dateModified' => $g->updated_at?->toIso8601String(),
            'author' => self::author($g->author),
            'publisher' => self::publisher(),
            'isAccessibleForFree' => ($g->access_level?->value ?? 'public') === 'public',
            'image' => $items->take(30)->map(fn ($i) => array_filter([
                '@type' => 'ImageObject',
                'contentUrl' => $i->media->url('lg'),
                'thumbnailUrl' => $i->media->thumbUrl(),
                'caption' => $i->captionText(),
                'width' => $i->media->width,
                'height' => $i->media->height,
                'creditText' => $i->media->credit,
            ]))->values()->all() ?: null,
        ]);
    }

    public static function seconds(string|int $time): int
    {
        if (is_int($time) || ctype_digit((string) $time)) {
            return (int) $time;
        }
        $parts = array_reverse(array_map('intval', explode(':', $time)));

        return ($parts[0] ?? 0) + ($parts[1] ?? 0) * 60 + ($parts[2] ?? 0) * 3600;
    }
}
