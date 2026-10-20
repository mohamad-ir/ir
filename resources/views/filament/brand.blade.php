@php($logo = \App\Models\Media::find(setting('branding.logo_id')))
@if ($logo)
    <img src="{{ $logo->url() }}" alt="{{ setting('site.name') }}" style="height:2rem;width:auto">
@else
    <span style="display:inline-flex;align-items:center;gap:.6rem">
        <img src="{{ asset('favicon.svg') }}" alt="" style="height:1.9rem;width:1.9rem">
        <span style="font-family:'Newsreader Variable',Georgia,serif;font-size:1.2rem;font-weight:600;letter-spacing:-.01em" class="text-gray-950 dark:text-white">{{ setting('site.name') }}</span>
    </span>
@endif
