<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Video;
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
            'isAccessibleForFree' => true,
            'citation' => collect($a->references)->pluck('citation')->filter()->values()->all() ?: null,
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
            'isAccessibleForFree' => true,
            'citation' => collect($c->references)->pluck('citation')->filter()->values()->all() ?: null,
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
            'embedUrl' => $v->isFilePlayer() ? null : preg_replace('/[?&]autoplay=1/', '', (string) $v->embedUrl()),
            'contentUrl' => $v->isFilePlayer() ? $v->embedUrl() : null,
            'publisher' => self::publisher(),
            'hasPart' => collect($v->chapters)->filter(fn ($ch) => isset($ch['time'], $ch['title']))->values()->map(fn ($ch, $i) => [
                '@type' => 'Clip',
                'name' => $ch['title'],
                'startOffset' => self::seconds($ch['time']),
                'url' => $v->url().'?t='.self::seconds($ch['time']),
            ])->all() ?: null,
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
