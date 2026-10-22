<?php

namespace App\Support;

use App\RichEditor\CalloutBlock;
use App\RichEditor\FigureBlock;
use App\RichEditor\VideoEmbedBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Throwable;

/**
 * Renders rich editor content for the public site: expands custom blocks,
 * sanitises, adds heading anchors (for the table of contents), links [n]
 * citations to the reference list, lazy-loads images and makes tables scroll.
 */
class RichText
{
    public static function customBlocks(): array
    {
        return [CalloutBlock::class, FigureBlock::class, VideoEmbedBlock::class];
    }

    protected static bool $forEmail = false;

    public static function render(string|array|null $html, array $references = []): HtmlString
    {
        return new HtmlString(static::process($html, $references)['html']);
    }

    /** For emails: videos become a linked thumbnail, since email clients cannot play them. */
    public static function renderForEmail(string|array|null $html): HtmlString
    {
        static::$forEmail = true;
        try {
            return static::render($html);
        } finally {
            static::$forEmail = false;
        }
    }

    /** @return array{html: string, toc: array<int, array{id: string, text: string, level: int}>} */
    public static function process(string|array|null $html, array $references = []): array
    {
        if (blank($html)) {
            return ['html' => '', 'toc' => []];
        }

        if (is_array($html) && ! isset($html['type'])) {
            return ['html' => '', 'toc' => []];
        }

        try {
            $html = RichContentRenderer::make($html)
                ->customBlocks(static::customBlocks())
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsVisibility('public')
                ->toUnsafeHtml();
        } catch (Throwable $e) {
            report($e);
            $html = is_string($html) ? $html : '';
        }

        $html = static::sanitizer()->sanitize($html);
        $html = static::expandVideos($html);

        $toc = [];
        $used = [];
        $html = preg_replace_callback('~<h([23])([^>]*)>(.*?)</h\1>~is', function ($m) use (&$toc, &$used) {
            $text = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5));
            if ($text === '') {
                return $m[0];
            }
            $id = Str::slug($text) ?: 'section';
            $base = $id;
            $i = 2;
            while (isset($used[$id])) {
                $id = $base.'-'.$i++;
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) $m[1]];
            $attrs = preg_replace('~\sid="[^"]*"~', '', $m[2]);

            return "<h{$m[1]} id=\"{$id}\"{$attrs}>{$m[3]}</h{$m[1]}>";
        }, $html);

        if ($references) {
            $count = count($references);
            $html = preg_replace_callback('~\[(\d{1,3}(?:\s*[,–-]\s*\d{1,3})*)\](?![^<]*</a>)~u', function ($m) use ($count) {
                $nums = preg_split('~\s*,\s*~', $m[1]);
                $links = [];
                foreach ($nums as $n) {
                    if (preg_match('~^(\d+)\s*[–-]\s*(\d+)$~u', $n, $r)) {
                        $links[] = static::citeLink((int) $r[1], $count).'–'.static::citeLink((int) $r[2], $count);
                    } else {
                        $links[] = static::citeLink((int) $n, $count);
                    }
                }

                return '<sup class="cite">['.implode(', ', $links).']</sup>';
            }, $html);
        }

        $html = preg_replace('~<img(?![^>]*\sloading=)~i', '<img loading="lazy" decoding="async"', $html);
        $html = preg_replace('~<table~i', '<div class="table-wrap"><table', $html);
        $html = preg_replace('~</table>~i', '</table></div>', $html);
        $html = preg_replace('~<a([^>]+href="https?://(?!'.preg_quote(parse_url(config('app.url'), PHP_URL_HOST) ?? '', '~').')[^"]*")~i', '<a rel="noopener" target="_blank"$1', $html);

        return ['html' => $html, 'toc' => $toc];
    }

    /**
     * Video blocks are stored as safe placeholders and replaced with the player after
     * sanitizing, built only from the block's own settings (never from raw HTML).
     */
    public static function expandVideos(string $html): string
    {
        // The sanitizer may entity-encode characters of the attribute (e.g. "=" becomes "&#61;").
        return preg_replace_callback('~<div data-video-embed="([A-Za-z0-9+/=_&#;-]+)"[^>]*>\s*</div>~', function ($m) {
            $config = json_decode((string) base64_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true), true);

            return is_array($config) ? VideoEmbed::html($config, static::$forEmail) : '';
        }, $html) ?? $html;
    }

    protected static function citeLink(int $n, int $count): string
    {
        return $n >= 1 && $n <= $count ? '<a href="#ref-'.$n.'">'.$n.'</a>' : (string) $n;
    }

    public static function sanitizer(): HtmlSanitizer
    {
        static $sanitizer;

        return $sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowElement('iframe', ['src', 'title', 'allow', 'allowfullscreen', 'loading', 'class'])
                ->allowElement('figure', ['class'])
                ->allowElement('figcaption', ['class'])
                ->allowElement('aside', ['class', 'data-tone'])
                ->allowElement('mark', ['class', 'style'])
                ->allowElement('details', ['class', 'open'])
                ->allowElement('summary', ['class'])
                ->allowElement('button', ['type', 'class', 'data-video'])
                ->allowAttribute('class', '*')
                ->allowAttribute('style', ['p', 'h2', 'h3', 'h4', 'span', 'mark', 'td', 'th', 'li', 'div'])
                ->allowAttribute('colspan', ['td', 'th'])
                ->allowAttribute('rowspan', ['td', 'th'])
                ->allowAttribute('loading', ['img'])
                ->allowAttribute('srcset', ['img'])
                ->allowAttribute('sizes', ['img'])
                ->allowAttribute('width', ['img'])
                ->allowAttribute('height', ['img'])
                ->allowAttribute('data-video-embed', ['div'])
                ->allowAttribute('data-col-span', '*')
                ->allowAttribute('data-cols', '*')
                ->allowAttribute('data-from-breakpoint', '*')
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowMediaSchemes(['https', 'http'])
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->forceAttribute('iframe', 'loading', 'lazy')
                ->withMaxInputLength(2_000_000)
        );
    }
}
