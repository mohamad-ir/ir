@if (($data['webinars'] ?? collect())->isNotEmpty())
    <x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" action-label="All webinars" :action-url="route('webinars.index')" />
    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($data['webinars'] as $webinar)
            <x-webinar-card :webinar="$webinar" />
        @endforeach
    </div>
@elseif ($context['preview'] ?? false)
    <p class="rounded-ui bg-subtle p-6 text-center text-sm text-muted">Webinars section — hidden on the live site until a webinar is scheduled.</p>
@endif
