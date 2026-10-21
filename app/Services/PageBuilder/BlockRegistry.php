<?php

namespace App\Services\PageBuilder;

use App\Models\ReusableBlock;
use App\Services\PageBuilder\Blocks;
use Illuminate\Support\HtmlString;
use Throwable;

class BlockRegistry
{
    /** @var array<int, class-string<PageBlock>> */
    public const BLOCKS = [
        Blocks\HeroBlock::class,
        Blocks\RichTextBlock::class,
        Blocks\ContentFeedBlock::class,
        Blocks\CaseOfWeekBlock::class,
        Blocks\ImageTextBlock::class,
        Blocks\FeatureGridBlock::class,
        Blocks\TopicsGridBlock::class,
        Blocks\StatsBlock::class,
        Blocks\CtaBlock::class,
        Blocks\NewsletterBlock::class,
        Blocks\ProfileBlock::class,
        Blocks\CredentialsBlock::class,
        Blocks\PublicationsBlock::class,
        Blocks\TimelineBlock::class,
        Blocks\TestimonialsBlock::class,
        Blocks\LogosBlock::class,
        Blocks\FaqBlock::class,
        Blocks\VideoBlock::class,
        Blocks\GalleryBlock::class,
        Blocks\GalleriesBlock::class,
        Blocks\ExamHubBlock::class,
        Blocks\ContactFormBlock::class,
        Blocks\InterestBlock::class,
        Blocks\CoursesBlock::class,
        Blocks\WebinarsBlock::class,
        Blocks\PricingBlock::class,
        Blocks\SpacerBlock::class,
        Blocks\ReusableBlockRef::class,
    ];

    /** @return array<string, class-string<PageBlock>> */
    public function map(): array
    {
        static $map;

        return $map ??= collect(self::BLOCKS)->mapWithKeys(fn ($c) => [$c::key() => $c])->all();
    }

    public function filamentBlocks(bool $includeReusable = true): array
    {
        return collect(self::BLOCKS)
            ->when(! $includeReusable, fn ($c) => $c->reject(fn ($b) => $b === Blocks\ReusableBlockRef::class))
            ->map(fn ($c) => $c::make())->values()->all();
    }

    /** Normalises saved (list) and raw Livewire (uuid-keyed) builder state. */
    public function normalize(?array $blocks): array
    {
        return collect($blocks ?? [])->values()->filter(fn ($b) => is_array($b) && isset($b['type']))->map(fn ($b) => [
            'type' => $b['type'],
            'data' => is_array($b['data'] ?? null) ? $b['data'] : [],
        ])->all();
    }

    public function render(?array $blocks, array $context = [], int $depth = 0): HtmlString
    {
        $html = '';
        $map = $this->map();

        foreach ($this->normalize($blocks) as $index => $block) {
            $class = $map[$block['type']] ?? null;
            $data = $block['data'];
            if (! $class || ! empty($data['_hidden'])) {
                continue;
            }

            if ($class === Blocks\ReusableBlockRef::class) {
                if ($depth < 2 && ($reusable = ReusableBlock::find($data['reusable_block_id'] ?? null))) {
                    $html .= $this->render($reusable->blocks, $context, $depth + 1);
                }

                continue;
            }

            try {
                $prepared = $class::prepare($data);
                if (! $class::shouldRender($prepared, $context)) {
                    continue;
                }
                $html .= view('blocks._section', [
                    'type' => $block['type'],
                    'data' => $prepared,
                    'index' => $index,
                    'first' => $index === 0 && $depth === 0,
                    'tone' => $data['_tone'] ?? $class::defaultTone(),
                    'context' => $context,
                ])->render();
            } catch (Throwable $e) {
                report($e);
                if (app()->hasDebugModeEnabled() || ($context['preview'] ?? false)) {
                    $html .= '<div class="container-site py-6"><div class="rounded-ui bg-red-50 p-4 text-sm text-red-800">Block “'.e($class::label()).'” could not be rendered: '.e($e->getMessage()).'</div></div>';
                }
            }
        }

        return new HtmlString($html);
    }

    public function plainText(?array $blocks): string
    {
        $map = $this->map();

        return trim(collect($this->normalize($blocks))->map(function ($b) use ($map) {
            $class = $map[$b['type']] ?? null;

            return $class && empty($b['data']['_hidden']) ? $class::plainText($b['data']) : '';
        })->filter()->implode("\n"));
    }

    /** Structured data (e.g. FAQPage) contributed by blocks on a page. */
    public function schema(?array $blocks): array
    {
        $faq = collect($this->normalize($blocks))->where('type', 'faq')->reject(fn ($b) => ! empty($b['data']['_hidden']))
            ->flatMap(fn ($b) => $b['data']['items'] ?? [])->filter(fn ($i) => filled($i['question'] ?? null));

        if ($faq->isEmpty()) {
            return [];
        }

        return [[
            '@type' => 'FAQPage',
            'mainEntity' => $faq->map(fn ($i) => [
                '@type' => 'Question',
                'name' => $i['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($i['answer'] ?? '')],
            ])->values()->all(),
        ]];
    }
}
