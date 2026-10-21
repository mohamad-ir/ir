@php($seo = app(\App\Services\Seo\SeoManager::class))
<meta name="description" content="{{ $seo->metaDescription() }}">
<meta name="robots" content="{{ $seo->robots() }}">
<link rel="canonical" href="{{ $seo->canonicalUrl() }}">
<meta property="og:site_name" content="{{ setting('site.name') }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->title ?: setting('site.name') }}">
<meta property="og:description" content="{{ $seo->metaDescription() }}">
<meta property="og:url" content="{{ $seo->canonicalUrl() }}">
<meta property="og:locale" content="en_GB">
@if ($image = $seo->shareImage())
<meta property="og:image" content="{{ $image }}">
@if ($seo->imageAlt)<meta property="og:image:alt" content="{{ $seo->imageAlt }}">@endif
<meta name="twitter:image" content="{{ $image }}">
@endif
@if ($seo->publishedTime)
<meta property="article:published_time" content="{{ $seo->publishedTime }}">
<meta property="article:modified_time" content="{{ $seo->modifiedTime }}">
@endif
<meta name="twitter:card" content="{{ $seo->shareImage() ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->title ?: setting('site.name') }}">
<meta name="twitter:description" content="{{ $seo->metaDescription() }}">
@if ($handle = setting('seo.twitter_handle'))<meta name="twitter:site" content="{{ '@'.ltrim($handle, '@') }}">@endif
@if ($v = setting('seo.google_verification'))<meta name="google-site-verification" content="{{ $v }}">@endif
@if ($v = setting('seo.bing_verification'))<meta name="msvalidate.01" content="{{ $v }}">@endif
<meta name="theme-color" content="{{ setting('theme.primary') }}">
<script type="application/ld+json">{!! $seo->jsonLd() !!}</script>
