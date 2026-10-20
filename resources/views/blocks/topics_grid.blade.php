<x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" :action-label="'All topics'" :action-url="route('topics.index')" />
<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($data['topics'] ?? [] as $topic)
        <a href="{{ $topic->urlPath() }}" class="group card-interactive flex items-center gap-4 p-4">
            <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-ui bg-viewer font-heading text-sm font-bold text-white" style="{{ $topic->color ? 'background:'.$topic->color : '' }}">
                @if ($topic->media)
                    <img src="{{ $topic->media->thumbUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                @else
                    {{ \Illuminate\Support\Str::limit($topic->abbreviation ?: $topic->name, 4, '') }}
                @endif
            </span>
            <span class="min-w-0">
                <span class="block truncate font-semibold {{ $tone === 'ink' ? 'text-white' : 'text-ink' }} group-hover:text-accent-ink transition">{{ $topic->name }}</span>
                @if (! empty($data['show_counts']))
                    <span class="block text-sm text-muted">{{ $topic->cases_count }} {{ \Illuminate\Support\Str::plural('case', $topic->cases_count) }}</span>
                @endif
            </span>
        </a>
    @endforeach
</div>
