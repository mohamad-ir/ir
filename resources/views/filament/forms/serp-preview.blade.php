@php
    $state = $getLivewire()->data ?? [];
    $seo = $state['seo'] ?? [];
    $title = filled($seo['meta_title'] ?? null) ? $seo['meta_title'] : ($state[$titleField] ?? 'Page title');
    $desc = filled($seo['meta_description'] ?? null) ? $seo['meta_description'] : strip_tags((string) ($state[$descriptionField] ?? ''));
    $template = setting('seo.title_template') ?: '%title% | %site%';
    $fullTitle = str_replace(['%title%', '%site%'], [$title, setting('site.name')], $template);
    $slug = $state['slug'] ?? '';
@endphp
<div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Google preview</p>
    <div style="font-family: arial, sans-serif; max-width: 600px">
        <div style="font-size:14px;color:#4d5156;line-height:1.3">{{ parse_url(url('/'), PHP_URL_HOST) }} › {{ $slug }}</div>
        <div style="font-size:20px;color:#1a0dab;line-height:1.3;margin-top:2px">{{ \Illuminate\Support\Str::limit($fullTitle, 62) }}</div>
        <div style="font-size:14px;color:#4d5156;line-height:1.58;margin-top:3px">{{ \Illuminate\Support\Str::limit($desc ?: 'Add a meta description or summary to control this text.', 158) }}</div>
    </div>
    @if (mb_strlen($fullTitle) > 62)
        <p class="mt-3 text-xs text-warning-600">The title is likely to be truncated in Google results.</p>
    @endif
</div>
