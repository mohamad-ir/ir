@if (($data['galleries'] ?? collect())->isNotEmpty())
    <x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" action-label="All galleries" :action-url="route('galleries.index')" />
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($data['galleries'] as $gallery)<x-gallery-card :gallery="$gallery" />@endforeach
    </div>
@else
    <p class="rounded-ui bg-subtle p-6 text-center text-sm text-muted">Photo galleries — publish a gallery to show it here.</p>
@endif
